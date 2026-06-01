<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add payment_status column to purchase_invoices table if it doesn't exist
        if (!Schema::hasColumn('purchase_invoices', 'payment_status')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->string('payment_status')->default('pending')->after('status');
            });
        }

        // 2. Map existing statuses:
        // - draft -> pending
        // - confirmed -> approve
        // - cancelled -> decline
        DB::table('purchase_invoices')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('purchase_invoices')->where('status', 'confirmed')->update(['status' => 'approve']);
        DB::table('purchase_invoices')->where('status', 'cancelled')->update(['status' => 'decline']);
    }

    public function down(): void
    {
        // Reverse mappings
        DB::table('purchase_invoices')->where('status', 'pending')->update(['status' => 'draft']);
        DB::table('purchase_invoices')->where('status', 'approve')->update(['status' => 'confirmed']);
        DB::table('purchase_invoices')->where('status', 'decline')->update(['status' => 'cancelled']);

        // Remove column
        if (Schema::hasColumn('purchase_invoices', 'payment_status')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }
    }
};
