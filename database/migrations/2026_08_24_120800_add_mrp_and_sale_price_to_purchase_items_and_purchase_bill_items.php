<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_bill_items', 'purchase_price')) {
                $table->decimal('purchase_price', 10, 2)->default(0)->after('custom_size_value');
            }
            if (!Schema::hasColumn('purchase_bill_items', 'mrp')) {
                $table->decimal('mrp', 10, 2)->nullable()->after('purchase_price');
            }
        });

        // Backfill existing purchase_bill_items with price & mrp from variant or product table
        $items = DB::table('purchase_bill_items')
            ->where(function ($q) {
                $q->whereNull('purchase_price')
                  ->orWhere('purchase_price', 0)
                  ->orWhereNull('mrp');
            })
            ->select('id', 'product_id', 'product_variant_id')
            ->get();

        foreach ($items as $item) {
            $purchasePrice = 0.00;
            $mrp = null;

            if (!empty($item->product_variant_id)) {
                $variant = DB::table('product_variants')->where('id', $item->product_variant_id)->first();
                if ($variant) {
                    $purchasePrice = isset($variant->purchase_price) ? (float) $variant->purchase_price : 0.00;
                    $mrp = (isset($variant->mrp) && $variant->mrp !== null) ? (float) $variant->mrp : null;
                }
            }

            if (($purchasePrice <= 0 || $mrp === null) && !empty($item->product_id)) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if ($product) {
                    if ($purchasePrice <= 0) {
                        $purchasePrice = isset($product->purchase_price) ? (float) $product->purchase_price : 0.00;
                    }
                    if ($mrp === null) {
                        $mrp = (isset($product->mrp) && $product->mrp !== null) ? (float) $product->mrp : null;
                    }
                }
            }

            DB::table('purchase_bill_items')
                ->where('id', $item->id)
                ->update([
                    'purchase_price' => $purchasePrice,
                    'mrp'            => $mrp,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_bill_items', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
            if (Schema::hasColumn('purchase_bill_items', 'mrp')) {
                $table->dropColumn('mrp');
            }
        });
    }
};
