<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('collection_product')) {
            Schema::create('collection_product', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('collection_id')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasColumn('products', 'collection_id')) {
            $existing = DB::table('products')
                ->whereNotNull('collection_id')
                ->select('id as product_id', 'collection_id')
                ->get();

            foreach ($existing as $row) {
                DB::table('collection_product')->insertOrIgnore([
                    'product_id'    => $row->product_id,
                    'collection_id' => $row->collection_id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
    }
};
