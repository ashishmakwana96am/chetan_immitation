<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('name');
            $table->longText('additional_information')->nullable()->after('description');
            $table->longText('product_highlights')->nullable()->after('additional_information');
        });
    }

    public function down(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'additional_information', 'product_highlights']);
        });
    }
};
