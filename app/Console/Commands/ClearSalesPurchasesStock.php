<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Location;

class ClearSalesPurchasesStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:clear-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all sales, purchases, stock, and location balance transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('Are you sure you want to clear all sales, purchases, stock, and location balances? This action cannot be undone!')) {
            $this->info('Action cancelled.');
            return;
        }

        $this->info('Starting data cleanup...');

        Schema::disableForeignKeyConstraints();

        $tables = [
            'order_items',
            'order_payments',
            'order_cancellation_requests',
            'orders',
            'purchase_items',
            'purchase_payments',
            'purchase_allocations',
            'purchases',
            'purchase_bill_items',
            'purchase_bills',
            'inventories',
            'location_balance_transactions',
            'customer_balance_transactions',
            'cart_items',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->info("Truncating table: {$table}");
                DB::table($table)->truncate();
            }
        }

        $this->info('Resetting balances to 0...');
        if (Schema::hasTable('location_balances')) {
            \App\Models\LocationBalance::query()->update([
                'cash_balance' => 0,
                'bank_balance' => 0,
            ]);
        }
        if (Schema::hasTable('customer_balances')) {
            \App\Models\CustomerBalance::query()->update([
                'balance' => 0,
                'cash_balance' => 0,
                'bank_balance' => 0,
            ]);
        }

        Schema::enableForeignKeyConstraints();

        $this->info('All sales, purchases, stock, and location balances have been successfully cleared!');
    }
}
