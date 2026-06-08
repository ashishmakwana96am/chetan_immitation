<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'download purchases', 'module' => 'Purchases'],
            ['name' => 'download sales', 'module' => 'Sales'],
        ];

        foreach ($permissions as $data) {
            $permission = Permission::firstOrCreate([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);
            $permission->update(['module' => $data['module']]);
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }

    public function down(): void
    {
        $permissionNames = ['download purchases', 'download sales'];

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->revokePermissionTo($permissionNames);
        }

        Permission::whereIn('name', $permissionNames)->delete();
    }
};
