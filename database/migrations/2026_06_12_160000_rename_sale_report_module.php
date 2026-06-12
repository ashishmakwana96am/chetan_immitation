<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('modules')
            ->where('name', 'Sale Report')
            ->update(['name' => 'Sales Report']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')
            ->where('name', 'Sales Report')
            ->update(['name' => 'Sale Report']);
    }
};
