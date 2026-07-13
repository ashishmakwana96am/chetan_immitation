<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('locations', 'cash_balance')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->decimal('cash_balance', 12, 2)->default(0)->after('gst_number');
            });
        }

        if (!Schema::hasColumn('locations', 'bank_balance')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->decimal('bank_balance', 12, 2)->default(0)->after('cash_balance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('locations', 'bank_balance')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('bank_balance');
            });
        }

        if (Schema::hasColumn('locations', 'cash_balance')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('cash_balance');
            });
        }
    }
};
