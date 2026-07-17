<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create permission
        $p = 'view customer ledger';
        $permission = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        $permission->update(['module' => 'Accounting']);

        // 2. Assign to super-admin
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo($p);
        }

        // 3. Create sub-module under "Accounting" parent module
        $parent = Module::where('name', 'Accounting')->first();
        if ($parent) {
            Module::create([
                'parent_id'      => $parent->id,
                'name'           => 'Customer Ledger',
                'icon'           => 'ti ti-users',
                'route'          => 'admin.ledgers.customer',
                'active_pattern' => 'admin/ledgers/customer*',
                'permission'     => 'view customer ledger',
                'sort_order'     => 5,
            ]);
        }
    }

    public function down(): void
    {
        // Remove module
        Module::withTrashed()
            ->where('active_pattern', 'admin/ledgers/customer*')
            ->forceDelete();

        // Delete permission
        $perm = Permission::where('name', 'view customer ledger')->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
