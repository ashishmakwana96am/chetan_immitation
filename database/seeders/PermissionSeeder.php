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

            // States
            'view states' => 'States',
            'create states' => 'States',
            'edit states' => 'States',
            'delete states' => 'States',

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
            'bulk upload product images' => 'Products',

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

            // Purchase Bills
            'view purchase bills' => 'Purchase Bill',
            'create purchase bills' => 'Purchase Bill',
            'edit purchase bills' => 'Purchase Bill',
            'accept purchase bills' => 'Purchase Bill',
            'reject purchase bills' => 'Purchase Bill',
            'export purchase bills' => 'Purchase Bill',
            'edit purchase bills payment status' => 'Purchase Bill',

            // Expenses
            'view expenses' => 'Expenses',
            'create expenses' => 'Expenses',
            'edit expenses' => 'Expenses',
            'delete expenses' => 'Expenses',

            // Customers
            'view customers' => 'Customers',
            'create customers' => 'Customers',
            'edit customers' => 'Customers',
            'delete customers' => 'Customers',

            // Sales
            'view sales' => 'Sales',
            'create sales' => 'Sales',
            'edit sales' => 'Sales',
            'edit sales status' => 'Sales',
            'edit sales payment status' => 'Sales',

            // Attributes
            'view attributes' => 'Attributes',
            'create attributes' => 'Attributes',
            'edit attributes' => 'Attributes',
            'delete attributes' => 'Attributes',

            // Contact Inquiries
            'view contact inquiries' => 'Contact Inquiries',
            'delete contact inquiries' => 'Contact Inquiries',

            // Coupons
            'view coupons' => 'Coupons',
            'create coupons' => 'Coupons',
            'edit coupons' => 'Coupons',
            'delete coupons' => 'Coupons',

            // Product Reviews
            'view product reviews' => 'Product Reviews',
            'delete product reviews' => 'Product Reviews',

            // Website Content
            'view website content' => 'Website Content',
            'edit website content' => 'Website Content',

            // Settings
            'view settings' => 'Settings',
            'edit settings' => 'Settings',
            'download backup' => 'Settings',
            // Past Date Records
            'edit past date records' => 'Past Date Records',
        ];

        // Migrate/cleanup old permission if it exists
        Permission::where('name', 'delete sales')->delete();

        $oldPermission = Permission::where('name', 'view activity logs')->first();
        if ($oldPermission) {
            $newPermission = Permission::firstOrCreate(['name' => 'view utility report', 'module' => 'Reports']);
            $rolesWithOldPermission = Role::permission('view activity logs')->get();
            foreach ($rolesWithOldPermission as $role) {
                $role->givePermissionTo($newPermission);
            }
            $oldPermission->delete();
        }

        $permissions['view payment reports'] = 'Reports';
        $permissions['view utility report'] = 'Reports';
        $permissions['view daily reports'] = 'Reports';
        $permissions['download sales'] = 'Sales';
        $permissions['download purchases'] = 'Purchases';

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
