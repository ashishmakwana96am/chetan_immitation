<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'mrp')) {
                $table->decimal('mrp', 10, 2)->nullable()->after('custom_size_value');
            }
        });

        // Backfill existing order_items mrp from variants or products
        $items = DB::table('order_items')->whereNull('mrp')->select('id', 'product_id', 'product_variant_id', 'custom_size_value')->get();
        foreach ($items as $item) {
            $mrp = null;
            if (!empty($item->product_variant_id)) {
                $variant = DB::table('product_variants')->where('id', $item->product_variant_id)->first();
                if ($variant && isset($variant->mrp) && $variant->mrp !== null) {
                    $mrp = (float) $variant->mrp;
                }
            }
            if ($mrp === null && !empty($item->product_id)) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if ($product && isset($product->mrp) && $product->mrp !== null) {
                    $mrp = (float) $product->mrp;
                }
            }
            if ($mrp !== null) {
                DB::table('order_items')->where('id', $item->id)->update(['mrp' => $mrp]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'mrp')) {
                $table->dropColumn('mrp');
            }
        });
    }
};
