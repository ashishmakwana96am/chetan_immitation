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
     *  - POS Cash / POS UPI sale (source != ONLINE) → credit immediately
     *  - Online / Razorpay (payment_method = online, payment_status = PAID) → credit immediately
     *  - COD (payment_method = cod, payment_status = PENDING) → skip now,
     *    credit when payment_status is later updated to PAID
     */
    public function created(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        if ($order->payment_status != \App\Models\Order::PAYMENT_STATUS_PAID) {
            return;
        }

        $this->creditBalance($order);
    }

    /**
     * When payment_status changes to PAID, credit the balance.
     * If payment_status was PAID and changed to something else, reverse the balance.
     * Also handles payment_method / final_amount changes for PAID orders.
     */
    public function updated(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        $oldStatus = $order->getOriginal('payment_status');
        $newStatus = $order->payment_status;

        // Transition 1: Unpaid -> PAID
        if ($oldStatus != \App\Models\Order::PAYMENT_STATUS_PAID && $newStatus == \App\Models\Order::PAYMENT_STATUS_PAID) {
            $this->creditBalance($order);
            return;
        }

        // Transition 2: PAID -> Unpaid (e.g. status set back to pending)
        if ($oldStatus == \App\Models\Order::PAYMENT_STATUS_PAID && $newStatus != \App\Models\Order::PAYMENT_STATUS_PAID) {
            $this->removeSaleBalance((int) $order->getOriginal('location_id'), $order->getOriginal('payment_method'), (float) $order->getOriginal('final_amount'), $order->order_no);
            return;
        }

        // Transition 3: Was PAID, remains PAID, but method, amount, or location changed
        if ($oldStatus == \App\Models\Order::PAYMENT_STATUS_PAID && $newStatus == \App\Models\Order::PAYMENT_STATUS_PAID) {
            if ($order->wasChanged('payment_method') || $order->wasChanged('final_amount') || $order->wasChanged('location_id')) {
                $oldLocationId = (int) $order->getOriginal('location_id');
                $oldMethod     = $order->getOriginal('payment_method');
                $oldAmount     = (float) $order->getOriginal('final_amount');
                $newLocationId = (int) $order->location_id;
                $newMethod     = $order->payment_method;
                $newAmount     = (float) $order->final_amount;

                $this->updateSaleBalance($oldLocationId, $oldMethod, $oldAmount, $newLocationId, $newMethod, $newAmount, $order);
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

        if ($order->payment_status != \App\Models\Order::PAYMENT_STATUS_PAID) {
            return;
        }

        $this->removeSaleBalance((int) $order->location_id, $order->payment_method, (float) $order->final_amount, $order->order_no);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function creditBalance(Order $order): void
    {
        if (!$order->location_id || $order->final_amount <= 0) {
            return;
        }

        $balanceType = $this->resolveBalanceType($order->payment_method);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($order, $balanceType, $balanceCol) {
            $balance = LocationBalance::where('location_id', $order->location_id)->lockForUpdate()->firstOrFail();

            $oldBalance = (float) $balance->{$balanceCol};
            $newBalance = $oldBalance + (float) $order->final_amount;
            $balance->update([$balanceCol => $newBalance]);

            $transaction = LocationBalanceTransaction::create([
                'location_id'  => $order->location_id,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_CREDIT,
                'amount'       => $order->final_amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Sale #' . $order->order_no,
                'created_by'   => LocationBalanceTransaction::getFallbackUserId($order->user_id ?? $order->created_by),
            ]);

            ActivityLogger::log(
                'Accounting',
                'create',
                $transaction,
                [$balanceCol => $oldBalance],
                [$balanceCol => $newBalance],
                'Balance credited for Sale #' . $order->order_no . ' (' . format_price($order->final_amount) . ')'
            );
        });
    }

    private function updateSaleBalance(int $oldLocationId, ?string $oldMethod, float $oldAmount, int $newLocationId, ?string $newMethod, float $newAmount, Order $order): void
    {
        $oldType = $this->resolveBalanceType($oldMethod);
        $newType = $this->resolveBalanceType($newMethod);
        $oldCol  = $oldType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
        $newCol  = $newType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';

        DB::transaction(function () use ($oldLocationId, $oldCol, $oldAmount, $oldType, $newLocationId, $newCol, $newAmount, $newType, $order) {
            if ($oldLocationId === $newLocationId && $oldCol === $newCol) {
                // Same location & balance type: update by difference
                $newLocBalance = LocationBalance::where('location_id', $newLocationId)->lockForUpdate()->first();
                $newBalanceVal = 0.0;
                if ($newLocBalance) {
                    $diff = $newAmount - $oldAmount;
                    $newBalanceVal = (float)$newLocBalance->{$newCol} + $diff;
                    $newLocBalance->update([
                        $newCol => $newBalanceVal
                    ]);
                }
            } else {
                // Location or payment method changed: deduct old, add new
                if ($oldLocationId > 0 && $oldAmount > 0) {
                    $oldLocBalance = LocationBalance::where('location_id', $oldLocationId)->lockForUpdate()->first();
                    if ($oldLocBalance) {
                        $oldLocBalance->update([
                            $oldCol => (float)$oldLocBalance->{$oldCol} - $oldAmount
                        ]);
                    }
                }

                $newLocBalance = LocationBalance::where('location_id', $newLocationId)->lockForUpdate()->first();
                $newBalanceVal = 0.0;
                if ($newLocBalance) {
                    $newBalanceVal = (float)$newLocBalance->{$newCol} + $newAmount;
                    $newLocBalance->update([
                        $newCol => $newBalanceVal
                    ]);
                }
            }

            // Find existing transaction for this sale
            $existingTx = LocationBalanceTransaction::where('notes', 'Sale #' . $order->order_no)->first();
            $transaction = $existingTx;
            if ($existingTx) {
                $existingTx->update([
                    'location_id'  => $newLocationId,
                    'balance_type' => $newType,
                    'amount'       => $newAmount,
                    'balance_after'=> $newBalanceVal,
                ]);
            } else {
                $transaction = LocationBalanceTransaction::create([
                    'location_id'  => $newLocationId,
                    'balance_type' => $newType,
                    'type'         => LocationBalanceTransaction::TYPE_CREDIT,
                    'amount'       => $newAmount,
                    'balance_after'=> $newBalanceVal,
                    'notes'        => 'Sale #' . $order->order_no,
                    'created_by'   => LocationBalanceTransaction::getFallbackUserId($order->user_id ?? $order->created_by),
                ]);
            }

            ActivityLogger::log(
                'Accounting',
                'update',
                $transaction,
                [$oldCol => $oldAmount],
                [$newCol => $newAmount],
                'Balance adjusted for updated Sale #' . $order->order_no
            );
        });
    }

    private function removeSaleBalance(int $locationId, ?string $paymentMethod, float $amount, string $orderNo): void
    {
        if (!$locationId || $amount <= 0) {
            return;
        }

        $balanceType = $this->resolveBalanceType($paymentMethod);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceCol, $amount, $orderNo) {
            $balance = LocationBalance::where('location_id', $locationId)->lockForUpdate()->first();
            $oldBalance = $balance ? (float) $balance->{$balanceCol} : null;
            $newBalance = $oldBalance;
            if ($balance) {
                $newBalance = $oldBalance - $amount;
                $balance->update([$balanceCol => $newBalance]);
            }

            LocationBalanceTransaction::whereIn('notes', ['Sale #' . $orderNo, 'Reversal: Sale #' . $orderNo])->delete();

            ActivityLogger::log(
                'Accounting',
                'delete',
                null,
                $balance ? [$balanceCol => $oldBalance] : null,
                $balance ? [$balanceCol => $newBalance] : null,
                'Balance reversed for cancelled/deleted Sale #' . $orderNo . ' (' . format_price($amount) . ')'
            );
        });
    }

    /**
     * Determine balance type from payment method.
     * online / upi / razorpay / bank_transfer → bank
     * cash / anything else                   → cash
     */
    private function resolveBalanceType(?string $paymentMethod): string
    {
        $online = ['upi', 'online', 'razorpay', 'bank_transfer', 'bank transfer', 'cod'];

        return in_array(strtolower($paymentMethod ?? ''), $online, true)
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;
    }
}
