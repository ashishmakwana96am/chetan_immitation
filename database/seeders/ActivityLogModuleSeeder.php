<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;

class ActivityLogModuleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view activity logs' => 'Utility Report',
        ];

        foreach ($permissions as $name => $module) {
            $p = Permission::firstOrCreate(['name' => $name]);
            $p->update(['module' => $module]);
        }

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        $parentModule = Module::whereHas('children', function ($q) {
            $q->where('route', 'admin.reports.sales');
        })->first();

        if (!$parentModule) {
            $parentModule = Module::whereNotNull('parent_id')->first()?->parent
                ?? Module::whereNull('parent_id')->latest()->first();
        }

        // Remove the old module entry from before this feature was renamed.
        Module::where('route', 'admin.activity-logs.index')->delete();

        if ($parentModule) {
            $maxSort = Module::where('parent_id', $parentModule->id)->max('sort_order') ?? 0;

            Module::updateOrCreate(
                ['route' => 'admin.reports.utility'],
                [
                    'parent_id'      => $parentModule->id,
                    'name'           => 'Utility Report',
                    'icon'           => 'ti ti-history',
                    'route'          => 'admin.reports.utility',
                    'active_pattern' => 'admin/reports/utility*',
                    'permission'     => 'view activity logs',
                    'sort_order'     => $maxSort + 1,
                ]
            );
        }
    }
}
