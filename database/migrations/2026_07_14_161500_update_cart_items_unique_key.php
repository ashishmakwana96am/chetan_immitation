<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Add new unique key including pair_type first
            $table->unique(['customer_id', 'product_id', 'product_variant_id', 'pair_type'], 'cart_items_customer_product_variant_pair_unique');
            
            // Drop old unique key
            $table->dropUnique('cart_items_customer_id_product_id_product_variant_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['customer_id', 'product_id', 'product_variant_id'], 'cart_items_customer_id_product_id_product_variant_id_unique');
            $table->dropUnique('cart_items_customer_product_variant_pair_unique');
        });
    }
};
