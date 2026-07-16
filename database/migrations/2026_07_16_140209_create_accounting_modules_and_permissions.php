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
        $permission = Permission::firstOrCreate(['name' => 'view cash book', 'guard_name' => 'web']);
        $permission->update(['module' => 'Accounting']);

        // 2. Grant to super-admin
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo('view cash book');
        }

        // 3. Create Accounting module category
        $accountingCategory = Module::create([
            'name'           => 'Accounting',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/accounting*',
            'permission'     => null,
            'sort_order'     => 11,
        ]);

        // 4. Create Cash Book child module
        Module::create([
            'parent_id'      => $accountingCategory->id,
            'name'           => 'Cash Book',
            'icon'           => 'ti ti-book',
            'route'          => 'admin.accounting.cashbook',
            'active_pattern' => 'admin/accounting/cashbook*',
            'permission'     => 'view cash book',
            'sort_order'     => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('active_pattern', 'like', 'admin/accounting%')->forceDelete();
        
        $permission = Permission::where('name', 'view cash book')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
