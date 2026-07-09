<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs') && !Schema::hasTable('utility_reports')) {
            Schema::rename('activity_logs', 'utility_reports');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('utility_reports') && !Schema::hasTable('activity_logs')) {
            Schema::rename('utility_reports', 'activity_logs');
        }
    }
};
