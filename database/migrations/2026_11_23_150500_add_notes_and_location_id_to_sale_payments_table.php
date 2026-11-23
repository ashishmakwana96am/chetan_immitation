<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_payments', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('order_id')->constrained('locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('sale_payments', 'notes')) {
                $table->text('notes')->nullable()->after('payment_method');
            }
        });

        DB::statement("UPDATE sale_payments sp JOIN orders o ON o.id = sp.order_id SET sp.location_id = o.location_id WHERE sp.location_id IS NULL");
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sale_payments', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
            if (Schema::hasColumn('sale_payments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
