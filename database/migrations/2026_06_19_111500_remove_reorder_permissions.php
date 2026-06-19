<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['reorder categories', 'reorder sub categories', 'reorder products'];
        $superAdminRole = Role::where('name', 'super-admin')->first();
        foreach ($permissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                if ($superAdminRole) {
                    $superAdminRole->revokePermissionTo($permission);
                }
                $permission->delete();
            }
        }
        
        // Reset permission cache
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = [
            'reorder categories'     => 'Categories',
            'reorder sub categories' => 'Sub Categories',
            'reorder products'       => 'Products',
        ];
        $superAdminRole = Role::where('name', 'super-admin')->first();
        foreach ($permissions as $name => $module) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $permission->update(['module' => $module]);
            if ($superAdminRole) {
                $superAdminRole->givePermissionTo($permission);
            }
        }
        
        // Reset permission cache
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
