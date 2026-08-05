<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $editPerm = Permission::firstOrCreate([
            'name'       => 'edit customer balance',
            'guard_name' => 'web',
        ]);
        $editPerm->update(['module' => 'Reports']);

        $deletePerm = Permission::firstOrCreate([
            'name'       => 'delete customer balance',
            'guard_name' => 'web',
        ]);
        $deletePerm->update(['module' => 'Reports']);

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        $rolesWithManage = Role::whereHas('permissions', function ($q) {
            $q->where('name', 'manage customer balance');
        })->get();

        foreach ($rolesWithManage as $role) {
            $role->givePermissionTo(['edit customer balance', 'delete customer balance']);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['edit customer balance', 'delete customer balance'])->delete();

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }
};
