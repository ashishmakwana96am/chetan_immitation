<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\LocationBalanceTransaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $targetIds = [385, 386, 383, 384, 304, 305];

        if (Schema::hasTable('location_balance_transactions')) {
            DB::table('location_balance_transactions')
                ->whereIn('id', $targetIds)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('branch_balance_transfers')) {
            DB::table('branch_balance_transfers')
                ->whereIn('amount', [1000.00, 5000.00])
                ->whereIn(DB::raw('DATE(created_at)'), ['2026-08-08', '2026-08-14'])
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        if (class_exists(LocationBalanceTransaction::class)) {
            LocationBalanceTransaction::syncLocationBalance(1, LocationBalanceTransaction::BALANCE_TYPE_CASH);
            LocationBalanceTransaction::syncLocationBalance(2, LocationBalanceTransaction::BALANCE_TYPE_CASH);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $targetIds = [385, 386, 383, 384, 304, 305];

        if (Schema::hasTable('location_balance_transactions')) {
            DB::table('location_balance_transactions')
                ->whereIn('id', $targetIds)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('branch_balance_transfers')) {
            DB::table('branch_balance_transfers')
                ->whereIn('amount', [1000.00, 5000.00])
                ->whereIn(DB::raw('DATE(created_at)'), ['2026-08-08', '2026-08-14'])
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
        }

        if (class_exists(LocationBalanceTransaction::class)) {
            LocationBalanceTransaction::syncLocationBalance(1, LocationBalanceTransaction::BALANCE_TYPE_CASH);
            LocationBalanceTransaction::syncLocationBalance(2, LocationBalanceTransaction::BALANCE_TYPE_CASH);
        }
    }
};
