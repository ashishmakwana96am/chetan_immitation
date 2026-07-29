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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('paid_cash_amount', 10, 2)->default(0)->after('payment_method');
            $table->decimal('paid_online_amount', 10, 2)->default(0)->after('paid_cash_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_cash_amount', 'paid_online_amount']);
        });
    }
};
