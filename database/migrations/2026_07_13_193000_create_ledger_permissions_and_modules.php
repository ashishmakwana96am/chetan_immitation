<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view supplier ledger',
            'view cash ledger',
            'view bank ledger',
            'view branch ledger',
        ];

        foreach ($permissions as $p) {
            $permission = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $permission->update(['module' => 'Ledgers']);
        }

        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }

        $ledgersCategory = Module::create([
            'name'           => 'Ledgers',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/ledgers*',
            'permission'     => null,
            'sort_order'     => 10,
        ]);

        Module::create([
            'parent_id'      => $ledgersCategory->id,
            'name'           => 'Supplier Ledger',
            'icon'           => 'ti ti-building-store',
            'route'          => 'admin.ledgers.supplier',
            'active_pattern' => 'admin/ledgers/supplier*',
            'permission'     => 'view supplier ledger',
            'sort_order'     => 1,
        ]);

        Module::create([
            'parent_id'      => $ledgersCategory->id,
            'name'           => 'Cash Ledger',
            'icon'           => 'ti ti-cash',
            'route'          => 'admin.ledgers.cash',
            'active_pattern' => 'admin/ledgers/cash*',
            'permission'     => 'view cash ledger',
            'sort_order'     => 2,
        ]);

        Module::create([
            'parent_id'      => $ledgersCategory->id,
            'name'           => 'Bank Ledger',
            'icon'           => 'ti ti-building-bank',
            'route'          => 'admin.ledgers.bank',
            'active_pattern' => 'admin/ledgers/bank*',
            'permission'     => 'view bank ledger',
            'sort_order'     => 3,
        ]);

        Module::create([
            'parent_id'      => $ledgersCategory->id,
            'name'           => 'Branch Ledger',
            'icon'           => 'ti ti-arrows-exchange',
            'route'          => 'admin.ledgers.branch',
            'active_pattern' => 'admin/ledgers/branch*',
            'permission'     => 'view branch ledger',
            'sort_order'     => 4,
        ]);
    }

    public function down(): void
    {
        Module::withTrashed()
            ->where('active_pattern', 'admin/ledgers*')
            ->orWhere('active_pattern', 'like', 'admin/ledgers/%')
            ->forceDelete();

        $permissions = [
            'view supplier ledger',
            'view cash ledger',
            'view bank ledger',
            'view branch ledger',
        ];

        foreach ($permissions as $p) {
            $perm = Permission::where('name', $p)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }
};
