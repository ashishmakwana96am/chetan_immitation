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
        if (!Schema::hasColumn('locations', 'gst_number')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('gst_number', 20)->nullable()->after('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('locations', 'gst_number')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('gst_number');
            });
        }
    }
};
