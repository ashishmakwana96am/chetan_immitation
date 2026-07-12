<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'import purchases',
            'guard_name' => 'web',
        ]);
        $permission->update(['module' => 'Purchases']);

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'import purchases')->first();
        if ($permission) {
            $superAdminRole = Role::where('name', 'super-admin')->first();
            if ($superAdminRole) {
                $superAdminRole->revokePermissionTo($permission);
            }
            $permission->delete();
        }
    }
};
