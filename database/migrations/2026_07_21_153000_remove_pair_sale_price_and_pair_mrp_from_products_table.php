<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('products', 'pair_sale_price')) {
                $columnsToDrop[] = 'pair_sale_price';
            }
            if (Schema::hasColumn('products', 'pair_mrp')) {
                $columnsToDrop[] = 'pair_mrp';
            }
            if (Schema::hasColumn('products', 'pair_mode')) {
                $columnsToDrop[] = 'pair_mode';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'pair_sale_price')) {
                $table->decimal('pair_sale_price', 10, 2)->nullable()->after('pair_product');
            }
            if (!Schema::hasColumn('products', 'pair_mrp')) {
                $table->decimal('pair_mrp', 10, 2)->nullable()->after('pair_sale_price');
            }
            if (!Schema::hasColumn('products', 'pair_mode')) {
                $table->string('pair_mode')->nullable()->after('pair_mrp');
            }
        });
    }
};
