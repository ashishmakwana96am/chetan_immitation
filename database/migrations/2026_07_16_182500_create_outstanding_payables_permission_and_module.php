<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $p = 'view outstanding payables';
        $permission = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        $permission->update(['module' => 'Accounting']);

        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo($p);
        }

        $parent = Module::where('name', 'Accounting')->first();
        if ($parent) {
            Module::create([
                'parent_id'      => $parent->id,
                'name'           => 'Outstanding Payables',
                'icon'           => 'ti ti-wallet',
                'route'          => 'admin.accounting.outstanding-payables',
                'active_pattern' => 'admin/accounting/outstanding-payables*',
                'permission'     => 'view outstanding payables',
                'sort_order'     => 6,
            ]);
        }
    }

    public function down(): void
    {
        Module::withTrashed()
            ->where('active_pattern', 'admin/accounting/outstanding-payables*')
            ->forceDelete();

        $perm = Permission::where('name', 'view outstanding payables')->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
