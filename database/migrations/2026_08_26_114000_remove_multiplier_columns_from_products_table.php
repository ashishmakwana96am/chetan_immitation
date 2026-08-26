<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_multiplier',
                'sale_multiplier',
                'mrp_multiplier',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_multiplier', 8, 3)->nullable()->after('product_code');
            $table->decimal('sale_multiplier', 8, 3)->nullable()->after('purchase_multiplier');
            $table->decimal('mrp_multiplier', 8, 3)->nullable()->after('sale_multiplier');
        });
    }
};
