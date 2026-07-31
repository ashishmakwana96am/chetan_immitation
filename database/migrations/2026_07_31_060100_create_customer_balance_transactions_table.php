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
        Schema::create('customer_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('source');
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        $permission = Permission::firstOrCreate(['name' => 'manage customer balance', 'guard_name' => 'web']);
        $permission->update(['module' => 'Accounting']);

        $role = Role::where('name', 'super-admin')->first();
        if ($role) {
            $role->givePermissionTo('manage customer balance');
        }

        // Also grant to any role that can already access the cash book / bank book,
        // so existing staff don't lose access to a button now shown on those pages.
        $bookRoles = Role::where(function ($q) {
            $q->whereHas('permissions', fn ($pq) => $pq->where('name', 'view cash book'))
                ->orWhereHas('permissions', fn ($pq) => $pq->where('name', 'view bank book'));
        })->get();

        foreach ($bookRoles as $bookRole) {
            $bookRole->givePermissionTo('manage customer balance');
        }
    }

    public function down(): void
    {
        $perm = Permission::where('name', 'manage customer balance')->first();
        if ($perm) {
            $perm->delete();
        }

        Schema::dropIfExists('customer_balance_transactions');
    }
};
