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
        Schema::rename('stock_transfers', 'purchase_bills');
        Schema::rename('stock_transfer_items', 'purchase_bill_items');

        DB::statement('ALTER TABLE purchase_bill_items DROP FOREIGN KEY stock_transfer_items_stock_transfer_id_foreign');
        DB::statement('ALTER TABLE purchase_bill_items CHANGE stock_transfer_id purchase_bill_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE purchase_bill_items ADD CONSTRAINT purchase_bill_items_purchase_bill_id_foreign FOREIGN KEY (purchase_bill_id) REFERENCES purchase_bills (id) ON DELETE CASCADE');

        $permissionMap = [
            'view stock transfers' => 'view purchase bills',
            'create stock transfers' => 'create purchase bills',
            'accept stock transfers' => 'accept purchase bills',
            'reject stock transfers' => 'reject purchase bills',
        ];
        foreach ($permissionMap as $old => $new) {
            DB::table('permissions')->where('name', $old)->update(['name' => $new]);
        }

        DB::table('modules')->where('route', 'admin.stock-transfers.index')->update([
            'name' => 'Purchase Bill',
            'route' => 'admin.purchase-bills.index',
            'active_pattern' => 'admin/purchase-bills*',
            'permission' => 'view purchase bills',
        ]);
        DB::table('modules')->where('active_pattern', 'like', '%admin/stock-transfers*%')->update([
            'active_pattern' => DB::raw("REPLACE(active_pattern, 'admin/stock-transfers*', 'admin/purchase-bills*')"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionMap = [
            'view purchase bills' => 'view stock transfers',
            'create purchase bills' => 'create stock transfers',
            'accept purchase bills' => 'accept stock transfers',
            'reject purchase bills' => 'reject stock transfers',
        ];
        foreach ($permissionMap as $old => $new) {
            DB::table('permissions')->where('name', $old)->update(['name' => $new]);
        }

        DB::table('modules')->where('route', 'admin.purchase-bills.index')->update([
            'name' => 'Stock Transfers',
            'route' => 'admin.stock-transfers.index',
            'active_pattern' => 'admin/stock-transfers*',
            'permission' => 'view stock transfers',
        ]);
        DB::table('modules')->where('active_pattern', 'like', '%admin/purchase-bills*%')->update([
            'active_pattern' => DB::raw("REPLACE(active_pattern, 'admin/purchase-bills*', 'admin/stock-transfers*')"),
        ]);

        DB::statement('ALTER TABLE purchase_bill_items DROP FOREIGN KEY purchase_bill_items_purchase_bill_id_foreign');
        DB::statement('ALTER TABLE purchase_bill_items CHANGE purchase_bill_id stock_transfer_id BIGINT UNSIGNED NOT NULL');

        Schema::rename('purchase_bill_items', 'stock_transfer_items');
        Schema::rename('purchase_bills', 'stock_transfers');

        DB::statement('ALTER TABLE stock_transfer_items ADD CONSTRAINT stock_transfer_items_stock_transfer_id_foreign FOREIGN KEY (stock_transfer_id) REFERENCES stock_transfers (id) ON DELETE CASCADE');
    }
};
