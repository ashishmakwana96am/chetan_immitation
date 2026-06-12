<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Spatie Permissions for Coupons
        $permissions = [
            'view coupons',
            'create coupons',
            'edit coupons',
            'delete coupons',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $permission->update(['module' => 'Coupons']);
        }

        // 2. Assign to super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        // 3. Add Coupons to sidebar menu (under Sales & Customers)
        $parent = Module::where('name', 'Sales & Customers')->first();
        if ($parent) {
            Module::create([
                'parent_id'      => $parent->id,
                'name'           => 'Coupons',
                'icon'           => 'ti ti-ticket',
                'route'          => 'admin.coupons.index',
                'active_pattern' => 'admin/coupons*',
                'permission'     => 'view coupons',
                'sort_order'     => 3,
            ]);
        }
    }

    public function down(): void
    {
        // 1. Remove Coupons from sidebar menu
        Module::where('name', 'Coupons')->delete();

        // 2. Remove Permissions
        $permissions = [
            'view coupons',
            'create coupons',
            'edit coupons',
            'delete coupons',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->roles()->detach();
                $permission->delete();
            }
        }

        // 3. Re-sync super admin permissions
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }
};
