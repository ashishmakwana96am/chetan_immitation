<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Http\Controllers\DashboardController;

class ClearLocationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'location:clear-data {location_id? : The ID or Name of the location to clear} {--force : Force the operation without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard deletes all stock inventory, transfer bills, sales orders, payments, and resets location balances for a specific branch location.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locationInput = $this->argument('location_id');

        if (!$locationInput) {
            $locations = Location::orderBy('id')->get(['id', 'name']);
            if ($locations->isEmpty()) {
                $this->error('No locations found in database.');
                return 1;
            }

            $choices = $locations->mapWithKeys(fn($l) => [$l->id => "[ID: {$l->id}] {$l->name}"])->toArray();
            $selectedId = $this->choice('Select the location to clear data for:', $choices);
            $locationId = (int) preg_replace('/[^0-9]/', '', strtok($selectedId, ']'));
        } else if (is_numeric($locationInput)) {
            $locationId = (int) $locationInput;
        } else {
            $loc = Location::where('name', 'like', "%{$locationInput}%")->first();
            if (!$loc) {
                $this->error("Location not found matching '{$locationInput}'.");
                return 1;
            }
            $locationId = $loc->id;
        }

        $location = Location::find($locationId);
        if (!$location) {
            $this->error("Location ID {$locationId} not found.");
            return 1;
        }

        if (!$this->option('force')) {
            $confirm = $this->confirm("DANGER: Are you sure you want to HARD DELETE all stock, sales orders, transfers, and balances for location '{$location->name}' (ID: {$location->id})? This cannot be undone!", false);
            if (!$confirm) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info("Starting data wipe for location: {$location->name} (ID: {$location->id})...");

        Schema::disableForeignKeyConstraints();

        // 1. Delete Sales Orders & Items & Payments for this location
        $orderIds = DB::table('orders')->where('location_id', $locationId)->pluck('id')->toArray();
        if (!empty($orderIds)) {
            $itemCount = DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            $paymentCount = DB::table('sale_payments')->whereIn('order_id', $orderIds)->delete();
            $orderCount = DB::table('orders')->whereIn('id', $orderIds)->delete();
            $this->line(" - Deleted {$orderCount} orders, {$itemCount} order items, {$paymentCount} sale payments.");
        } else {
            $this->line(" - No orders found for this location.");
        }

        // 2. Delete Transfer Bills (to or from this location)
        $billIds = DB::table('purchase_bills')
            ->where('from_location_id', $locationId)
            ->orWhere('to_location_id', $locationId)
            ->pluck('id')
            ->toArray();

        if (!empty($billIds)) {
            $billItemCount = DB::table('purchase_bill_items')->whereIn('purchase_bill_id', $billIds)->delete();
            $billCount = DB::table('purchase_bills')->whereIn('id', $billIds)->delete();
            $this->line(" - Deleted {$billCount} transfer bills and {$billItemCount} transfer bill items.");
        } else {
            $this->line(" - No transfer bills found for this location.");
        }

        // 3. Delete Purchase Allocations for this location
        $allocCount = DB::table('purchase_allocations')->where('location_id', $locationId)->delete();
        $this->line(" - Deleted {$allocCount} purchase allocation records.");

        // 4. Delete / Reset Inventories for this location
        $invCount = DB::table('inventories')->where('location_id', $locationId)->delete();
        $this->line(" - Removed {$invCount} inventory records for this location.");

        // 5. Delete Expenses for this location
        if (Schema::hasTable('expenses')) {
            $expCount = DB::table('expenses')->where('location_id', $locationId)->delete();
            $this->line(" - Deleted {$expCount} expense records.");
        }

        // 6. Delete Location Balance Transactions & Reset Balance
        if (Schema::hasTable('location_balance_transactions')) {
            $txCount = DB::table('location_balance_transactions')->where('location_id', $locationId)->delete();
            $this->line(" - Deleted {$txCount} location balance transactions.");
        }

        if (Schema::hasTable('location_balances')) {
            LocationBalance::where('location_id', $locationId)->update([
                'cash_balance' => 0.00,
                'bank_balance' => 0.00,
            ]);
            $this->line(" - Reset location cash and bank balances to ₹0.00.");
        }

        Schema::enableForeignKeyConstraints();

        // 7. Invalidate caches
        DashboardController::clearDashboardCaches();

        $this->info("SUCCESS: All stock, sales, transfers, and balances for location '{$location->name}' have been completely wiped.");
        return 0;
    }
}
