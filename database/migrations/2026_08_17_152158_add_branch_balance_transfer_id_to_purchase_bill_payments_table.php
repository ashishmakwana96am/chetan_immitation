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
        if (!Schema::hasColumn('purchase_bill_payments', 'branch_balance_transfer_id')) {
            Schema::table('purchase_bill_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_balance_transfer_id')->nullable()->after('purchase_bill_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_bill_payments', 'branch_balance_transfer_id')) {
            Schema::table('purchase_bill_payments', function (Blueprint $table) {
                $table->dropColumn('branch_balance_transfer_id');
            });
        }
    }
};
