<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_bill_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_bill_payments', 'stock_return_bill_id')) {
                $table->unsignedBigInteger('stock_return_bill_id')->nullable()->after('branch_balance_transfer_id');
                $table->foreign('stock_return_bill_id')->references('id')->on('purchase_bills')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_bill_payments', 'notes')) {
                $table->string('notes', 255)->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bill_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_bill_payments', 'stock_return_bill_id')) {
                $table->dropForeign(['stock_return_bill_id']);
                $table->dropColumn('stock_return_bill_id');
            }
            if (Schema::hasColumn('purchase_bill_payments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
