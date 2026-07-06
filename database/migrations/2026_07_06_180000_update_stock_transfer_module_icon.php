<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')
            ->where('route', 'admin.stock-transfers.index')
            ->update(['icon' => 'ti ti-transfer-out']);
    }

    public function down(): void
    {
        DB::table('modules')
            ->where('route', 'admin.stock-transfers.index')
            ->update(['icon' => 'ti ti-arrows-transfer-down']);
    }
};
