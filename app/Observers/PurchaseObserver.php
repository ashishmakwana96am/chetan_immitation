<?php

namespace App\Observers;

use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\Purchase;
use App\Services\ActivityLogger;
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
            $advancePaid = (float) \App\Models\PurchasePayment::where('purchase_id', $purchase->id)
                ->where('is_advance', true)
                ->sum('amount');

            $directPaid = (float) \App\Models\PurchasePayment::where('purchase_id', $purchase->id)
                ->whereNull('bulk_purchase_payment_id')
                ->where(function ($q) {
                    $q->where('is_advance', false)->orWhereNull('is_advance');
                })->sum('amount');

            $amountToDeduct = $directPaid > 0 ? $directPaid : max(0.0, (float) $purchase->paid_amount - $advancePaid);
            if ($amountToDeduct > 0) {
                $this->deductBalance($purchase, $amountToDeduct);
            }
        }
    }

    public function updated(Purchase $purchase): void
    {
        $oldPaid   = (float) $purchase->getOriginal('paid_amount');
        $oldMethod = $purchase->getOriginal('payment_method');

        $this->updatePurchaseBalance($oldPaid, $oldMethod, $purchase);
    }

    public function deleted(Purchase $purchase): void
    {
        if ($purchase->paid_amount > 0) {
            $this->refundBalance($purchase, (float) $purchase->paid_amount);
        }
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function resolvePurchaseLocationId(Purchase $purchase): int
    {
        $defaultLoc = \App\Models\Location::where('is_default', true)->first()
            ?? \App\Models\Location::where('status', 1)->first()
            ?? \App\Models\Location::first();

        return $defaultLoc ? (int) $defaultLoc->id : 1;
    }

    private function deductBalance(Purchase $purchase, float $amount): void
    {
        $locationId = $this->resolvePurchaseLocationId($purchase);

        if (!$locationId || $amount <= 0) {
            return;
        }

        $balanceType = $this->resolveBalanceType($purchase->payment_method);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceType, $balanceCol, $amount, $purchase) {
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->first();
            if (!$balance) {
                $balance = LocationBalance::create([
                    'location_id'  => $locationId,
                    'cash_balance' => 0,
                    'bank_balance' => 0,
                ]);
            }

            $oldBalance = (float) $balance->{$balanceCol};
            $newBalance = $oldBalance - $amount;
            $balance->update([$balanceCol => $newBalance]);

            $transaction = LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Purchase #' . $purchase->invoice_no,
                'created_by'   => LocationBalanceTransaction::getFallbackUserId($purchase->created_by),
            ]);

            ActivityLogger::log(
                'Accounting',
                'create',
                $transaction,
                [$balanceCol => $oldBalance],
                [$balanceCol => $newBalance],
                'Balance deducted for Purchase #' . $purchase->invoice_no . ' (' . format_price($amount) . ')'
            );
        });

        LocationBalanceTransaction::syncLocationBalance($locationId, $balanceType);
    }

    private function updatePurchaseBalance(float $oldPaidInput, ?string $oldMethod, Purchase $purchase): void
    {
        $locationId = $this->resolvePurchaseLocationId($purchase);

        if (!$locationId) {
            return;
        }

        $oldType = $this->resolveBalanceType($oldMethod);
        $newType = $this->resolveBalanceType($purchase->payment_method);
        $oldCol  = $oldType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $newCol  = $newType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';

        $note = 'Purchase #' . $purchase->invoice_no;
        $existingTx = LocationBalanceTransaction::where('notes', $note)->first();
        $oldPaid = $existingTx ? (float) $existingTx->amount : 0.0;

        // Calculate only direct cashbook payments (excluding advance payments and bulk payments)
        $newPaid = (float) \App\Models\PurchasePayment::where('purchase_id', $purchase->id)
            ->whereNull('bulk_purchase_payment_id')
            ->where(function ($q) {
                $q->where('is_advance', false)->orWhereNull('is_advance');
            })->sum('amount');

        DB::transaction(function () use ($locationId, $oldCol, $oldPaid, $newCol, $newPaid, $oldType, $newType, $note, $purchase) {
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->first();

            if ($balance) {
                if ($oldCol === $newCol) {
                    $diff = $newPaid - $oldPaid;
                    $newBalanceVal = (float) $balance->{$newCol} - $diff;
                    $balance->update([
                        $newCol => $newBalanceVal
                    ]);
                } else {
                    if ($oldPaid > 0) {
                        $balance->update([
                            $oldCol => (float) $balance->{$oldCol} + $oldPaid
                        ]);
                    }
                    $newBalanceVal = (float) $balance->{$newCol} - $newPaid;
                    $balance->update([
                        $newCol => $newBalanceVal
                    ]);
                }
            } else {
                $newBalanceVal = 0.0;
            }

            $existingTx = LocationBalanceTransaction::where('notes', $note)->first();
            $transaction = null;

            if ($existingTx) {
                if ($newPaid <= 0) {
                    $existingTx->delete();
                } else {
                    $existingTx->update([
                        'location_id'  => $locationId,
                        'balance_type' => $newType,
                        'amount'       => $newPaid,
                        'balance_after'=> $newBalanceVal,
                    ]);
                    $transaction = $existingTx;
                }
            } else if ($newPaid > 0) {
                $transaction = LocationBalanceTransaction::create([
                    'location_id'  => $locationId,
                    'balance_type' => $newType,
                    'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                    'amount'       => $newPaid,
                    'balance_after'=> $newBalanceVal,
                    'notes'        => $note,
                    'created_by'   => LocationBalanceTransaction::getFallbackUserId($purchase->created_by),
                ]);
            }

            if ($transaction) {
                ActivityLogger::log(
                    'Accounting',
                    'update',
                    $transaction,
                    [$oldCol => $oldPaid],
                    [$newCol => $newPaid],
                    'Balance adjusted for updated Purchase #' . $purchase->invoice_no
                );
            }
        });

        LocationBalanceTransaction::syncLocationBalance($locationId, $oldType);
        if ($oldType !== $newType) {
            LocationBalanceTransaction::syncLocationBalance($locationId, $newType);
        }
    }

    private function refundBalance(Purchase $purchase, float $amount): void
    {
        $locationId = $this->resolvePurchaseLocationId($purchase);

        if (!$locationId || $amount <= 0) {
            return;
        }

        $balanceType = $this->resolveBalanceType($purchase->payment_method);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceCol, $amount, $purchase) {
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->first();
            $oldBalance = $balance ? (float) $balance->{$balanceCol} : null;
            $newBalance = $oldBalance;
            if ($balance) {
                $newBalance = $oldBalance + $amount;
                $balance->update([$balanceCol => $newBalance]);
            }

            $cleanInvoiceNo = preg_replace('/^DEL-\d+-/i', '', $purchase->invoice_no);
            $note = 'Purchase #' . $cleanInvoiceNo;
            LocationBalanceTransaction::whereIn('notes', [$note, 'Purchase #' . $purchase->invoice_no, 'Refund/Correction: ' . $note])->delete();

            ActivityLogger::log(
                'Accounting',
                'delete',
                null,
                $balance ? [$balanceCol => $oldBalance] : null,
                $balance ? [$balanceCol => $newBalance] : null,
                'Balance refunded on deletion of Purchase #' . $cleanInvoiceNo . ' (' . format_price($amount) . ')'
            );
        });

        LocationBalanceTransaction::syncLocationBalance($locationId, $balanceType);
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
