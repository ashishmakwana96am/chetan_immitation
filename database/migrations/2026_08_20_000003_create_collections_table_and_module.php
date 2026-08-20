<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Module;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create collections table
        Schema::dropIfExists('collections');
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable()->default('');
            $table->string('short_name', 50);
            $table->tinyInteger('status')->default(1)->comment('1: Active, 2: Inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 2. Create Spatie Permissions for Collections
        $permissions = [
            'view collections',
            'create collections',
            'edit collections',
            'delete collections',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $permission->update(['module' => 'Collections']);
        }

        // 3. Assign permissions to super-admin
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        // 4. Add Collections module in sidebar (under Catalog / right after Sub Categories)
        Module::withTrashed()->where('name', 'Collections')->forceDelete();
        $parent = Module::where('name', 'Catalog')->first();
        if ($parent) {
            Module::create([
                'parent_id'      => $parent->id,
                'name'           => 'Collections',
                'icon'           => 'ti ti-folders',
                'route'          => 'admin.collections.index',
                'active_pattern' => 'admin/collections*',
                'permission'     => 'view collections',
                'sort_order'     => 3,
            ]);
        }
    }

    public function down(): void
    {
        // 1. Remove Collections module from sidebar
        Module::withTrashed()->where('name', 'Collections')->forceDelete();

        // 2. Remove permissions
        $permissions = [
            'view collections',
            'create collections',
            'edit collections',
            'delete collections',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->roles()->detach();
                $permission->delete();
            }
        }

        // 3. Re-sync super admin permissions
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        // 4. Drop table
        Schema::dropIfExists('collections');
    }
};
