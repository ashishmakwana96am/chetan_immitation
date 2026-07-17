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
        $permission = Permission::firstOrCreate(['name' => 'view bank book', 'guard_name' => 'web']);
        $permission->update(['module' => 'Accounting']);

        // 2. Grant to super-admin
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo('view bank book');
        }

        // 3. Find Accounting module category
        $accountingCategory = Module::where('name', 'Accounting')->first();
        if ($accountingCategory) {
            // 4. Create Bank Book child module
            Module::create([
                'parent_id'      => $accountingCategory->id,
                'name'           => 'Bank Book',
                'icon'           => 'ti ti-file-spreadsheet',
                'route'          => 'admin.accounting.bankbook',
                'active_pattern' => 'admin/accounting/bankbook*',
                'permission'     => 'view bank book',
                'sort_order'     => 2,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Module::where('route', 'admin.accounting.bankbook')->forceDelete();
        
        $permission = Permission::where('name', 'view bank book')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
