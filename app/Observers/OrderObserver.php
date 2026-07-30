<?php

namespace App\Observers;

use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    /**
     * When a sale order is created, credit the appropriate balance.
     *
     * Rules:
     *  - POS Cash / POS UPI sale (source != ONLINE) → credit immediately, split
     *    across cash/bank per paid_cash_amount / paid_online_amount
     *  - Online / Razorpay (payment_method = online, payment_status = PAID) → credit immediately
     *  - COD (payment_method = cod, payment_status = PENDING) → skip now,
     *    credit when payment_status is later updated to PAID
     */
    public function created(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        $isPaidLike = in_array((int) $order->payment_status, [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL], true);
        if (!$isPaidLike) {
            return;
        }

        $this->creditBalance(
            $order->location_id,
            (float) $order->paid_cash_amount,
            (float) $order->paid_online_amount,
            (float) $order->final_amount,
            $order->payment_method,
            $order->order_no,
            $order->user_id ?? $order->created_by
        );
    }

    /**
     * When payment_status changes to PAID or PARTIAL, credit the balance.
     * If payment_status was PAID/PARTIAL and changed to PENDING, reverse the balance.
     * Also handles payment_method / final_amount / location / cash-online split changes for PAID/PARTIAL orders.
     */
    public function updated(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        $oldStatus = (int) $order->getOriginal('payment_status');
        $newStatus = (int) $order->payment_status;

        $wasPaidLike = in_array($oldStatus, [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL], true);
        $isPaidLike = in_array($newStatus, [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL], true);

        // Transition 1: Unpaid -> PAID / PARTIAL
        if (!$wasPaidLike && $isPaidLike) {
            $this->creditBalance(
                $order->location_id,
                (float) $order->paid_cash_amount,
                (float) $order->paid_online_amount,
                (float) $order->final_amount,
                $order->payment_method,
                $order->order_no,
                $order->user_id ?? $order->created_by
            );
            return;
        }

        // Transition 2: Was PAID / PARTIAL -> Unpaid
        if ($wasPaidLike && !$isPaidLike) {
            $order->salePayments()->delete();
            $order->payments()->delete();
            $this->removeSaleBalance(
                (int) $order->getOriginal('location_id'),
                (float) $order->getOriginal('paid_cash_amount'),
                (float) $order->getOriginal('paid_online_amount'),
                $order->getOriginal('payment_method'),
                (float) $order->getOriginal('final_amount'),
                $order->order_no
            );
            return;
        }

        // Transition 3: Was PAID / PARTIAL, remains PAID / PARTIAL, but method, amount, location, or split changed
        if ($wasPaidLike && $isPaidLike) {
            if (
                $order->wasChanged('payment_method')
                || $order->wasChanged('final_amount')
                || $order->wasChanged('location_id')
                || $order->wasChanged('paid_cash_amount')
                || $order->wasChanged('paid_online_amount')
                || $order->wasChanged('payment_status')
            ) {
                // Reverse the previously recorded allocation, then credit the new one.
                $this->removeSaleBalance(
                    (int) $order->getOriginal('location_id'),
                    (float) $order->getOriginal('paid_cash_amount'),
                    (float) $order->getOriginal('paid_online_amount'),
                    $order->getOriginal('payment_method'),
                    (float) $order->getOriginal('final_amount'),
                    $order->order_no,
                    true
                );

                $oldAmt = (float) $order->getOriginal('final_amount');
                $newAmt = (float) $order->final_amount;
                $editDesc = 'Balance updated for Sale #' . $order->order_no . ' (Old: ' . format_price($oldAmt) . ' → New: ' . format_price($newAmt) . ')';

                $this->creditBalance(
                    $order->location_id,
                    (float) $order->paid_cash_amount,
                    (float) $order->paid_online_amount,
                    (float) $order->final_amount,
                    $order->payment_method,
                    $order->order_no,
                    $order->user_id ?? $order->created_by,
                    $editDesc
                );
            }
        }
    }

    /**
     * When a paid sale order is deleted, reverse the credited balance.
     */
    public function deleted(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        if ($order->payment_status != Order::PAYMENT_STATUS_PAID) {
            return;
        }

        $this->removeSaleBalance(
            (int) $order->location_id,
            (float) $order->paid_cash_amount,
            (float) $order->paid_online_amount,
            $order->payment_method,
            (float) $order->final_amount,
            $order->order_no
        );
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    /**
     * Credit cash/bank balance for a paid sale. Uses the explicit cash/online
     * split (paid_cash_amount / paid_online_amount) when present; falls back
     * to a single balance type derived from payment_method for flows that
     * don't set the split columns (e.g. online storefront checkout).
     */
    private function creditBalance(?int $locationId, float $cashAmount, float $onlineAmount, float $finalAmount, ?string $paymentMethod, string $orderNo, ?int $userId): void
    {
        if (!$locationId) {
            return;
        }

        if ($cashAmount <= 0 && $onlineAmount <= 0) {
            if ($finalAmount <= 0) {
                return;
            }

            $this->applyBalanceChange($locationId, $this->resolveBalanceType($paymentMethod), $finalAmount, LocationBalanceTransaction::TYPE_CREDIT, 'Sale #' . $orderNo, $userId);
            return;
        }

        if ($cashAmount > 0) {
            $this->applyBalanceChange($locationId, LocationBalanceTransaction::BALANCE_TYPE_CASH, $cashAmount, LocationBalanceTransaction::TYPE_CREDIT, 'Sale #' . $orderNo . ' (Cash)', $userId);
        }
        if ($onlineAmount > 0) {
            $this->applyBalanceChange($locationId, LocationBalanceTransaction::BALANCE_TYPE_BANK, $onlineAmount, LocationBalanceTransaction::TYPE_CREDIT, 'Sale #' . $orderNo . ' (Online)', $userId);
        }
    }

    /**
     * Reverse a previously credited sale (cancellation, deletion, or edit that
     * changes the allocation). Mirrors creditBalance's split/legacy handling.
     */
    private function removeSaleBalance(int $locationId, float $cashAmount, float $onlineAmount, ?string $paymentMethod, float $finalAmount, string $orderNo, bool $isUpdateReversal = false): void
    {
        if (!$locationId) {
            return;
        }

        if ($cashAmount <= 0 && $onlineAmount <= 0) {
            if ($finalAmount <= 0) {
                return;
            }

            $this->applyBalanceChange($locationId, $this->resolveBalanceType($paymentMethod), $finalAmount, LocationBalanceTransaction::TYPE_DEBIT, 'Sale #' . $orderNo, null, true, $isUpdateReversal);
            return;
        }

        if ($cashAmount > 0) {
            $this->applyBalanceChange($locationId, LocationBalanceTransaction::BALANCE_TYPE_CASH, $cashAmount, LocationBalanceTransaction::TYPE_DEBIT, 'Sale #' . $orderNo . ' (Cash)', null, true, $isUpdateReversal);
        }
        if ($onlineAmount > 0) {
            $this->applyBalanceChange($locationId, LocationBalanceTransaction::BALANCE_TYPE_BANK, $onlineAmount, LocationBalanceTransaction::TYPE_DEBIT, 'Sale #' . $orderNo . ' (Online)', null, true, $isUpdateReversal);
        }
    }

    /**
     * Apply a credit/debit to a location's cash or bank balance and record the
     * activity. On reversal, the prior transaction row (matched by its note)
     * is removed so it doesn't linger after a cancellation/edit.
     */
    private function applyBalanceChange(int $locationId, string $balanceType, float $amount, string $direction, string $note, ?int $userId, bool $isReversal = false, bool $isUpdateReversal = false, ?string $customLogDescription = null): void
    {
        $balanceCol = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceType, $balanceCol, $amount, $direction, $note, $userId, $isReversal, $isUpdateReversal, $customLogDescription) {
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->first();
            if (!$balance) {
                return;
            }

            $oldBalance = (float) $balance->{$balanceCol};
            $newBalance = $direction === LocationBalanceTransaction::TYPE_CREDIT
                ? $oldBalance + $amount
                : $oldBalance - $amount;
            $balance->update([$balanceCol => $newBalance]);

            if ($isReversal) {
                LocationBalanceTransaction::where('notes', 'LIKE', '%' . $note . '%')->delete();

                if (!$isUpdateReversal) {
                    ActivityLogger::log(
                        'Accounting',
                        'delete',
                        null,
                        [$balanceCol => $oldBalance],
                        [$balanceCol => $newBalance],
                        'Balance reversed for ' . $note . ' (' . format_price($amount) . ')'
                    );
                }

                return;
            }

            $transaction = LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'balance_type' => $balanceType,
                'type'         => $direction,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => $note,
                'created_by'   => LocationBalanceTransaction::getFallbackUserId($userId),
            ]);

            $logDesc = $customLogDescription ?: ('Balance credited for ' . $note . ' (' . format_price($amount) . ')');

            ActivityLogger::log(
                'Accounting',
                $customLogDescription ? 'update' : 'create',
                $transaction,
                [$balanceCol => $oldBalance],
                [$balanceCol => $newBalance],
                $logDesc
            );
        });
    }

    /**
     * cash / anything else → cash_balance
     * online / upi / razorpay / bank_transfer / cod → bank_balance
     */
    private function resolveBalanceType(?string $paymentMethod): string
    {
        $online = ['upi', 'online', 'razorpay', 'bank_transfer', 'bank transfer', 'cod'];

        return in_array(strtolower($paymentMethod ?? ''), $online, true)
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;
    }
}
