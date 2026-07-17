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
        $permission = Permission::where('name', 'import purchases')->first();
        if ($permission) {
            $permission->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::firstOrCreate(['name' => 'import purchases', 'guard_name' => 'web', 'module' => 'Purchases']);
    }
};
