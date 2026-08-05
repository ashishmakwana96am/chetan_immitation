<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use Illuminate\Support\Facades\DB;

class CustomerBalanceTransactionObserver
{
    public function created(CustomerBalanceTransaction $transaction): void
    {
        $this->updateCustomerBalances($transaction->customer_id);
    }

    public function updated(CustomerBalanceTransaction $transaction): void
    {
        $this->updateCustomerBalances($transaction->customer_id);

        if ($transaction->wasChanged('customer_id')) {
            $oldCustomerId = $transaction->getOriginal('customer_id');
            if ($oldCustomerId) {
                $this->updateCustomerBalances((int) $oldCustomerId);
            }
        }
    }

    public function deleted(CustomerBalanceTransaction $transaction): void
    {
        $this->updateCustomerBalances($transaction->customer_id);
    }

    public function updateCustomerBalances(int $customerId): void
    {
        $transactions = CustomerBalanceTransaction::where('customer_id', $customerId)
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0.0;
        foreach ($transactions as $tx) {
            if ($tx->type === CustomerBalanceTransaction::TYPE_CREDIT) {
                $runningBalance += (float) $tx->amount;
            } else {
                $runningBalance -= (float) $tx->amount;
            }

            if ((float) $tx->balance_after !== (float) $runningBalance) {
                DB::table('customer_balance_transactions')
                    ->where('id', $tx->id)
                    ->update(['balance_after' => $runningBalance]);
            }
        }

        $cashBal = 0.0;
        $bankBal = 0.0;

        foreach ($transactions as $tx) {
            $amt = (float) $tx->amount;
            if ($tx->type === CustomerBalanceTransaction::TYPE_CREDIT) {
                if ($tx->source === 'bank') {
                    $bankBal += $amt;
                } else {
                    $cashBal += $amt;
                }
            } else { // DEBIT
                if ($tx->source === 'bank') {
                    if ($bankBal >= $amt) {
                        $bankBal -= $amt;
                    } else {
                        $rem = $amt - $bankBal;
                        $bankBal = 0.0;
                        $cashBal -= $rem;
                    }
                } else { // cash
                    if ($cashBal >= $amt) {
                        $cashBal -= $amt;
                    } else {
                        $rem = $amt - $cashBal;
                        $cashBal = 0.0;
                        $bankBal -= $rem;
                    }
                }
            }
        }

        if ($cashBal < 0 && $bankBal > 0) {
            $offset = min(abs($cashBal), $bankBal);
            $cashBal += $offset;
            $bankBal -= $offset;
        } elseif ($bankBal < 0 && $cashBal > 0) {
            $offset = min(abs($bankBal), $cashBal);
            $bankBal += $offset;
            $cashBal -= $offset;
        }

        $cashBalance = $cashBal;
        $bankBalance = $bankBal;
        $totalBalance = $cashBalance + $bankBalance;

        DB::table('customer_balances')->updateOrInsert(
            ['customer_id' => $customerId],
            [
                'balance'      => $totalBalance,
                'cash_balance' => $cashBalance,
                'bank_balance' => $bankBalance,
                'updated_at'   => now(),
            ]
        );
    }
}
