<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'additional_information')) {
                $table->text('additional_information')->nullable()->change();
            }

            if (Schema::hasColumn('products', 'product_highlights')) {
                $table->text('product_highlights')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // These fields are optional by design. Leave them nullable on rollback to
        // match the original product information migrations and avoid data loss.
    }
};
