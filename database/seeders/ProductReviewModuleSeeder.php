<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;

class ProductReviewModuleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view product reviews'   => 'Product Reviews',
            'delete product reviews' => 'Product Reviews',
        ];

        foreach ($permissions as $name => $module) {
            $p = Permission::firstOrCreate(['name' => $name]);
            $p->update(['module' => $module]);
        }

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        $parentModule = Module::whereHas('children', function ($q) {
            $q->where('route', 'admin.customers.index');
        })->first();

        if (!$parentModule) {
            $parentModule = Module::whereNotNull('parent_id')->first()?->parent
                ?? Module::whereNull('parent_id')->latest()->first();
        }

        if ($parentModule) {
            $maxSort = Module::where('parent_id', $parentModule->id)->max('sort_order') ?? 0;

            Module::firstOrCreate(
                ['route' => 'admin.product-reviews.index'],
                [
                    'parent_id'      => $parentModule->id,
                    'name'           => 'Product Reviews',
                    'icon'           => 'ti ti-star',
                    'route'          => 'admin.product-reviews.index',
                    'active_pattern' => 'admin/product-reviews*',
                    'permission'     => 'view product reviews',
                    'sort_order'     => $maxSort + 1,
                ]
            );
        }
    }
}
