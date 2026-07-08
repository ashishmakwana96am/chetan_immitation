<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $states = [
            ['name' => 'Gujarat', 'shipping_charge' => 49.00, 'delivery_days' => 2],
            ['name' => 'Maharashtra', 'shipping_charge' => 69.00, 'delivery_days' => 3],
            ['name' => 'Rajasthan', 'shipping_charge' => 79.00, 'delivery_days' => 4],
            ['name' => 'Madhya Pradesh', 'shipping_charge' => 79.00, 'delivery_days' => 4],
            ['name' => 'Delhi', 'shipping_charge' => 89.00, 'delivery_days' => 4],
            ['name' => 'Karnataka', 'shipping_charge' => 99.00, 'delivery_days' => 5],
            ['name' => 'Tamil Nadu', 'shipping_charge' => 99.00, 'delivery_days' => 5],
            ['name' => 'Uttar Pradesh', 'shipping_charge' => 89.00, 'delivery_days' => 4],
            ['name' => 'West Bengal', 'shipping_charge' => 109.00, 'delivery_days' => 6],
            ['name' => 'Telangana', 'shipping_charge' => 99.00, 'delivery_days' => 5],
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(
                ['name' => $state['name']],
                array_merge($state, [
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('states')->whereIn('name', [
            'Gujarat', 'Maharashtra', 'Rajasthan', 'Madhya Pradesh', 'Delhi',
            'Karnataka', 'Tamil Nadu', 'Uttar Pradesh', 'West Bengal', 'Telangana',
        ])->delete();
    }
};
