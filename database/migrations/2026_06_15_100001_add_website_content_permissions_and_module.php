<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view website content' => 'Website Content',
            'edit website content' => 'Website Content',
        ];

        foreach ($permissions as $name => $module) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $permission->update(['module' => $module]);
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        $parent = Module::create([
            'name'           => 'Website Content',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/website-content*',
            'permission'     => null,
            'sort_order'     => 8,
        ]);

        Module::create([
            'parent_id'      => $parent->id,
            'name'           => 'Manage Content',
            'icon'           => 'ti ti-file-description',
            'route'          => 'admin.website-content.index',
            'active_pattern' => 'admin/website-content*',
            'permission'     => 'view website content',
            'sort_order'     => 1,
        ]);
    }

    public function down(): void
    {
        Module::where('name', 'Manage Content')->delete();
        Module::where('name', 'Website Content')->delete();

        $permissions = ['view website content', 'edit website content'];
        foreach ($permissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->roles()->detach();
                $permission->delete();
            }
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }
};
