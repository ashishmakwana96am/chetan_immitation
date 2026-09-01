<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'purchase_item_id')) {
                $table->unsignedBigInteger('purchase_item_id')->nullable()->after('product_variant_id');
            }
            if (!Schema::hasColumn('order_items', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->nullable()->after('mrp');
            }
        });

        DB::statement("
            UPDATE order_items 
            JOIN products ON products.id = order_items.product_id
            LEFT JOIN product_variants ON product_variants.id = order_items.product_variant_id
            SET order_items.purchase_price = order_items.quantity * COALESCE(product_variants.purchase_price, products.purchase_price, 0)
            WHERE order_items.purchase_price IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'purchase_item_id')) {
                $table->dropColumn('purchase_item_id');
            }
            if (Schema::hasColumn('order_items', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
        });
    }
};
