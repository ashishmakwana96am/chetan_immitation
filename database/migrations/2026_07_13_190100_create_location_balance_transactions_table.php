<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->string('balance_type');
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        $permission = Permission::firstOrCreate(['name' => 'manage branch balances', 'guard_name' => 'web']);
        $permission->update(['module' => 'Locations']);

        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo('manage branch balances');
        }
    }

    public function down(): void
    {
        $perm = Permission::where('name', 'manage branch balances')->first();
        if ($perm) {
            $perm->delete();
        }

        Schema::dropIfExists('location_balance_transactions');
    }
};
