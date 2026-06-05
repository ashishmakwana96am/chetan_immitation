<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Remove old module permissions that are no longer used
        $removePermissions = ['create modules', 'edit modules', 'delete modules'];
        foreach ($removePermissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->roles()->detach();
                $permission->delete();
            }
        }

        // Add reorder modules permission
        $permission = Permission::firstOrCreate(['name' => 'reorder modules', 'guard_name' => 'web']);
        $permission->update(['module' => 'Modules']);

        // Assign to super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        // Remove reorder modules
        $permission = Permission::where('name', 'reorder modules')->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }

        // Restore old permissions
        $restorePermissions = ['create modules', 'edit modules', 'delete modules'];
        $superAdminRole = Role::where('name', 'super-admin')->first();
        foreach ($restorePermissions as $name) {
            $perm = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $perm->update(['module' => 'Modules']);
            if ($superAdminRole) {
                $superAdminRole->givePermissionTo($perm);
            }
        }
    }
};
