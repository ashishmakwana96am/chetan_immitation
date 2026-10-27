<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'delete purchase bills',
            'guard_name' => 'web',
        ]);
        $permission->update(['module' => 'Purchase Bill']);

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }

    public function down(): void
    {
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->revokePermissionTo('delete purchase bills');
        }

        Permission::where('name', 'delete purchase bills')->delete();
    }
};
