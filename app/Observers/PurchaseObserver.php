<?php

namespace App\Observers;

use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseObserver
{
    /**
     * When a purchase is created with paid_amount > 0,
     * deduct from the location's cash or bank balance.
     */
    public function created(Purchase $purchase): void
    {
        if ($purchase->paid_amount > 0) {
            $this->deductBalance($purchase, (float) $purchase->paid_amount);
        }
    }

    /**
     * When paid_amount increases on update, deduct the difference.
     */
    public function updated(Purchase $purchase): void
    {
        if (!$purchase->wasChanged('paid_amount')) {
            return;
        }

        $oldPaid = (float) $purchase->getOriginal('paid_amount');
        $newPaid = (float) $purchase->paid_amount;
        $diff    = $newPaid - $oldPaid;

        if ($diff > 0) {
            $this->deductBalance($purchase, $diff);
        }
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function deductBalance(Purchase $purchase, float $amount): void
    {
        $locationId = $purchase->location_id;

        if (!$locationId) {
            $locationId = $purchase->items()
                ->with('allocations')
                ->get()
                ->flatMap(fn($item) => $item->allocations)
                ->first()
                ?->location_id;
        }

        if (!$locationId || $amount <= 0) {
            return;
        }

        $balanceType = $this->resolveBalanceType($purchase->payment_method);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceType, $balanceCol, $amount, $purchase) {
            $location = Location::where('id', $locationId)->lockForUpdate()->firstOrFail();

            $newBalance = max(0, (float) $location->{$balanceCol} - $amount);
            $location->update([$balanceCol => $newBalance]);

            LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Purchase #' . $purchase->invoice_no,
                'created_by'   => $purchase->created_by ?? auth()->id(),
            ]);
        });
    }

    /**
     * cash / anything else → cash_balance
     * upi / online / bank  → bank_balance
     */
    private function resolveBalanceType(?string $paymentMethod): string
    {
        $online = ['online', 'razorpay', 'bank_transfer', 'bank transfer'];

        return in_array(strtolower($paymentMethod ?? ''), $online, true)
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;
    }
}
