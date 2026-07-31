<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view customer report', 'guard_name' => 'web']);
        $permission->update(['module' => 'Reports & Analytics']);

        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo('view customer report');
        }

        $reportsCategory = Module::where('name', 'Reports & Analytics')->whereNull('parent_id')->first();
        if ($reportsCategory) {
            Module::create([
                'parent_id'      => $reportsCategory->id,
                'name'           => 'Customer Report',
                'icon'           => 'ti ti-users',
                'route'          => 'admin.reports.customer-report',
                'active_pattern' => 'admin/reports/customer-report*',
                'permission'     => 'view customer report',
                'sort_order'     => 9,
            ]);
        }
    }

    public function down(): void
    {
        Module::where('route', 'admin.reports.customer-report')->forceDelete();

        $permission = Permission::where('name', 'view customer report')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
