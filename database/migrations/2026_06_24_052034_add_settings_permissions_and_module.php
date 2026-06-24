<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'view settings' => 'Settings',
            'edit settings' => 'Settings',
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
            'name'           => 'Settings',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/settings*',
            'permission'     => null,
            'sort_order'     => 9,
        ]);

        Module::create([
            'parent_id'      => $parent->id,
            'name'           => 'Settings',
            'icon'           => 'ti ti-settings',
            'route'          => 'admin.settings.index',
            'active_pattern' => 'admin/settings*',
            'permission'     => 'view settings',
            'sort_order'     => 1,
        ]);

        Setting::setValue('announcement_text', 'Festive Season Sale: Up to 40% Off | Free Shipping on Orders Above ₹1999');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'announcement_text')->delete();

        Module::where('name', 'Settings')->delete();

        $permissions = ['view settings', 'edit settings'];
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
