<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view outstanding payables',
            'create payable payment',
            'edit payable payment',
            'delete payable payment',
        ];

        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $perm->update(['module' => 'Accounting']);
        }

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['create payable payment', 'edit payable payment', 'delete payable payment'])->delete();
    }
};
