<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use Illuminate\Support\Facades\DB;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        if ($expense->amount > 0 && $expense->location_id) {
            $this->deductBalance($expense, (float) $expense->amount, $expense->location_id, $expense->payment_method);
        }
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        if (
            !$expense->wasChanged('location_id') &&
            !$expense->wasChanged('amount') &&
            !$expense->wasChanged('payment_method')
        ) {
            return;
        }

        $oldLocationId = $expense->getOriginal('location_id');
        $oldAmount     = (float) $expense->getOriginal('amount');
        $oldMethod     = $expense->getOriginal('payment_method');

        if ($oldLocationId && $oldAmount > 0) {
            $this->reverseBalance($expense, $oldAmount, $oldLocationId, $oldMethod);
        }

        if ($expense->location_id && $expense->amount > 0) {
            $this->deductBalance($expense, (float) $expense->amount, $expense->location_id, $expense->payment_method);
        }
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        if ($expense->location_id && $expense->amount > 0) {
            $this->reverseBalance($expense, (float) $expense->amount, $expense->location_id, $expense->payment_method);
        }
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function deductBalance(Expense $expense, float $amount, int $locationId, ?string $paymentMethod): void
    {
        $balanceType = $this->resolveBalanceType($paymentMethod);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceType, $balanceCol, $amount, $expense) {
            $location = Location::where('id', $locationId)->lockForUpdate()->firstOrFail();

            $newBalance = max(0, (float) $location->{$balanceCol} - $amount);
            $location->update([$balanceCol => $newBalance]);

            LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Expense: ' . ($expense->title ?: $expense->category),
                'created_by'   => $expense->created_by ?? auth()->id(),
            ]);
        });
    }

    private function reverseBalance(Expense $expense, float $amount, int $locationId, ?string $paymentMethod): void
    {
        $balanceType = $this->resolveBalanceType($paymentMethod);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceType, $balanceCol, $amount, $expense) {
            $location = Location::where('id', $locationId)->lockForUpdate()->firstOrFail();

            $newBalance = (float) $location->{$balanceCol} + $amount;
            $location->update([$balanceCol => $newBalance]);

            LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_CREDIT,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Reversal: Expense ' . ($expense->title ?: $expense->category),
                'created_by'   => auth()->id(),
            ]);
        });
    }

    /**
     * cash / anything else → cash_balance
     * online / bank / upi  → bank_balance
     */
    private function resolveBalanceType(?string $paymentMethod): string
    {
        $online = ['online', 'upi', 'bank transfer', 'bank_transfer', 'card'];

        return in_array(strtolower($paymentMethod ?? ''), $online, true)
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;
    }
}
