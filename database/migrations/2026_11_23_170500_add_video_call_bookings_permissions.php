<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            'view video call bookings' => 'Video Call Bookings',
            'cancel video call bookings' => 'Video Call Bookings',
        ];

        $roles = Role::whereIn('name', ['Super Admin', 'Admin', 'super-admin'])->get();

        foreach ($perms as $name => $module) {
            $perm = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ], [
                'module' => $module,
            ]);
            $perm->update(['module' => $module]);

            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'view video call bookings',
            'cancel video call bookings',
        ])->delete();
    }
};
