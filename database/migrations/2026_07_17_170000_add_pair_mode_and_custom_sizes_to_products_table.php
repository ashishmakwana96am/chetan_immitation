<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('pair_mode')->nullable()->after('pair_mrp');
            $table->json('custom_sizes')->nullable()->after('pair_mode');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pair_mode', 'custom_sizes']);
        });
    }
};
