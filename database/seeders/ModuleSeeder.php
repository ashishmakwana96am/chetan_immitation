<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key constraints
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Module::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 1. Dashboard
        $dashboardCategory = Module::create([
            'name'           => 'Dashboard Overview',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/dashboard*',
            'permission'     => null,
            'sort_order'     => 1,
        ]);
        Module::create([
            'parent_id'      => $dashboardCategory->id,
            'name'           => 'Dashboard',
            'icon'           => 'ti ti-smart-home',
            'route'          => 'admin.dashboard',
            'active_pattern' => 'admin/dashboard',
            'permission'     => null,
            'sort_order'     => 1,
        ]);

        // 2. Catalog
        $catalogCategory = Module::create([
            'name'           => 'Catalog',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/categories*,admin/sub-categories*,admin/products*,admin/attributes*',
            'permission'     => null,
            'sort_order'     => 2,
        ]);
        Module::create([
            'parent_id'      => $catalogCategory->id,
            'name'           => 'Categories',
            'icon'           => 'ti ti-category',
            'route'          => 'admin.categories.index',
            'active_pattern' => 'admin/categories*',
            'permission'     => 'view categories',
            'sort_order'     => 1,
        ]);
        Module::create([
            'parent_id'      => $catalogCategory->id,
            'name'           => 'Sub Categories',
            'icon'           => 'ti ti-layout-grid-add',
            'route'          => 'admin.sub-categories.index',
            'active_pattern' => 'admin/sub-categories*',
            'permission'     => 'view sub categories',
            'sort_order'     => 2,
        ]);
        Module::create([
            'parent_id'      => $catalogCategory->id,
            'name'           => 'Products',
            'icon'           => 'ti ti-box',
            'route'          => 'admin.products.index',
            'active_pattern' => 'admin/products*',
            'permission'     => 'view products',
            'sort_order'     => 3,
        ]);
        Module::create([
            'parent_id'      => $catalogCategory->id,
            'name'           => 'Attributes',
            'icon'           => 'ti ti-list-check',
            'route'          => 'admin.attributes.index',
            'active_pattern' => 'admin/attributes*',
            'permission'     => 'view attributes',
            'sort_order'     => 4,
        ]);

        // 3. Inventory & Procurement
        $inventoryCategory = Module::create([
            'name'           => 'Inventory & Procurement',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/suppliers*,admin/purchases*',
            'permission'     => null,
            'sort_order'     => 3,
        ]);
        Module::create([
            'parent_id'      => $inventoryCategory->id,
            'name'           => 'Suppliers',
            'icon'           => 'ti ti-truck',
            'route'          => 'admin.suppliers.index',
            'active_pattern' => 'admin/suppliers*',
            'permission'     => 'view suppliers',
            'sort_order'     => 1,
        ]);
        Module::create([
            'parent_id'      => $inventoryCategory->id,
            'name'           => 'Purchases',
            'icon'           => 'ti ti-shopping-cart',
            'route'          => 'admin.purchases.index',
            'active_pattern' => 'admin/purchases*',
            'permission'     => 'view purchases',
            'sort_order'     => 2,
        ]);

        // 4. Sales & Customers
        $salesCustomersCategory = Module::create([
            'name'           => 'Sales & Customers',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/customers*,admin/sales*',
            'permission'     => null,
            'sort_order'     => 4,
        ]);
        Module::create([
            'parent_id'      => $salesCustomersCategory->id,
            'name'           => 'Customers',
            'icon'           => 'ti ti-users',
            'route'          => 'admin.customers.index',
            'active_pattern' => 'admin/customers*',
            'permission'     => 'view customers',
            'sort_order'     => 1,
        ]);
        Module::create([
            'parent_id'      => $salesCustomersCategory->id,
            'name'           => 'Sales',
            'icon'           => 'ti ti-receipt',
            'route'          => 'admin.sales.index',
            'active_pattern' => 'admin/sales*',
            'permission'     => 'view sales',
            'sort_order'     => 2,
        ]);

        // 5. Location
        $locationCategory = Module::create([
            'name'           => 'Location',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/locations*',
            'permission'     => null,
            'sort_order'     => 5,
        ]);
        Module::create([
            'parent_id'      => $locationCategory->id,
            'name'           => 'Locations',
            'icon'           => 'ti ti-map-pin',
            'route'          => 'admin.locations.index',
            'active_pattern' => 'admin/locations*',
            'permission'     => 'view locations',
            'sort_order'     => 1,
        ]);

        // 6. Reports & Analytics
        $reportsCategory = Module::create([
            'name'           => 'Reports & Analytics',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/reports*',
            'permission'     => null,
            'sort_order'     => 6,
        ]);
        Module::create([
            'parent_id'      => $reportsCategory->id,
            'name'           => 'Products Report',
            'icon'           => 'ti ti-chart-bar',
            'route'          => 'admin.reports.products',
            'active_pattern' => 'admin/reports/products',
            'permission'     => 'view product reports',
            'sort_order'     => 1,
        ]);
        Module::create([
            'parent_id'      => $reportsCategory->id,
            'name'           => 'Stock Inventory',
            'icon'           => 'ti ti-chart-pie',
            'route'          => 'admin.reports.stock-inventory',
            'active_pattern' => 'admin/reports/stock-inventory',
            'permission'     => 'view stock inventory reports',
            'sort_order'     => 2,
        ]);
        Module::create([
            'parent_id'      => $reportsCategory->id,
            'name'           => 'Purchase Reports',
            'icon'           => 'ti ti-chart-line',
            'route'          => 'admin.reports.purchases',
            'active_pattern' => 'admin/reports/purchases',
            'permission'     => 'view purchase reports',
            'sort_order'     => 3,
        ]);
        Module::create([
            'parent_id'      => $reportsCategory->id,
            'name'           => 'Sales Report',
            'icon'           => 'ti ti-chart-arrows',
            'route'          => 'admin.reports.sales',
            'active_pattern' => 'admin/reports/sales',
            'permission'     => 'view sale reports',
            'sort_order'     => 4,
        ]);
        Module::create([
            'parent_id'      => $reportsCategory->id,
            'name'           => 'Profit & Loss Report',
            'icon'           => 'ti ti-chart-infographic',
            'route'          => 'admin.reports.profit-loss',
            'active_pattern' => 'admin/reports/profit-loss',
            'permission'     => 'view profit loss reports',
            'sort_order'     => 5,
        ]);

        // 7. User & Access
        $userAccessCategory = Module::create([
            'name'           => 'User & Access',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/users*,admin/roles*,admin/permissions*',
            'permission'     => null,
            'sort_order'     => 7,
        ]);
        Module::create([
            'parent_id'      => $userAccessCategory->id,
            'name'           => 'Users',
            'icon'           => 'ti ti-user',
            'route'          => 'admin.users.index',
            'active_pattern' => 'admin/users*',
            'permission'     => 'view users',
            'sort_order'     => 1,
        ]);
        Module::create([
            'parent_id'      => $userAccessCategory->id,
            'name'           => 'Roles',
            'icon'           => 'ti ti-shield-lock',
            'route'          => 'admin.roles.index',
            'active_pattern' => 'admin/roles*',
            'permission'     => 'view roles',
            'sort_order'     => 2,
        ]);

        // 8. Website Content
        $websiteCategory = Module::create([
            'name'           => 'Website Content',
            'icon'           => null,
            'route'          => null,
            'active_pattern' => 'admin/website-content*,admin/contact-inquiries*',
            'permission'     => null,
            'sort_order'     => 8,
        ]);
        Module::create([
            'parent_id'      => $websiteCategory->id,
            'name'           => 'Contact Inquiries',
            'icon'           => 'ti ti-mail',
            'route'          => 'admin.contact-inquiries.index',
            'active_pattern' => 'admin/contact-inquiries*',
            'permission'     => 'view contact inquiries',
            'sort_order'     => 1,
        ]);
    }
}
