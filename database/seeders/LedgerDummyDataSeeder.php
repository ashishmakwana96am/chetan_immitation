<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Additive dummy-data generator for Cash / Bank / Branch ledgers (doesn't
 * truncate anything, safe to run alongside real/existing data).
 *
 * Orders and Expenses are inserted with Model::withoutEvents() so their
 * observers (which stamp LocationBalanceTransaction rows with "now") don't
 * fire — instead every financial event across sales/expenses/transfers is
 * collected, sorted chronologically per location, and replayed once at the
 * end so balance_after values stay consistent and dates match what's shown
 * on the Order/Expense/PurchaseBill records themselves.
 */
class LedgerDummyDataSeeder extends Seeder
{
    /** @var array<int, array<int, array{date: Carbon, balanceType: string, type: string, amount: float, notes: string}>> */
    private array $events = [];

    private int $counter = 0;

    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->first()
            ?? User::first();

        if (!$admin) {
            $this->command?->warn('No admin user found — skipping ledger dummy data.');
            return;
        }

        $locations = Location::where('status', 1)->get();
        $products  = Product::where('type', '!=', 'variable')->where('status', 1)->get();
        $customerIds = Customer::pluck('id')->all();

        foreach ($locations as $location) {
            $this->events[$location->id] = [];
        }

        $this->seedSales($locations, $products, $customerIds, $admin->id);
        $this->seedExpenses($locations, $admin->id);
        $this->seedStockTransfers($locations, $products, $admin->id);
        $this->replayBalanceEvents();
    }

    private function seedSales(Collection $locations, Collection $products, array $customerIds, int $adminId): void
    {
        if ($products->isEmpty()) {
            return;
        }

        foreach ($locations as $location) {
            for ($i = 0; $i < 5; $i++) {
                $date = now()->subDays(rand(0, 18))->setTime(rand(10, 20), rand(0, 59));
                $paymentMethod = rand(0, 1) ? 'cash' : 'online';

                $item1 = $products->random();
                $item2 = $products->random();
                $qty1 = rand(1, 3);
                $qty2 = rand(1, 3);
                $total = round(((float) $item1->sale_price * $qty1) + ((float) $item2->sale_price * $qty2), 2);

                $orderNo = 'SAL-DM-' . $location->id . '-' . str_pad(++$this->counter, 5, '0', STR_PAD_LEFT);

                $order = Order::withoutEvents(fn () => Order::create([
                    'customer_id'    => $customerIds ? $customerIds[array_rand($customerIds)] : null,
                    'location_id'    => $location->id,
                    'user_id'        => $adminId,
                    'order_no'       => $orderNo,
                    'order_type'     => 'sale',
                    'status'         => Order::STATUS_APPROVE,
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'payment_method' => $paymentMethod,
                    'final_amount'   => $total,
                    'created_at'     => $date,
                ]));

                foreach ([[$item1, $qty1], [$item2, $qty2]] as [$item, $qty]) {
                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $item->id,
                        'quantity'        => $qty,
                        'price'           => $item->sale_price,
                        'discount_type'   => 'flat',
                        'discount_value'  => 0,
                        'discount_amount' => 0,
                        'total'           => $item->sale_price * $qty,
                    ]);
                }

                $this->addEvent(
                    $location->id,
                    $date,
                    $this->resolveOrderBalanceType($paymentMethod),
                    LocationBalanceTransaction::TYPE_CREDIT,
                    $total,
                    'Sale #' . $orderNo
                );
            }
        }
    }

    private function seedExpenses(Collection $locations, int $adminId): void
    {
        $items = [
            ['title' => 'Shop Rent', 'category' => 'Rent'],
            ['title' => 'Electricity Bill', 'category' => 'Utility'],
            ['title' => 'Packaging Material', 'category' => 'Other'],
            ['title' => 'Staff Salary', 'category' => 'Salary'],
            ['title' => 'Local Transport', 'category' => 'Transport'],
            ['title' => 'Shop Maintenance', 'category' => 'Maintenance'],
        ];

        foreach ($locations as $location) {
            for ($i = 0; $i < 4; $i++) {
                $date = now()->subDays(rand(0, 18))->setTime(rand(9, 19), rand(0, 59));
                $paymentMethod = rand(0, 1) ? 'Cash' : 'Online';
                $pick = $items[array_rand($items)];
                $amount = (float) rand(300, 4500);

                Expense::withoutEvents(fn () => Expense::create([
                    'location_id'    => $location->id,
                    'title'          => $pick['title'],
                    'category'       => $pick['category'],
                    'amount'         => $amount,
                    'payment_method' => $paymentMethod,
                    'expense_date'   => $date->toDateString(),
                    'created_by'     => $adminId,
                    'created_at'     => $date,
                ]));

                $this->addEvent(
                    $location->id,
                    $date,
                    $this->resolveExpenseBalanceType($paymentMethod),
                    LocationBalanceTransaction::TYPE_DEBIT,
                    $amount,
                    'Expense: ' . $pick['title']
                );
            }
        }
    }

    private function seedStockTransfers(Collection $locations, Collection $products, int $adminId): void
    {
        if ($locations->count() < 2 || $products->isEmpty()) {
            return;
        }

        $pairsCount = min(6, $locations->count());
        $shuffled = $locations->shuffle()->values();
        $productIds = $products->pluck('id');

        for ($i = 0; $i < $pairsCount; $i++) {
            $from = $shuffled[$i];
            $to   = $shuffled[($i + 1) % $pairsCount];

            if ($from->id === $to->id) {
                continue;
            }

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

            $date = now()->subDays(rand(1, 14))->setTime(rand(10, 17), rand(0, 59));
            $qty = max(1, min(5, intdiv($available->quantity, 2)));
            $paymentMethod = rand(0, 1) ? 'cash' : 'online';
            $value = round((float) $product->purchase_price * $qty, 2);
            $transferNo = 'ST-DM-' . $from->id . $to->id . '-' . str_pad(++$this->counter, 5, '0', STR_PAD_LEFT);

            $bill = PurchaseBill::create([
                'transfer_no'      => $transferNo,
                'from_location_id' => $from->id,
                'to_location_id'   => $to->id,
                'status'           => PurchaseBill::STATUS_ACCEPTED,
                'payment_method'   => $paymentMethod,
                'created_by'       => $adminId,
                'accepted_by'      => $adminId,
                'accepted_at'      => $date,
                'created_at'       => $date,
            ]);

            PurchaseBillItem::create([
                'purchase_bill_id' => $bill->id,
                'product_id'       => $product->id,
                'quantity'         => $qty,
            ]);

            $available->decrement('quantity', $qty);

            $destination = Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $to->id],
                ['quantity' => 0, 'created_by' => $adminId]
            );
            $destination->increment('quantity', $qty);

            $balanceType = in_array($paymentMethod, ['online', 'bank_transfer'], true)
                ? LocationBalanceTransaction::BALANCE_TYPE_BANK
                : LocationBalanceTransaction::BALANCE_TYPE_CASH;

            // Mirrors PurchaseBillController::accept(): the sending branch is
            // paid (credit) for the stock, the receiving branch pays (debit).
            $this->addEvent($from->id, $date, $balanceType, LocationBalanceTransaction::TYPE_CREDIT, $value, 'Stock Transfer Out #' . $transferNo);
            $this->addEvent($to->id, $date, $balanceType, LocationBalanceTransaction::TYPE_DEBIT, $value, 'Stock Transfer In #' . $transferNo);
        }
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

    private function resolveOrderBalanceType(string $paymentMethod): string
    {
        $bank = ['upi', 'online', 'razorpay', 'bank_transfer', 'bank transfer', 'cod'];

        return in_array(strtolower($paymentMethod), $bank, true)
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;
    }

    private function resolveExpenseBalanceType(string $paymentMethod): string
    {
        $bank = ['online', 'upi', 'bank transfer', 'bank_transfer', 'card'];

        return in_array(strtolower($paymentMethod), $bank, true)
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;
    }
}
