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
        if (!Schema::hasTable('purchase_batch_stocks')) {
            Schema::create('purchase_batch_stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('purchase_item_id')->nullable()->index();
                $table->decimal('purchase_price', 15, 2)->default(0.00)->index();
                $table->decimal('quantity', 15, 2)->default(0.00);
                $table->timestamps();

                $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_batch_stocks');
    }
};
