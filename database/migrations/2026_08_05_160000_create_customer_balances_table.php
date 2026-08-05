<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_balances')) {
            Schema::create('customer_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->decimal('cash_balance', 12, 2)->default(0.00);
                $table->decimal('bank_balance', 12, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // Backfill customer balances from customers table
        $hasBalanceCol = Schema::hasColumn('customers', 'balance');
        $customers = DB::table('customers')->get();

        foreach ($customers as $customer) {
            $exists = DB::table('customer_balances')->where('customer_id', $customer->id)->exists();
            if (!$exists) {
                DB::table('customer_balances')->insert([
                    'customer_id'  => $customer->id,
                    'balance'      => $hasBalanceCol ? ($customer->balance ?? 0.00) : 0.00,
                    'cash_balance' => $hasBalanceCol ? ($customer->cash_balance ?? 0.00) : 0.00,
                    'bank_balance' => $hasBalanceCol ? ($customer->bank_balance ?? 0.00) : 0.00,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // Drop balance columns from customers table if they exist
        Schema::table('customers', function (Blueprint $table) {
            $colsToDrop = [];
            if (Schema::hasColumn('customers', 'balance')) {
                $colsToDrop[] = 'balance';
            }
            if (Schema::hasColumn('customers', 'cash_balance')) {
                $colsToDrop[] = 'cash_balance';
            }
            if (Schema::hasColumn('customers', 'bank_balance')) {
                $colsToDrop[] = 'bank_balance';
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'balance')) {
                $table->decimal('balance', 12, 2)->default(0.00)->after('is_credit_customer');
            }
            if (!Schema::hasColumn('customers', 'cash_balance')) {
                $table->decimal('cash_balance', 12, 2)->default(0.00)->after('balance');
            }
            if (!Schema::hasColumn('customers', 'bank_balance')) {
                $table->decimal('bank_balance', 12, 2)->default(0.00)->after('cash_balance');
            }
        });

        if (Schema::hasTable('customer_balances')) {
            $balances = DB::table('customer_balances')->get();
            foreach ($balances as $bal) {
                DB::table('customers')->where('id', $bal->customer_id)->update([
                    'balance'      => $bal->balance,
                    'cash_balance' => $bal->cash_balance,
                    'bank_balance' => $bal->bank_balance,
                ]);
            }

            Schema::dropIfExists('customer_balances');
        }
    }
};
