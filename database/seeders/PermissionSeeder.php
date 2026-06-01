<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Roles
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Permissions
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',

            // Locations
            'view locations',
            'create locations',
            'edit locations',
            'delete locations',

            // Categories
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Sub Categories
            'view sub categories',
            'create sub categories',
            'edit sub categories',
            'delete sub categories',

            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Reports
            'view product reports',
            'view stock inventory reports',
            'view purchase reports',
            'view sale reports',
            'view profit loss reports',

            // Suppliers
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',

            // Purchases
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',

            // Customers
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',

            // Sales
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }
}
