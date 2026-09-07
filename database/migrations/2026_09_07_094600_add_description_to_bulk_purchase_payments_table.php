<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_purchase_payments')) {
            Schema::table('bulk_purchase_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('bulk_purchase_payments', 'description')) {
                    $table->text('description')->nullable()->after('payment_method');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_purchase_payments')) {
            Schema::table('bulk_purchase_payments', function (Blueprint $table) {
                if (Schema::hasColumn('bulk_purchase_payments', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};
