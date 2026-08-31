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
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'mrp')) {
                $table->decimal('mrp', 12, 2)->nullable()->after('purchase_price');
            }
        });

        // Populate existing purchase_items.mrp from products table
        DB::statement("
            UPDATE purchase_items 
            JOIN products ON products.id = purchase_items.product_id
            SET purchase_items.mrp = COALESCE(NULLIF(products.mrp, 0), products.sale_price, 0)
            WHERE purchase_items.mrp IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'mrp')) {
                $table->dropColumn('mrp');
            }
        });
    }
};
