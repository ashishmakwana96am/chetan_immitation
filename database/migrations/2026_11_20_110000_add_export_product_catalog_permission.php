<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::firstOrCreate([
            'name' => 'export product catalog',
            'guard_name' => 'web',
        ], [
            'module_name' => 'Products',
        ]);

        $roles = Role::whereIn('name', ['Super Admin', 'Admin'])->get();
        foreach ($roles as $role) {
            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    public function down(): void
    {
        $perm = Permission::where('name', 'export product catalog')->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
