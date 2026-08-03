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
    }

    public function deleted(CustomerBalanceTransaction $transaction): void
    {
        $this->updateCustomerBalances($transaction->customer_id);
    }

    public function updateCustomerBalances(int $customerId): void
    {
        $query = CustomerBalanceTransaction::where('customer_id', $customerId);

        $cashCredit = (float) (clone $query)->where('source', 'cash')->where('type', 'credit')->sum('amount');
        $cashDebit  = (float) (clone $query)->where('source', 'cash')->where('type', 'debit')->sum('amount');

        $bankCredit = (float) (clone $query)->where('source', 'bank')->where('type', 'credit')->sum('amount');
        $bankDebit  = (float) (clone $query)->where('source', 'bank')->where('type', 'debit')->sum('amount');

        $cashBalance = $cashCredit - $cashDebit;
        $bankBalance = $bankCredit - $bankDebit;
        $totalBalance = $cashBalance + $bankBalance;

        DB::table('customers')->where('id', $customerId)->update([
            'balance'      => $totalBalance,
            'cash_balance' => $cashBalance,
            'bank_balance' => $bankBalance,
        ]);
    }
}
