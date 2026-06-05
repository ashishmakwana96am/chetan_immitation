<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('product_information', 'additional_information');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('additional_information', 'product_information');
        });
    }
};
