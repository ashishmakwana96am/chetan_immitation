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
        Schema::rename('purchase_invoices', 'purchases');

        DB::statement('ALTER TABLE purchase_items DROP FOREIGN KEY purchase_items_purchase_invoice_id_foreign');
        DB::statement('ALTER TABLE purchase_items CHANGE purchase_invoice_id purchase_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES purchases (id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE purchase_items DROP FOREIGN KEY purchase_items_purchase_id_foreign');
        DB::statement('ALTER TABLE purchase_items CHANGE purchase_id purchase_invoice_id BIGINT UNSIGNED NOT NULL');

        Schema::rename('purchases', 'purchase_invoices');

        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_purchase_invoice_id_foreign FOREIGN KEY (purchase_invoice_id) REFERENCES purchase_invoices (id) ON DELETE CASCADE');
    }
};
