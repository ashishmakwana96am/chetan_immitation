<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method');
            $table->unsignedBigInteger('location_id');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Insert Expenses module into modules table
        $inventoryModuleId = DB::table('modules')
            ->where('name', 'Inventory & Procurement')
            ->whereNull('parent_id')
            ->value('id');

        if ($inventoryModuleId) {
            DB::table('modules')->where('id', $inventoryModuleId)->update([
                'active_pattern' => 'admin/suppliers*,admin/purchases*,admin/stock-transfers*,admin/expenses*',
            ]);
        }

        DB::table('modules')->updateOrInsert([
            'route' => 'admin.expenses.index',
        ], [
            'parent_id' => $inventoryModuleId,
            'name' => 'Expenses',
            'route' => 'admin.expenses.index',
            'active_pattern' => 'admin/expenses*',
            'permission' => 'view expenses',
            'icon' => 'ti ti-wallet',
            'sort_order' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Spatie Permissions
        $permissions = [
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
        ];

        foreach ($permissions as $p) {
            $permission = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $permission->update(['module' => 'Expenses']);
        }

        // Assign to Super Admin role
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        // Remove module
        DB::table('modules')->where('route', 'admin.expenses.index')->delete();

        // Remove Spatie Permissions
        $permissions = [
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
        ];

        foreach ($permissions as $p) {
            $perm = Permission::where('name', $p)->first();
            if ($perm) {
                $perm->delete();
            }
        }

        Schema::dropIfExists('expenses');
    }
};
