<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Additive dummy-data generator for Purchase Bills (inter-location stock
 * transfers) only — doesn't touch sales/expenses. Safe to run alongside
 * existing data; only moves inventory/balances for bills it marks accepted,
 * mirroring PurchaseBillController::accept().
 */
class PurchaseBillDummyDataSeeder extends Seeder
{
    private int $counter = 0;

    /** @var array<int, array<int, array{date: Carbon, balanceType: string, type: string, amount: float, notes: string}>> */
    private array $events = [];

    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->first()
            ?? User::first();

        if (!$admin) {
            $this->command?->warn('No admin user found — skipping purchase bill dummy data.');
            return;
        }

        $locations = Location::where('status', 1)->get();
        $products  = Product::where('type', '!=', 'variable')->where('status', 1)->get();

        if ($locations->count() < 2 || $products->isEmpty()) {
            $this->command?->warn('Need at least 2 active locations and 1 product — skipping purchase bill dummy data.');
            return;
        }

        foreach ($locations as $location) {
            $this->events[$location->id] = [];
        }

        $billCount = 18;
        $productIds = $products->pluck('id');

        for ($i = 0; $i < $billCount; $i++) {
            $pair = $locations->random(2)->values();
            $from = $pair[0];
            $to   = $pair[1];

            $available = Inventory::where('location_id', $from->id)
                ->whereIn('product_id', $productIds)
                ->where('quantity', '>', 5)
                ->inRandomOrder()
                ->first();

            if (!$available) {
                continue;
            }

            $product = $products->firstWhere('id', $available->product_id);
            if (!$product) {
                continue;
            }

            $date = now()->subDays(rand(1, 25))->setTime(rand(10, 18), rand(0, 59));
            $qty  = max(1, min(6, intdiv($available->quantity, 2)));
            $paymentMethod = rand(0, 1) ? 'cash' : 'online';
            $transferNo = 'PB-DM-' . $from->id . $to->id . '-' . str_pad(++$this->counter, 5, '0', STR_PAD_LEFT);

            // Weighted spread: mostly accepted (so reports/dashboard have data),
            // a few pending, occasional rejected — mirrors a real bill queue.
            $roll = rand(1, 100);
            $status = $roll <= 70 ? PurchaseBill::STATUS_ACCEPTED
                : ($roll <= 90 ? PurchaseBill::STATUS_PENDING : PurchaseBill::STATUS_REJECTED);
            $paymentStatus = rand(0, 1) ? PurchaseBill::PAYMENT_STATUS_PAID : PurchaseBill::PAYMENT_STATUS_PENDING;

            $bill = PurchaseBill::create([
                'transfer_no'      => $transferNo,
                'from_location_id' => $from->id,
                'to_location_id'   => $to->id,
                'status'           => $status,
                'payment_method'   => $paymentMethod,
                'payment_status'   => $status === PurchaseBill::STATUS_REJECTED ? PurchaseBill::PAYMENT_STATUS_PENDING : $paymentStatus,
                'created_by'       => $admin->id,
                'accepted_by'      => $status === PurchaseBill::STATUS_PENDING ? null : $admin->id,
                'accepted_at'      => $status === PurchaseBill::STATUS_PENDING ? null : $date,
                'created_at'       => $date,
            ]);

            PurchaseBillItem::create([
                'purchase_bill_id' => $bill->id,
                'product_id'       => $product->id,
                'quantity'         => $qty,
            ]);

            if ($status !== PurchaseBill::STATUS_ACCEPTED) {
                continue;
            }

            $value = round((float) $product->purchase_price * $qty, 2);

            $available->decrement('quantity', $qty);

            $destination = Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $to->id],
                ['quantity' => 0, 'created_by' => $admin->id]
            );
            $destination->increment('quantity', $qty);

            if ($bill->payment_status === PurchaseBill::PAYMENT_STATUS_PAID && $value > 0) {
                $balanceType = $paymentMethod === 'online'
                    ? LocationBalanceTransaction::BALANCE_TYPE_BANK
                    : LocationBalanceTransaction::BALANCE_TYPE_CASH;

                $this->addEvent($from->id, $date, $balanceType, LocationBalanceTransaction::TYPE_CREDIT, $value, 'Purchase Bill Out #' . $transferNo);
                $this->addEvent($to->id, $date, $balanceType, LocationBalanceTransaction::TYPE_DEBIT, $value, 'Purchase Bill In #' . $transferNo);
            }
        }

        $this->replayBalanceEvents();

        $this->command?->info('Purchase bill dummy data seeded.');
    }

    private function addEvent(int $locationId, Carbon $date, string $balanceType, string $type, float $amount, string $notes): void
    {
        $this->events[$locationId][] = [
            'date'        => $date,
            'balanceType' => $balanceType,
            'type'        => $type,
            'amount'      => $amount,
            'notes'       => $notes,
        ];
    }

    private function replayBalanceEvents(): void
    {
        foreach ($this->events as $locationId => $events) {
            if (empty($events)) {
                continue;
            }

            usort($events, fn ($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

            $location = Location::find($locationId);
            $runningCash = (float) $location->cash_balance;
            $runningBank = (float) $location->bank_balance;

            foreach ($events as $event) {
                if ($event['balanceType'] === LocationBalanceTransaction::BALANCE_TYPE_CASH) {
                    $runningCash = $event['type'] === LocationBalanceTransaction::TYPE_CREDIT
                        ? $runningCash + $event['amount']
                        : max(0, $runningCash - $event['amount']);
                    $balanceAfter = $runningCash;
                } else {
                    $runningBank = $event['type'] === LocationBalanceTransaction::TYPE_CREDIT
                        ? $runningBank + $event['amount']
                        : max(0, $runningBank - $event['amount']);
                    $balanceAfter = $runningBank;
                }

                LocationBalanceTransaction::create([
                    'location_id'   => $locationId,
                    'balance_type'  => $event['balanceType'],
                    'type'          => $event['type'],
                    'amount'        => $event['amount'],
                    'balance_after' => $balanceAfter,
                    'notes'         => $event['notes'],
                    'created_by'    => $location->created_by,
                    'created_at'    => $event['date'],
                    'updated_at'    => $event['date'],
                ]);
            }

            $location->update([
                'cash_balance' => $runningCash,
                'bank_balance' => $runningBank,
            ]);
        }
    }
}
