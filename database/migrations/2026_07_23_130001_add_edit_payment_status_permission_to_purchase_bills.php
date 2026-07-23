<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'edit purchase bills payment status',
            'guard_name' => 'web',
        ]);
        $permission->update(['module' => 'Purchase Bill']);

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }

    public function down(): void
    {
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->revokePermissionTo('edit purchase bills payment status');
        }

        Permission::where('name', 'edit purchase bills payment status')->delete();
    }
};
