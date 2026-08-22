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
        if (!Schema::hasColumn('purchase_payments', 'is_advance')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->boolean('is_advance')->default(false)->after('bulk_purchase_payment_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_payments', 'is_advance')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->dropColumn('is_advance');
            });
        }
    }
};
