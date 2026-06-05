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
            'view users' => 'Users',
            'create users' => 'Users',
            'edit users' => 'Users',
            'delete users' => 'Users',
            'change users password' => 'Users',

            // Roles
            'view roles' => 'Roles',
            'create roles' => 'Roles',
            'edit roles' => 'Roles',
            'delete roles' => 'Roles',

            // Permissions
            'view permissions' => 'Permissions',
            'create permissions' => 'Permissions',
            'edit permissions' => 'Permissions',
            'delete permissions' => 'Permissions',

            // Modules
            'view modules' => 'Modules',
            'reorder modules' => 'Modules',

            // Locations
            'view locations' => 'Locations',
            'create locations' => 'Locations',
            'edit locations' => 'Locations',
            'delete locations' => 'Locations',

            // Categories
            'view categories' => 'Categories',
            'create categories' => 'Categories',
            'edit categories' => 'Categories',
            'delete categories' => 'Categories',

            // Sub Categories
            'view sub categories' => 'Sub Categories',
            'create sub categories' => 'Sub Categories',
            'edit sub categories' => 'Sub Categories',
            'delete sub categories' => 'Sub Categories',

            // Products
            'view products' => 'Products',
            'create products' => 'Products',
            'edit products' => 'Products',
            'delete products' => 'Products',
            'clone products' => 'Products',

            // Reports
            'view product reports' => 'Reports',
            'view stock inventory reports' => 'Reports',
            'view purchase reports' => 'Reports',
            'view sale reports' => 'Reports',
            'view profit loss reports' => 'Reports',

            // Suppliers
            'view suppliers' => 'Suppliers',
            'create suppliers' => 'Suppliers',
            'edit suppliers' => 'Suppliers',
            'delete suppliers' => 'Suppliers',

            // Purchases
            'view purchases' => 'Purchases',
            'create purchases' => 'Purchases',
            'edit purchases' => 'Purchases',
            'delete purchases' => 'Purchases',
            'edit purchases status' => 'Purchases',
            'edit purchases payment status' => 'Purchases',

            // Customers
            'view customers' => 'Customers',
            'create customers' => 'Customers',
            'edit customers' => 'Customers',
            'delete customers' => 'Customers',

            // Sales
            'view sales' => 'Sales',
            'create sales' => 'Sales',
            'edit sales' => 'Sales',
            'delete sales' => 'Sales',
            'edit sales status' => 'Sales',
            'edit sales payment status' => 'Sales',
        ];

        foreach ($permissions as $name => $module) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $permission->update(['module' => $module]);
        }

        // Assign all permissions to super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }
    }
}
