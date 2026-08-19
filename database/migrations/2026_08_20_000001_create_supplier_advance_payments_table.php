<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_advance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('bulk_purchase_payment_id')->nullable()->constrained('bulk_purchase_payments')->onDelete('set null');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('used_amount', 15, 2)->default(0.00);
            $table->decimal('remaining_amount', 15, 2)->default(0.00);
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_advance_payments');
    }
};
