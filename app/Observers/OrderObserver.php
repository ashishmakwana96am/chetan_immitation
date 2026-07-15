<?php

namespace App\Observers;

use App\Models\Location;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
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

        if (
            strtolower($order->payment_method ?? '') === 'cod' &&
            $order->payment_status != \App\Models\Order::PAYMENT_STATUS_PAID
        ) {
            return;
        }

        $this->creditBalance($order);
    }

    /**
     * When payment_status changes to PAID (e.g. COD delivery confirmed),
     * credit the balance at that point.
     * Also handles payment_method changes on POS sales.
     */
    public function updated(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        if (
            strtolower($order->payment_method ?? '') === 'cod' &&
            $order->wasChanged('payment_status') &&
            $order->payment_status == \App\Models\Order::PAYMENT_STATUS_PAID
        ) {
            $this->creditBalance($order);
            return;
        }

        if (
            $order->source !== 'ONLINE' &&
            ($order->wasChanged('payment_method') || $order->wasChanged('final_amount'))
        ) {
            $oldMethod = $order->getOriginal('payment_method');
            $oldAmount = (float) $order->getOriginal('final_amount');
            $this->reverseBalance($order->location_id, $oldMethod, $oldAmount, $order->order_no);
            $this->creditBalance($order);
        }
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
            $location = Location::where('id', $order->location_id)->lockForUpdate()->firstOrFail();

            $newBalance = (float) $location->{$balanceCol} + (float) $order->final_amount;
            $location->update([$balanceCol => $newBalance]);

            LocationBalanceTransaction::create([
                'location_id'  => $order->location_id,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_CREDIT,
                'amount'       => $order->final_amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Sale #' . $order->order_no,
                'created_by'   => $order->user_id,
            ]);
        });
    }

    private function reverseBalance(int $locationId, ?string $paymentMethod, float $amount, string $orderNo): void
    {
        if (!$locationId || $amount <= 0) {
            return;
        }

        $balanceType = $this->resolveBalanceType($paymentMethod);
        $balanceCol  = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        DB::transaction(function () use ($locationId, $balanceType, $balanceCol, $amount, $orderNo) {
            $location = Location::where('id', $locationId)->lockForUpdate()->firstOrFail();

            $newBalance = max(0, (float) $location->{$balanceCol} - $amount);
            $location->update([$balanceCol => $newBalance]);

            LocationBalanceTransaction::create([
                'location_id'  => $locationId,
                'balance_type' => $balanceType,
                'type'         => LocationBalanceTransaction::TYPE_DEBIT,
                'amount'       => $amount,
                'balance_after'=> $newBalance,
                'notes'        => 'Reversal: Sale #' . $orderNo,
                'created_by'   => auth()->id(),
            ]);
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
