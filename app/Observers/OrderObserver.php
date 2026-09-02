<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
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
        \Illuminate\Support\Facades\Cache::forget('dashboard_super_admin_data');
        if ($order->location_id) {
            \Illuminate\Support\Facades\Cache::forget("dashboard_location_data_{$order->location_id}");
        }
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

        $this->debitCustomerWalletForSale(
            $order->customer_id,
            (float) $order->paid_cash_amount,
            (float) $order->paid_online_amount,
            (float) $order->final_amount,
            $order->payment_method,
            $order->order_no,
            $order->user_id ?? $order->created_by,
            null,
            (bool) $order->use_credit_balance
        );
    }

    /**
     * When payment_status changes to PAID or PARTIAL, credit the balance.
     * If payment_status was PAID/PARTIAL and changed to PENDING, reverse the balance.
     * Also handles payment_method / final_amount / location / cash-online split changes for PAID/PARTIAL orders.
     */
    public function updated(Order $order): void
    {
        \Illuminate\Support\Facades\Cache::forget('dashboard_super_admin_data');
        if ($order->location_id) {
            \Illuminate\Support\Facades\Cache::forget("dashboard_location_data_{$order->location_id}");
        }
        if ($order->order_type !== 'sale') {
            return;
        }

        $oldOrderNo = $order->getOriginal('order_no') ?? $order->order_no;
        $newOrderNo = $order->order_no;

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
                $newOrderNo,
                $order->user_id ?? $order->created_by
            );

            $this->debitCustomerWalletForSale(
                $order->customer_id,
                (float) $order->paid_cash_amount,
                (float) $order->paid_online_amount,
                (float) $order->final_amount,
                $order->payment_method,
                $newOrderNo,
                $order->user_id ?? $order->created_by,
                null,
                (bool) $order->use_credit_balance
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
                $oldOrderNo
            );

            $this->reverseCustomerWalletForSale(
                $order->getOriginal('customer_id'),
                (float) $order->getOriginal('paid_cash_amount'),
                (float) $order->getOriginal('paid_online_amount'),
                $order->getOriginal('payment_method'),
                (float) $order->getOriginal('final_amount'),
                $oldOrderNo
            );
            return;
        }

        if ($wasPaidLike && $isPaidLike) {
            if (
                $order->wasChanged('payment_method')
                || $order->wasChanged('final_amount')
                || $order->wasChanged('location_id')
                || $order->wasChanged('customer_id')
                || $order->wasChanged('paid_cash_amount')
                || $order->wasChanged('paid_online_amount')
                || $order->wasChanged('payment_status')
                || $order->wasChanged('use_credit_balance')
                || $order->wasChanged('order_no')
            ) {
                $this->updateSaleBalance($order, $oldOrderNo, $newOrderNo);
                $this->updateCustomerWalletForSale($order, $oldOrderNo, $newOrderNo);
            }
        }
    }

    /**
     * When a paid or partially-paid sale order is deleted, reverse whatever
     * was actually credited (branch ledger + customer wallet) for it. Must
     * match the "wasPaidLike" check in updated() below — a Partial sale has
     * genuinely debited the customer's wallet for its paid_cash/online amount,
     * so skipping it here (as this used to, checking PAID only) silently
     * leaves that money stuck out of the customer's balance forever.
     */
    public function deleted(Order $order): void
    {
        if ($order->order_type !== 'sale') {
            return;
        }

        if (!in_array((int) $order->payment_status, [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL], true)) {
            return;
        }

        $orderNo = $order->getOriginal('order_no') ?? $order->order_no;

        $this->removeSaleBalance(
            (int) $order->location_id,
            (float) $order->paid_cash_amount,
            (float) $order->paid_online_amount,
            $order->payment_method,
            (float) $order->final_amount,
            $orderNo
        );

        $this->reverseCustomerWalletForSale(
            $order->customer_id,
            (float) $order->paid_cash_amount,
            (float) $order->paid_online_amount,
            $order->payment_method,
            (float) $order->final_amount,
            $orderNo
        );
    }

    /**
     * Update existing location balance transactions in-place when a sale is updated.
     */
    private function updateSaleBalance(Order $order, string $oldOrderNo, string $newOrderNo): void
    {
        $oldLocationId = (int) $order->getOriginal('location_id');
        $newLocationId = (int) $order->location_id;
        $userId = $order->user_id ?? $order->created_by;

        $newCash = (float) $order->paid_cash_amount;
        $newOnline = (float) $order->paid_online_amount;
        $newFinal = (float) $order->final_amount;
        $newMethod = $order->payment_method;

        $desired = [];
        if ($newCash <= 0 && $newOnline <= 0) {
            if ($newFinal > 0) {
                $desired[] = [
                    'balance_type' => $this->resolveBalanceType($newMethod),
                    'amount'       => $newFinal,
                    'notes'        => 'Sale #' . $newOrderNo,
                ];
            }
        } else {
            if ($newCash > 0) {
                $desired[] = [
                    'balance_type' => LocationBalanceTransaction::BALANCE_TYPE_CASH,
                    'amount'       => $newCash,
                    'notes'        => 'Sale #' . $newOrderNo . ' (Cash)',
                ];
            }
            if ($newOnline > 0) {
                $desired[] = [
                    'balance_type' => LocationBalanceTransaction::BALANCE_TYPE_BANK,
                    'amount'       => $newOnline,
                    'notes'        => 'Sale #' . $newOrderNo . ' (Online)',
                ];
            }
        }

        $existingTxs = LocationBalanceTransaction::where(function ($q) use ($oldOrderNo, $newOrderNo) {
            $q->where('notes', 'LIKE', '%Sale #' . $oldOrderNo . '%')
              ->orWhere('notes', 'LIKE', '%Sale #' . $newOrderNo . '%');
        })->orderBy('id', 'asc')->get();

        $desiredCount = count($desired);
        $existingCount = $existingTxs->count();

        DB::transaction(function () use ($desired, $existingTxs, $newLocationId, $userId, $desiredCount, $existingCount) {
            for ($i = 0; $i < $desiredCount; $i++) {
                $item = $desired[$i];
                if ($i < $existingCount) {
                    
                    $tx = $existingTxs[$i];
                    $oldNotes = $tx->notes;
                    $oldAmt = (float) $tx->amount;

                    $tx->update([
                        'location_id'  => $newLocationId,
                        'balance_type' => $item['balance_type'],
                        'type'         => LocationBalanceTransaction::TYPE_CREDIT,
                        'amount'       => $item['amount'],
                        'notes'        => $item['notes'],
                    ]);

                    ActivityLogger::log(
                        'Accounting',
                        'update',
                        $tx,
                        ['notes' => $oldNotes, 'amount' => $oldAmt],
                        ['notes' => $item['notes'], 'amount' => $item['amount']],
                        'Balance updated for ' . $item['notes'] . ' (₹ ' . number_format($item['amount'], 2) . ')'
                    );
                } else {
                    $tx = LocationBalanceTransaction::create([
                        'location_id'  => $newLocationId,
                        'balance_type' => $item['balance_type'],
                        'type'         => LocationBalanceTransaction::TYPE_CREDIT,
                        'amount'       => $item['amount'],
                        'balance_after'=> 0,
                        'notes'        => $item['notes'],
                        'created_by'   => LocationBalanceTransaction::getFallbackUserId($userId),
                    ]);

                    ActivityLogger::log(
                        'Accounting',
                        'create',
                        $tx,
                        null,
                        ['notes' => $item['notes'], 'amount' => $item['amount']],
                        'Balance credited for ' . $item['notes'] . ' (₹ ' . number_format($item['amount'], 2) . ')'
                    );
                }
            }

            for ($i = $desiredCount; $i < $existingCount; $i++) {
                $existingTxs[$i]->delete();
            }
        });

        if ($oldLocationId) {
            LocationBalanceTransaction::syncLocationBalance($oldLocationId, LocationBalanceTransaction::BALANCE_TYPE_CASH);
            LocationBalanceTransaction::syncLocationBalance($oldLocationId, LocationBalanceTransaction::BALANCE_TYPE_BANK);
        }
        if ($newLocationId && $newLocationId !== $oldLocationId) {
            LocationBalanceTransaction::syncLocationBalance($newLocationId, LocationBalanceTransaction::BALANCE_TYPE_CASH);
            LocationBalanceTransaction::syncLocationBalance($newLocationId, LocationBalanceTransaction::BALANCE_TYPE_BANK);
        }
    }

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
    private function removeSaleBalance(?int $locationId, float $cashAmount, float $onlineAmount, ?string $paymentMethod, float $finalAmount, string $orderNo, bool $isUpdateReversal = false): void
    {
        if (!$locationId) {
            return;
        }

        $txs = LocationBalanceTransaction::where('notes', 'LIKE', '%Sale #' . $orderNo . '%')->get();
        foreach ($txs as $tx) {
            $tx->delete();
        }

        LocationBalanceTransaction::syncLocationBalance($locationId, LocationBalanceTransaction::BALANCE_TYPE_CASH);
        LocationBalanceTransaction::syncLocationBalance($locationId, LocationBalanceTransaction::BALANCE_TYPE_BANK);
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

        LocationBalanceTransaction::syncLocationBalance($locationId, $balanceType);
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

    // ─────────────────────────────────────────────
    // Credit customer wallet (mirrors the cash/bank
    // ledger crediting above, but debits the paying
    // customer's own balance when they are a credit
    // customer). Only fires for PAID/PARTIAL sales —
    // a PENDING sale never touches the wallet.
    // ─────────────────────────────────────────────

    private function updateCustomerWalletForSale(Order $order, string $oldOrderNo, string $newOrderNo): void
    {
        $oldCustId = $order->getOriginal('customer_id');
        $newCustId = $order->customer_id;
        $userId = $order->user_id ?? $order->created_by;

        $existingCustTxs = CustomerBalanceTransaction::where(function ($q) use ($oldOrderNo, $newOrderNo) {
            $q->where('notes', 'LIKE', '%Sale #' . $oldOrderNo . '%')
              ->orWhere('notes', 'LIKE', '%Sale #' . $newOrderNo . '%');
        })->orderBy('id', 'asc')->get();

        $customer = $newCustId ? Customer::find($newCustId) : null;
        $isCreditCust = $customer && $customer->is_credit_customer && (bool) $order->use_credit_balance;

        $newFinal = (float) $order->final_amount;
        $newOnline = (float) $order->paid_online_amount;
        $newMethod = $order->payment_method;

        if ($isCreditCust) {
            $totalAvail = max(0.0, (float) $customer->balance);
            if ((int) $oldCustId === (int) $newCustId) {
                $totalAvail += (float) $existingCustTxs->sum('amount');
            }
            $toDebit = min($newFinal, $totalAvail);
        } else {
            $toDebit = 0.0;
        }

        if ($toDebit > 0 && $customer) {
            $onlineMethods = ['online', 'upi', 'razorpay', 'bank_transfer', 'bank transfer'];
            $isOnline = in_array(strtolower($newMethod ?? ''), $onlineMethods, true) || $newOnline > 0;
            $source = $isOnline ? CustomerBalanceTransaction::SOURCE_BANK : CustomerBalanceTransaction::SOURCE_CASH;
            $label = $isOnline ? ' (Online)' : ' (Cash)';
            $notes = 'Sale #' . $newOrderNo . $label;

            DB::transaction(function () use ($existingCustTxs, $newCustId, $source, $toDebit, $notes, $userId, $customer) {
                $oldBalance = (float) $customer->balance;
                if ($existingCustTxs->isNotEmpty()) {
                    $tx = $existingCustTxs->first();
                    $tx->update([
                        'customer_id'   => $newCustId,
                        'source'        => $source,
                        'type'          => CustomerBalanceTransaction::TYPE_DEBIT,
                        'amount'        => $toDebit,
                        'notes'         => $notes,
                    ]);

                    for ($i = 1; $i < $existingCustTxs->count(); $i++) {
                        $existingCustTxs[$i]->delete();
                    }
                } else {
                    CustomerBalanceTransaction::create([
                        'customer_id'   => $newCustId,
                        'source'        => $source,
                        'type'          => CustomerBalanceTransaction::TYPE_DEBIT,
                        'amount'        => $toDebit,
                        'balance_after' => max(0.0, $oldBalance - $toDebit),
                        'notes'         => $notes,
                        'created_by'    => CustomerBalanceTransaction::getFallbackUserId($userId),
                    ]);
                }
            });
        } else {
            if ($existingCustTxs->isNotEmpty()) {
                foreach ($existingCustTxs as $tx) {
                    $tx->delete();
                }
            }
        }
    }

    /**
     * Debit a credit customer's wallet by the amount actually paid on a sale.
     */
    private function debitCustomerWalletForSale(?int $customerId, float $cashAmount, float $onlineAmount, float $finalAmount, ?string $paymentMethod, string $orderNo, ?int $userId, ?string $customLogDescription = null, bool $useCreditBalance = true): void
    {
        if (!$customerId || !$useCreditBalance) {
            return;
        }

        $customer = Customer::find($customerId);
        if (!$customer || !$customer->is_credit_customer) {
            return;
        }

        $totalAvail = max(0.0, (float) $customer->balance);
        if ($totalAvail <= 0) {
            return;
        }

        $toDebit = min((float) $finalAmount, $totalAvail);

        if ($toDebit <= 0) {
            return;
        }

        $onlineMethods = ['online', 'upi', 'razorpay', 'bank_transfer', 'bank transfer'];
        $isOnline = in_array(strtolower($paymentMethod ?? ''), $onlineMethods, true) || $onlineAmount > 0;

        $source = $isOnline ? CustomerBalanceTransaction::SOURCE_BANK : CustomerBalanceTransaction::SOURCE_CASH;
        $label = $isOnline ? ' (Online)' : ' (Cash)';

        $this->applyCustomerWalletChange($customerId, $toDebit, $source, 'Sale #' . $orderNo . $label, $userId, $customLogDescription);
    }

    /**
     * Reverse a previously debited wallet amount (sale cancelled, deleted, or
     * edited). Mirrors debitCustomerWalletForSale's split/legacy handling.
     */
    private function reverseCustomerWalletForSale(?int $customerId, float $cashAmount, float $onlineAmount, ?string $paymentMethod, float $finalAmount, string $orderNo, bool $isUpdateReversal = false): void
    {
        if (!$customerId) {
            return;
        }

        $customer = Customer::find($customerId);
        if (!$customer || !$customer->is_credit_customer) {
            return;
        }

        $matching = CustomerBalanceTransaction::where('customer_id', $customerId)
            ->where('notes', 'LIKE', '%Sale #' . $orderNo . '%')
            ->get();

        if ($matching->isEmpty()) {
            return;
        }

        $actualAmount = (float) $matching->sum('amount');
        $oldBalance = (float) $customer->balance;

        foreach ($matching as $tx) {
            $tx->delete();
        }

        if (!$isUpdateReversal) {
            $freshCustomer = $customer->fresh();
            ActivityLogger::log(
                'Customer Balance',
                'delete',
                null,
                ['balance' => $oldBalance],
                ['balance' => (float) ($freshCustomer ? $freshCustomer->balance : 0)],
                'Balance reversed for Sale #' . $orderNo . ' (' . format_price($actualAmount) . ')'
            );
        }
    }

    /**
     * Apply a debit (sale paid) or reversal (credit back) to a customer's
     * wallet balance. The debit amount is expected to already be capped to
     * the customer's available balance by SaleController::capPaymentToCustomerBalance
     * before the order is saved — the floor at 0 below is just a safety net,
     * not the primary enforcement, since a wallet balance must never go negative.
     */
    private function applyCustomerWalletChange(int $customerId, float $amount, string $source, string $note, ?int $userId, ?string $customLogDescription = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($customerId, $amount, $source, $note, $userId, $customLogDescription) {
            $customer = Customer::where('id', $customerId)->lockForUpdate()->first();
            if (!$customer) {
                return;
            }

            $oldBalance = (float) $customer->balance;
            $newBalance = max(0.0, $oldBalance - $amount);

            $transaction = CustomerBalanceTransaction::create([
                'customer_id'   => $customerId,
                'source'        => $source,
                'type'          => CustomerBalanceTransaction::TYPE_DEBIT,
                'amount'        => $amount,
                'balance_after' => $newBalance,
                'notes'         => $note,
                'created_by'    => CustomerBalanceTransaction::getFallbackUserId($userId),
            ]);

            $logDesc = $customLogDescription ?: ('Balance debited for ' . $note . ' (' . format_price($amount) . ')');

            ActivityLogger::log(
                'Customer Balance',
                $customLogDescription ? 'update' : 'create',
                $transaction,
                ['balance' => $oldBalance],
                ['balance' => (float) ($customer->fresh()?->balance ?? $newBalance)],
                $logDesc
            );
        });
    }
}
