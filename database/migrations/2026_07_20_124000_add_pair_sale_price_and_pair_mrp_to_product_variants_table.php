<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'pair_sale_price')) {
                $table->decimal('pair_sale_price', 10, 2)->nullable()->after('sale_price');
            }
            if (!Schema::hasColumn('product_variants', 'pair_mrp')) {
                $table->decimal('pair_mrp', 10, 2)->nullable()->after('pair_sale_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['pair_sale_price', 'pair_mrp']);
        });
    }
};
