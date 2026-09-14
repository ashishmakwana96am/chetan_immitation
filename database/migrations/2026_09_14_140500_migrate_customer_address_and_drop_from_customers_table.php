<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            Schema::table('customer_addresses', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
                $table->string('phone')->nullable()->change();
                $table->string('city')->nullable()->change();
                $table->string('type')->nullable()->default(null)->change();
            });
        }

        if (Schema::hasTable('customers') && Schema::hasTable('customer_addresses')) {
            $hasAddressCol = Schema::hasColumn('customers', 'address');
            $hasStateCol   = Schema::hasColumn('customers', 'state');

            if ($hasAddressCol || $hasStateCol) {
                $customers = DB::table('customers')->get();

                foreach ($customers as $customer) {
                    $addrText = $hasAddressCol ? trim((string) ($customer->address ?? '')) : '';
                    $stateVal = $hasStateCol ? trim((string) ($customer->state ?? '')) : '';

                    if ($addrText === '' && $stateVal === '') {
                        continue;
                    }

                    $existingCount = DB::table('customer_addresses')
                        ->where('customer_id', $customer->id)
                        ->whereNull('deleted_at')
                        ->count();

                    if ($existingCount === 0) {
                        DB::table('customer_addresses')->insert([
                            'customer_id' => $customer->id,
                            'address'     => $addrText,
                            'state'       => $stateVal,
                            'is_default'  => true,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('customers', 'state')) {
                $table->dropColumn('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'state')) {
                $table->string('state', 100)->nullable()->after('gst_no');
            }
            if (!Schema::hasColumn('customers', 'address')) {
                $table->text('address')->nullable()->after('state');
            }
        });

        if (Schema::hasTable('customer_addresses')) {
            $addresses = DB::table('customer_addresses')
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($addresses as $addr) {
                DB::table('customers')
                    ->where('id', $addr->customer_id)
                    ->whereNull('address')
                    ->update([
                        'address' => $addr->address,
                        'state'   => $addr->state,
                    ]);
            }
        }
    }
};
