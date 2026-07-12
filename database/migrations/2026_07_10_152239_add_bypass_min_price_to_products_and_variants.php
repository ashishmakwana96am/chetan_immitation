<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('bypass_min_price')->default(0)->after('pair_product');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('bypass_min_price')->default(0)->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('bypass_min_price');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('bypass_min_price');
        });
    }
};
