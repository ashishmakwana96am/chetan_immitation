<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_bill_id')->constrained('purchase_bills')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0)->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_payments');

        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
