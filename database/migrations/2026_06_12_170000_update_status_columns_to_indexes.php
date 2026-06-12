<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Map existing string statuses to temporary integer string representations
        $activeInactiveTables = [
            'users', 'locations', 'categories', 'sub_categories', 'products', 
            'product_variants', 'suppliers', 'customers', 'coupons', 'attributes'
        ];

        foreach ($activeInactiveTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::table($table)->where('status', 'active')->update(['status' => '1']);
                DB::table($table)->where('status', 'inactive')->update(['status' => '2']);
            }
        }

        if (Schema::hasTable('purchase_invoices')) {
            if (Schema::hasColumn('purchase_invoices', 'status')) {
                DB::table('purchase_invoices')->where('status', 'pending')->update(['status' => '1']);
                DB::table('purchase_invoices')->where('status', 'approve')->update(['status' => '2']);
                DB::table('purchase_invoices')->where('status', 'decline')->update(['status' => '3']);
            }
            if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
                DB::table('purchase_invoices')->where('payment_status', 'pending')->update(['payment_status' => '1']);
                DB::table('purchase_invoices')->where('payment_status', 'paid')->update(['payment_status' => '2']);
            }
        }

        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'status')) {
                DB::table('orders')->where('status', 'pending')->update(['status' => '1']);
                DB::table('orders')->where('status', 'approve')->update(['status' => '2']);
                DB::table('orders')->where('status', 'decline')->update(['status' => '3']);
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                DB::table('orders')->where('payment_status', 'pending')->update(['payment_status' => '1']);
                DB::table('orders')->where('payment_status', 'paid')->update(['payment_status' => '2']);
            }
        }

        // 2. Change columns to TINYINT
        foreach ($activeInactiveTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1");
            }
        }

        if (Schema::hasTable('purchase_invoices')) {
            if (Schema::hasColumn('purchase_invoices', 'status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1");
            }
            if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1");
            }
        }

        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1");
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change columns back to string/enum
        $activeInactiveTables = [
            'users', 'locations', 'categories', 'sub_categories', 'products', 
            'product_variants', 'suppliers', 'customers', 'coupons', 'attributes'
        ];

        foreach ($activeInactiveTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                // Change back to string first
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` VARCHAR(255) NOT NULL DEFAULT 'active'");
                DB::table($table)->where('status', '1')->update(['status' => 'active']);
                DB::table($table)->where('status', '2')->update(['status' => 'inactive']);
                // Convert back to enum definition
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
            }
        }

        if (Schema::hasTable('purchase_invoices')) {
            if (Schema::hasColumn('purchase_invoices', 'status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
                DB::table('purchase_invoices')->where('status', '1')->update(['status' => 'pending']);
                DB::table('purchase_invoices')->where('status', '2')->update(['status' => 'approve']);
                DB::table('purchase_invoices')->where('status', '3')->update(['status' => 'decline']);
            }
            if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `payment_status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
                DB::table('purchase_invoices')->where('payment_status', '1')->update(['payment_status' => 'pending']);
                DB::table('purchase_invoices')->where('payment_status', '2')->update(['payment_status' => 'paid']);
            }
        }

        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
                DB::table('orders')->where('status', '1')->update(['status' => 'pending']);
                DB::table('orders')->where('status', '2')->update(['status' => 'approve']);
                DB::table('orders')->where('status', '3')->update(['status' => 'decline']);
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
                DB::table('orders')->where('payment_status', '1')->update(['payment_status' => 'pending']);
                DB::table('orders')->where('payment_status', '2')->update(['payment_status' => 'paid']);
            }
        }
    }
};
