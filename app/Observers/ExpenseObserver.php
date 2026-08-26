<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Services\ActivityLogger;
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
        $oldTitle      = $expense->getOriginal('title') ?: $expense->getOriginal('category');

        $this->updateExpenseBalance($oldLocationId, $oldMethod, $oldAmount, $oldTitle, $expense);
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
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->firstOrFail();

            $oldBalance = (float) $balance->{$balanceCol};
            $newBalance = $oldBalance - $amount;
            $balance->update([$balanceCol => $newBalance]);

            $transaction = LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'expense_id'   => $expense->id,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Expense: ' . ($expense->title ?: $expense->category),
                'created_by'   => LocationBalanceTransaction::getFallbackUserId($expense->created_by),
            ]);

            ActivityLogger::log(
                'Accounting',
                'create',
                $transaction,
                [$balanceCol => $oldBalance],
                [$balanceCol => $newBalance],
                'Balance deducted for Expense: ' . ($expense->title ?: $expense->category) . ' (' . format_price($amount) . ')'
            );
        });
    }

    private function updateExpenseBalance(?int $oldLocationId, ?string $oldMethod, float $oldAmount, ?string $oldTitle, Expense $expense): void
    {
        $oldType = $this->resolveBalanceType($oldMethod);
        $newType = $this->resolveBalanceType($expense->payment_method);
        $oldCol  = $oldType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $newCol  = $newType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $newAmount = (float) $expense->amount;
        $newLocationId = (int) $expense->location_id;
        $newNote = 'Expense: ' . ($expense->title ?: $expense->category);
        $oldNote = 'Expense: ' . $oldTitle;

        DB::transaction(function () use ($oldLocationId, $oldCol, $oldAmount, $newLocationId, $newCol, $newAmount, $newType, $newNote, $oldNote, $expense) {
            if ($oldLocationId === $newLocationId && $oldCol === $newCol) {
                // Same location and balance type: update by difference
                $newBalance = LocationBalance::where('location_id', $newLocationId)->lockForUpdate()->first();
                $newBalanceVal = 0.0;
                if ($newBalance) {
                    $diff = $newAmount - $oldAmount;
                    $newBalanceVal = (float) $newBalance->{$newCol} - $diff;
                    $newBalance->update([
                        $newCol => $newBalanceVal
                    ]);
                }
            } else {
                // Restore old amount
                if ($oldLocationId && $oldAmount > 0) {
                    $oldBalance = LocationBalance::where('location_id', $oldLocationId)->lockForUpdate()->first();
                    if ($oldBalance) {
                        $oldBalance->update([
                            $oldCol => (float) $oldBalance->{$oldCol} + $oldAmount
                        ]);
                    }
                }

                // Deduct new amount
                $newBalanceVal = 0.0;
                if ($newLocationId && $newAmount > 0) {
                    $newBalance = LocationBalance::where('location_id', $newLocationId)->lockForUpdate()->first();
                    if ($newBalance) {
                        $newBalanceVal = (float) $newBalance->{$newCol} - $newAmount;
                        $newBalance->update([
                            $newCol => $newBalanceVal
                        ]);
                    }
                }
            }

            $existingTx = LocationBalanceTransaction::where('expense_id', $expense->id)->first();
            if (!$existingTx) {
                $existingTx = LocationBalanceTransaction::where(function ($q) use ($oldNote, $newNote) {
                    $q->where('notes', $oldNote)->orWhere('notes', $newNote);
                })->where('location_id', $oldLocationId ?: $newLocationId)->first();
            }

            $transaction = $existingTx;
            if ($existingTx) {
                $existingTx->update([
                    'location_id'  => $newLocationId,
                    'expense_id'   => $expense->id,
                    'balance_type' => $newType,
                    'amount'       => $newAmount,
                    'balance_after'=> $newBalanceVal,
                    'notes'        => $newNote,
                ]);
            } else if ($newLocationId && $newAmount > 0) {
                $transaction = LocationBalanceTransaction::create([
                    'location_id'  => $newLocationId,
                    'expense_id'   => $expense->id,
                    'balance_type' => $newType,
                    'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'       => $newAmount,
                    'balance_after'=> $newBalanceVal,
                    'notes'        => $newNote,
                    'created_by'   => LocationBalanceTransaction::getFallbackUserId($expense->created_by),
                ]);
            }

            ActivityLogger::log(
                'Accounting',
                'update',
                $transaction,
                [$oldCol => $oldAmount],
                [$newCol => $newAmount],
                'Balance adjusted for updated ' . $newNote
            );
        });
    }

    private function reverseBalance(Expense $expense, float $amount, int $locationId, ?string $paymentMethod): void
    {
        $balanceType = $this->resolveBalanceType($paymentMethod);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';
        $note = 'Expense: ' . ($expense->title ?: $expense->category);

        DB::transaction(function () use ($locationId, $balanceCol, $amount, $note, $expense) {
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->first();
            $oldBalance = $balance ? (float) $balance->{$balanceCol} : null;
            $newBalance = $oldBalance;
            if ($balance) {
                $newBalance = $oldBalance + $amount;
                $balance->update([$balanceCol => $newBalance]);
            }

            $deletedCount = LocationBalanceTransaction::where('expense_id', $expense->id)->delete();
            if ($deletedCount === 0) {
                LocationBalanceTransaction::whereIn('notes', [$note, 'Reversal: ' . $note])
                    ->where('location_id', $locationId)
                    ->delete();
            }

            ActivityLogger::log(
                'Accounting',
                'delete',
                null,
                $balance ? [$balanceCol => $oldBalance] : null,
                $balance ? [$balanceCol => $newBalance] : null,
                'Balance reversed for deleted ' . $note . ' (' . format_price($amount) . ')'
            );
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
