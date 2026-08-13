<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'edit past date records',
        ], [
            'module' => 'Settings',
        ]);

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin && !$superAdmin->hasPermissionTo($permission)) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'edit past date records')->delete();
    }
};
