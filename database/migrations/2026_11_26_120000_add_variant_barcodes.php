<?php

use App\Services\ProductBarcodes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode', 265)->nullable()->after('product_id')->unique();
            $table->unsignedInteger('barcode_sequence')->nullable()->after('barcode');
            $table->unique(['product_id', 'barcode_sequence']);
        });

        $connection = DB::connection();
        $connection->transaction(function () use ($connection) {
            $connection->table('products')->select('id')->whereNotNull('barcode')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')->from('product_variants')->whereColumn('product_id', 'products.id');
                })
                ->chunkById(200, function ($products) use ($connection) {
                    foreach ($products as $product) {
                        ProductBarcodes::backfillProduct($connection, $product->id);
                    }
                });
        });

        Cache::store('file')->forget('all_mapped_products_purchases');
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropUnique(['product_id', 'barcode_sequence']);
            $table->dropColumn(['barcode', 'barcode_sequence']);
        });

        Cache::store('file')->forget('all_mapped_products_purchases');
    }
};
