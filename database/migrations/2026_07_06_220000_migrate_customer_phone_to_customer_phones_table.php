<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    $digits = preg_replace('/\D/', '', (string) $customer->phone);

                    if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                        $digits = substr($digits, 2);
                    } elseif (strlen($digits) > 10) {
                        $digits = substr($digits, -10);
                    }

                    if (strlen($digits) === 10) {
                        DB::table('customer_phones')->insert([
                            'customer_id' => $customer->id,
                            'phone' => $digits,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('name');
        });

        DB::table('customer_phones')
            ->orderBy('id')
            ->get()
            ->groupBy('customer_id')
            ->each(function ($phones, $customerId) {
                DB::table('customers')->where('id', $customerId)->update([
                    'phone' => $phones->first()->phone,
                ]);
            });
    }
};
