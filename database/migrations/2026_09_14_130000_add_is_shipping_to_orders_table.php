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
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'is_shipping')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('is_shipping')->default(false)->after('tax_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'is_shipping')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('is_shipping');
            });
        }
    }
};
