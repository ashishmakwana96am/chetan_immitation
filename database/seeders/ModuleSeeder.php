<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks to allow truncate
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Module::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 1. Dashboard
        Module::create([
            'name'           => 'Dashboard',
            'icon'           => 'ti ti-smart-home',
            'route'          => 'admin.dashboard',
            'active_pattern' => 'admin/dashboard',
            'permission'     => null,
            'sort_order'     => 1,
        ]);

        // 2. Users
        Module::create([
            'name'           => 'Users',
            'icon'           => 'ti ti-user',
            'route'          => 'admin.users.index',
            'active_pattern' => 'admin/users*',
            'permission'     => 'view users',
            'sort_order'     => 2,
        ]);

        // 3. Locations
        Module::create([
            'name'           => 'Locations',
            'icon'           => 'ti ti-map-pin',
            'route'          => 'admin.locations.index',
            'active_pattern' => 'admin/locations*',
            'permission'     => 'view locations',
            'sort_order'     => 3,
        ]);

        // 4. Categories
        Module::create([
            'name'           => 'Categories',
            'icon'           => 'ti ti-category',
            'route'          => 'admin.categories.index',
            'active_pattern' => 'admin/categories*',
            'permission'     => 'view categories',
            'sort_order'     => 4,
        ]);

        // 5. Sub Categories
        Module::create([
            'name'           => 'Sub Categories',
            'icon'           => 'ti ti-layout-grid-add',
            'route'          => 'admin.sub-categories.index',
            'active_pattern' => 'admin/sub-categories*',
            'permission'     => 'view sub categories',
            'sort_order'     => 5,
        ]);

        // 6. Products
        Module::create([
            'name'           => 'Products',
            'icon'           => 'ti ti-box',
            'route'          => 'admin.products.index',
            'active_pattern' => 'admin/products*',
            'permission'     => 'view products',
            'sort_order'     => 6,
        ]);

        // 7. Suppliers
        Module::create([
            'name'           => 'Suppliers',
            'icon'           => 'ti ti-truck',
            'route'          => 'admin.suppliers.index',
            'active_pattern' => 'admin/suppliers*',
            'permission'     => 'view suppliers',
            'sort_order'     => 7,
        ]);

        // 8. Purchases
        Module::create([
            'name'           => 'Purchases',
            'icon'           => 'ti ti-shopping-cart',
            'route'          => 'admin.purchases.index',
            'active_pattern' => 'admin/purchases*',
            'permission'     => 'view purchases',
            'sort_order'     => 8,
        ]);

        // 9. Customers
        Module::create([
            'name'           => 'Customers',
            'icon'           => 'ti ti-users',
            'route'          => 'admin.customers.index',
            'active_pattern' => 'admin/customers*',
            'permission'     => 'view customers',
            'sort_order'     => 9,
        ]);

        // 10. Sales
        Module::create([
            'name'           => 'Sales',
            'icon'           => 'ti ti-receipt',
            'route'          => 'admin.sales.index',
            'active_pattern' => 'admin/sales*',
            'permission'     => 'view sales',
            'sort_order'     => 10,
        ]);

        // 11. Reports (Parent)
        $reports = Module::create([
            'name'           => 'Reports',
            'icon'           => 'ti ti-chart-bar',
            'route'          => null,
            'active_pattern' => 'admin/reports*',
            'permission'     => null,
            'sort_order'     => 11,
        ]);

        // Reports children
        Module::create([
            'parent_id'      => $reports->id,
            'name'           => 'Products Report',
            'icon'           => null,
            'route'          => 'admin.reports.products',
            'active_pattern' => 'admin/reports/products',
            'permission'     => 'view product reports',
            'sort_order'     => 1,
        ]);

        Module::create([
            'parent_id'      => $reports->id,
            'name'           => 'Stock Inventory',
            'icon'           => null,
            'route'          => 'admin.reports.stock-inventory',
            'active_pattern' => 'admin/reports/stock-inventory',
            'permission'     => 'view stock inventory reports',
            'sort_order'     => 2,
        ]);

        Module::create([
            'parent_id'      => $reports->id,
            'name'           => 'Purchase Reports',
            'icon'           => null,
            'route'          => 'admin.reports.purchases',
            'active_pattern' => 'admin/reports/purchases',
            'permission'     => 'view purchase reports',
            'sort_order'     => 3,
        ]);

        Module::create([
            'parent_id'      => $reports->id,
            'name'           => 'Sale Report',
            'icon'           => null,
            'route'          => 'admin.reports.sales',
            'active_pattern' => 'admin/reports/sales',
            'permission'     => 'view sale reports',
            'sort_order'     => 4,
        ]);

        Module::create([
            'parent_id'      => $reports->id,
            'name'           => 'Profit & Loss Report',
            'icon'           => null,
            'route'          => 'admin.reports.profit-loss',
            'active_pattern' => 'admin/reports/profit-loss',
            'permission'     => 'view profit loss reports',
            'sort_order'     => 5,
        ]);

        // 12. Roles & Permissions (Parent)
        $rolesAndPerms = Module::create([
            'name'           => 'Roles & Permissions',
            'icon'           => 'ti ti-shield-lock',
            'route'          => null,
            'active_pattern' => 'admin/roles*,admin/permissions*,admin/modules*',
            'permission'     => null,
            'sort_order'     => 12,
        ]);

        // Roles & Permissions children
        Module::create([
            'parent_id'      => $rolesAndPerms->id,
            'name'           => 'Roles',
            'icon'           => null,
            'route'          => 'admin.roles.index',
            'active_pattern' => 'admin/roles*',
            'permission'     => 'view roles',
            'sort_order'     => 1,
        ]);

        Module::create([
            'parent_id'      => $rolesAndPerms->id,
            'name'           => 'Permissions',
            'icon'           => null,
            'route'          => 'admin.permissions.index',
            'active_pattern' => 'admin/permissions*',
            'permission'     => 'view permissions',
            'sort_order'     => 2,
        ]);

        Module::create([
            'parent_id'      => $rolesAndPerms->id,
            'name'           => 'Modules',
            'icon'           => null,
            'route'          => 'admin.modules.index',
            'active_pattern' => 'admin/modules*',
            'permission'     => 'view modules',
            'sort_order'     => 3,
        ]);
    }
}
