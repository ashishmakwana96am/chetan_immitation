<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'cash_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('cash_balance', 12, 2)->default(0)->after('balance');
                $table->decimal('bank_balance', 12, 2)->default(0)->after('cash_balance');
            });
        }

        // Backfill existing cash_balance and bank_balance for all customers
        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            $cashCredit = (float) DB::table('customer_balance_transactions')
                ->where('customer_id', $customer->id)
                ->where('source', 'cash')
                ->where('type', 'credit')
                ->sum('amount');

            $cashDebit = (float) DB::table('customer_balance_transactions')
                ->where('customer_id', $customer->id)
                ->where('source', 'cash')
                ->where('type', 'debit')
                ->sum('amount');

            $bankCredit = (float) DB::table('customer_balance_transactions')
                ->where('customer_id', $customer->id)
                ->where('source', 'bank')
                ->where('type', 'credit')
                ->sum('amount');

            $bankDebit = (float) DB::table('customer_balance_transactions')
                ->where('customer_id', $customer->id)
                ->where('source', 'bank')
                ->where('type', 'debit')
                ->sum('amount');

            $cashBalance = $cashCredit - $cashDebit;
            $bankBalance = $bankCredit - $bankDebit;

            DB::table('customers')->where('id', $customer->id)->update([
                'cash_balance' => $cashBalance,
                'bank_balance' => $bankBalance,
                'balance'      => $cashBalance + $bankBalance,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cash_balance', 'bank_balance']);
        });
    }
};
