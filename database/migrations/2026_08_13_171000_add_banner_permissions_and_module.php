<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view banners'   => 'Website Content',
            'create banners' => 'Website Content',
            'edit banners'   => 'Website Content',
            'delete banners' => 'Website Content',
        ];

        foreach ($permissions as $name => $module) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $permission->update(['module' => $module]);
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        $websiteCategory = Module::where('name', 'Website Content')->whereNull('parent_id')->first();
        if (!$websiteCategory) {
            $websiteCategory = Module::create([
                'name'           => 'Website Content',
                'icon'           => null,
                'route'          => null,
                'active_pattern' => 'admin/website-content*,admin/contact-inquiries*,admin/banners*',
                'permission'     => null,
                'sort_order'     => 8,
            ]);
        } else {
            $websiteCategory->update([
                'active_pattern' => 'admin/website-content*,admin/contact-inquiries*,admin/banners*',
            ]);
        }

        $heroBannerModule = Module::where('name', 'Hero Banners')->where('parent_id', $websiteCategory->id)->first();
        if (!$heroBannerModule) {
            Module::create([
                'parent_id'      => $websiteCategory->id,
                'name'           => 'Hero Banners',
                'icon'           => 'ti ti-photo',
                'route'          => 'admin.banners.index',
                'active_pattern' => 'admin/banners*',
                'permission'     => 'view banners',
                'sort_order'     => 1,
            ]);
        } else {
            $heroBannerModule->update([
                'icon'           => 'ti ti-photo',
                'route'          => 'admin.banners.index',
                'active_pattern' => 'admin/banners*',
                'permission'     => 'view banners',
                'sort_order'     => 1,
            ]);
        }

        Cache::forget('admin_sidebar_modules');
    }

    public function down(): void
    {
        Module::where('name', 'Hero Banners')->delete();

        $permissions = ['view banners', 'create banners', 'edit banners', 'delete banners'];
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

        Cache::forget('admin_sidebar_modules');
    }
};
