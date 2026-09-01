<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('utility_reports')
            ->where('description', 'LIKE', '%Balance deducted for Purchase #PS-118%')
            ->update([
                'description' => 'Balance deducted for Purchase #PS-118 (₹ 39,000.00)'
            ]);

        DB::table('utility_reports')
            ->where('description', 'LIKE', '%PS-118%39,248%')
            ->orWhere('description', 'LIKE', '%PS-118%34,248%')
            ->update([
                'description' => DB::raw("REPLACE(REPLACE(description, '39,248.00', '39,000.00'), '34,248.00', '39,000.00')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('utility_reports')
            ->where('description', 'LIKE', '%Balance deducted for Purchase #PS-118 (₹ 34,248.00)%')
            ->update([
                'description' => 'Balance deducted for Purchase #PS-118 (₹ 39,248.00)'
            ]);
    }
};
