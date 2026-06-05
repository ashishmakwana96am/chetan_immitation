<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('created_by');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('created_by');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('created_by');
        });

        // Set initial sort_order based on existing id order
        DB::statement('UPDATE categories SET sort_order = id');
        DB::statement('UPDATE sub_categories SET sort_order = id');
        DB::statement('UPDATE products SET sort_order = id');
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
