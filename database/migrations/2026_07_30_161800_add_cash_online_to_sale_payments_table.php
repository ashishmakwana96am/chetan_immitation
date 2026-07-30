<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_payments')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_payments', 'cash_amount')) {
                    $table->decimal('cash_amount', 12, 2)->default(0)->after('amount');
                }
                if (!Schema::hasColumn('sale_payments', 'online_amount')) {
                    $table->decimal('online_amount', 12, 2)->default(0)->after('cash_amount');
                }
                if (!Schema::hasColumn('sale_payments', 'payment_method')) {
                    $table->string('payment_method')->nullable()->after('online_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_payments')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                $table->dropColumn(['cash_amount', 'online_amount', 'payment_method']);
            });
        }
    }
};
