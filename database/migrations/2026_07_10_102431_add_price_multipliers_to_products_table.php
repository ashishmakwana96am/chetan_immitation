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
            $table->decimal('purchase_multiplier', 10, 3)->default(2.5)->after('product_code');
            $table->decimal('sale_multiplier', 10, 3)->default(4.125)->after('purchase_multiplier');
            $table->decimal('mrp_multiplier', 10, 3)->default(4.575)->after('sale_multiplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['purchase_multiplier', 'sale_multiplier', 'mrp_multiplier']);
        });
    }
};
