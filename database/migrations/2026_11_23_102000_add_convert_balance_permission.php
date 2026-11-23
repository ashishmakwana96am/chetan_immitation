<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::firstOrCreate([
            'name' => 'convert balance',
            'guard_name' => 'web',
        ], [
            'module' => 'Branch Ledger',
        ]);
        $perm->update(['module' => 'Branch Ledger']);

        $roles = Role::whereIn('name', ['Super Admin', 'Admin', 'super-admin'])->get();
        foreach ($roles as $role) {
            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    public function down(): void
    {
        $perm = Permission::where('name', 'convert balance')->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
