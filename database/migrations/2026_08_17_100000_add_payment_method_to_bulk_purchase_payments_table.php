<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bulk_purchase_payments', 'payment_method')) {
            Schema::table('bulk_purchase_payments', function (Blueprint $table) {
                $table->string('payment_method')->nullable()->after('location_id');
            });
        }

        if (!Schema::hasColumn('bulk_purchase_payments', 'supplier_id')) {
            Schema::table('bulk_purchase_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('location_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bulk_purchase_payments', 'payment_method')) {
            Schema::table('bulk_purchase_payments', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }

        if (Schema::hasColumn('bulk_purchase_payments', 'supplier_id')) {
            Schema::table('bulk_purchase_payments', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }
    }
};
