<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('pair_product')->default(0)->after('sale');
            $table->decimal('pair_sale_price', 10, 2)->nullable()->after('pair_product');
            $table->decimal('pair_mrp', 10, 2)->nullable()->after('pair_sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pair_product', 'pair_sale_price', 'pair_mrp']);
        });
    }
};
