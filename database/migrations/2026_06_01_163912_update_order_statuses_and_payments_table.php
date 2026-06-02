<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remap existing order payment_status values:
        // - non_paid -> pending
        DB::table('orders')->where('payment_status', 'non_paid')->update(['payment_status' => 'pending']);

        // 2. Change column default value to 'pending'
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->change();
        });

        // 3. Remap existing order status values:
        // - pending -> pending
        // - completed -> approve
        // - paid -> approve
        // - cancelled -> decline
        DB::table('orders')->where('status', 'completed')->update(['status' => 'approve']);
        DB::table('orders')->where('status', 'paid')->update(['status' => 'approve']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'decline']);
    }

    public function down(): void
    {
        // 1. Change column default value back to 'non_paid'
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('non_paid')->change();
        });

        // 2. Reverse mappings
        DB::table('orders')->where('status', 'approve')->update(['status' => 'completed']);
        DB::table('orders')->where('status', 'decline')->update(['status' => 'cancelled']);
        DB::table('orders')->where('payment_status', 'pending')->update(['payment_status' => 'non_paid']);
    }
};
