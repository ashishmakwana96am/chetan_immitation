<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $activeInactiveTables = [
            'users', 'locations', 'categories', 'sub_categories', 'products', 
            'product_variants', 'suppliers', 'customers', 'coupons', 'attributes'
        ];

        foreach ($activeInactiveTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Active, 2 = Inactive'");
            }
        }

        if (Schema::hasTable('purchase_invoices')) {
            if (Schema::hasColumn('purchase_invoices', 'status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Pending, 2 = Approve, 3 = Decline'");
            }
            if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Pending, 2 = Paid'");
            }
        }

        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Pending, 2 = Approve, 3 = Decline'");
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1 COMMENT '1 = Pending, 2 = Paid'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $activeInactiveTables = [
            'users', 'locations', 'categories', 'sub_categories', 'products', 
            'product_variants', 'suppliers', 'customers', 'coupons', 'attributes'
        ];

        foreach ($activeInactiveTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''");
            }
        }

        if (Schema::hasTable('purchase_invoices')) {
            if (Schema::hasColumn('purchase_invoices', 'status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''");
            }
            if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
                DB::statement("ALTER TABLE `purchase_invoices` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1 COMMENT ''");
            }
        }

        if (Schema::hasTable('orders')) {
            if (Schema::hasColumn('orders', 'status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''");
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` TINYINT NOT NULL DEFAULT 1 COMMENT ''");
            }
        }
    }
};
