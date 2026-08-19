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
        $permissions = [
            'view balance transfer',
            'create balance transfer',
            'edit balance transfer',
            'delete balance transfer',
            'accept balance transfer',
            'reject balance transfer',
        ];

        foreach ($permissions as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);
            \Illuminate\Support\Facades\DB::table('permissions')
                ->where('name', $permName)
                ->update(['module' => 'Balance Transfer']);
        }

        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        \Spatie\Permission\Models\Permission::whereIn('name', [
            'view balance transfer',
            'create balance transfer',
            'edit balance transfer',
            'delete balance transfer',
            'accept balance transfer',
            'reject balance transfer',
        ])->delete();
    }
};
