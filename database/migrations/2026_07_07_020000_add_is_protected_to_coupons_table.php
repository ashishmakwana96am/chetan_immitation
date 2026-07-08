<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('is_protected')->default(false)->after('status');
        });

        DB::table('coupons')->updateOrInsert(
            ['code' => 'FREESHIP'],
            [
                'name' => 'Free Shipping',
                'description' => 'Get free shipping on this order. Orders of ' . '₹' . '1999 or more already ship free automatically.',
                'discount_type' => 'flat',
                'discount_value' => 0,
                'usage_limit' => null,
                'start_date' => null,
                'end_date' => null,
                'status' => 1,
                'is_protected' => true,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('coupons')->where('code', 'FREESHIP')->delete();

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('is_protected');
        });
    }
};
