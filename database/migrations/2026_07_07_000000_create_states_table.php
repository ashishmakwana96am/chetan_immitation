<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('shipping_charge', 8, 2)->default(0);
            $table->unsignedInteger('delivery_days')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Insert States module into modules table, under the existing Location category
        $locationModuleId = DB::table('modules')
            ->where('name', 'Location')
            ->whereNull('parent_id')
            ->value('id');

        DB::table('modules')->updateOrInsert([
            'route' => 'admin.states.index',
        ], [
            'parent_id' => $locationModuleId,
            'name' => 'States',
            'route' => 'admin.states.index',
            'active_pattern' => 'admin/states*',
            'permission' => 'view states',
            'icon' => 'ti ti-map-2',
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($locationModuleId) {
            DB::table('modules')->where('id', $locationModuleId)->update([
                'active_pattern' => 'admin/locations*,admin/states*',
            ]);
        }

        // Create Spatie Permissions
        $permissions = [
            'view states',
            'create states',
            'edit states',
            'delete states',
        ];

        foreach ($permissions as $p) {
            $permission = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $permission->update(['module' => 'States']);
        }

        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        DB::table('modules')->where('route', 'admin.states.index')->delete();

        $permissions = [
            'view states',
            'create states',
            'edit states',
            'delete states',
        ];

        foreach ($permissions as $p) {
            $perm = Permission::where('name', $p)->first();
            if ($perm) {
                $perm->delete();
            }
        }

        Schema::dropIfExists('states');
    }
};
