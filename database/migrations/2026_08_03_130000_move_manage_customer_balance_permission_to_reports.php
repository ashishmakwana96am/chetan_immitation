<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::where('name', 'manage customer balance')->update(['module' => 'Reports']);
    }

    public function down(): void
    {
        Permission::where('name', 'manage customer balance')->update(['module' => 'Accounting']);
    }
};
