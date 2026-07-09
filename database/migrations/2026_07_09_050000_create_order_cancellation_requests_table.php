<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->text('cancellation_reason');
            $table->string('status')->default('pending'); // pending / approved / rejected
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_gateway_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cancellation_requests');
    }
};
