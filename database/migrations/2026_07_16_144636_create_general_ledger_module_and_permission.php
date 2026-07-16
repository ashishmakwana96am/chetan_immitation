<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create permission
        $permission = Permission::firstOrCreate(['name' => 'view general ledger', 'guard_name' => 'web']);
        $permission->update(['module' => 'Accounting']);

        // 2. Grant to super-admin
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo('view general ledger');
        }

        // 3. Find Accounting module category
        $accountingCategory = Module::where('name', 'Accounting')->first();
        if ($accountingCategory) {
            // 4. Create General Ledger child module
            Module::create([
                'parent_id'      => $accountingCategory->id,
                'name'           => 'General Ledger',
                'icon'           => 'ti ti-report-analytics',
                'route'          => 'admin.accounting.general-ledger',
                'active_pattern' => 'admin/accounting/general-ledger*',
                'permission'     => 'view general ledger',
                'sort_order'     => 3,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('route', 'admin.accounting.general-ledger')->forceDelete();

        $permission = Permission::where('name', 'view general ledger')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
