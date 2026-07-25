<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::where('name', 'delete sales')->first();
        if ($permission) {
            $permission->delete();
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::firstOrCreate(['name' => 'delete sales', 'guard_name' => 'web', 'module' => 'Sales']);
    }
};
