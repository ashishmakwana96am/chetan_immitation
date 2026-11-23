<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_call_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_no', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('booking_date');
            $table->time('time_slot_start');
            $table->time('time_slot_end');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->string('google_event_id', 255)->nullable();
            $table->string('google_meet_link', 500)->nullable();
            $table->timestamps();
            $table->index(['booking_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_call_bookings');
    }
};
