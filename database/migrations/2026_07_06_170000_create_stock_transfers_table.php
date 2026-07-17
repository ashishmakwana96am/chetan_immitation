<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->unsignedBigInteger('from_location_id');
            $table->unsignedBigInteger('to_location_id');
            $table->tinyInteger('status')->default(1); // 1 = Pending, 2 = Accepted, 3 = Rejected
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('from_location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('to_location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Insert Purchase Bills module into modules table
        $inventoryModuleId = DB::table('modules')
            ->where('name', 'Inventory & Procurement')
            ->whereNull('parent_id')
            ->value('id');

        DB::table('modules')->updateOrInsert([
            'route' => 'admin.stock-transfers.index',
        ], [
            'parent_id' => $inventoryModuleId,
            'name' => 'Purchase Bills',
            'route' => 'admin.stock-transfers.index',
            'active_pattern' => 'admin/stock-transfers*',
            'permission' => 'view purchase bills',
            'icon' => 'ti ti-transfer-out',
            'sort_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Spatie Permissions
        $permissions = [
            'view purchase bills',
            'create purchase bills',
            'accept purchase bills',
            'reject purchase bills'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Assign to Super Admin role
        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        // Remove module
        DB::table('modules')->where('route', 'admin.stock-transfers.index')->delete();

        // Remove Spatie Permissions
        $permissions = [
            'view purchase bills',
            'create purchase bills',
            'accept purchase bills',
            'reject purchase bills'
        ];

        foreach ($permissions as $p) {
            $perm = Permission::where('name', $p)->first();
            if ($perm) {
                $perm->delete();
            }
        }

        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
