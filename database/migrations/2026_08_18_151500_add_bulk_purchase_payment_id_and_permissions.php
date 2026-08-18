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
        if (!Schema::hasColumn('purchase_payments', 'bulk_purchase_payment_id')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->foreignId('bulk_purchase_payment_id')->nullable()->after('purchase_id')->constrained('bulk_purchase_payments')->cascadeOnDelete();
            });
        }

        $permissions = ['edit payable payment', 'delete payable payment'];
        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $perm->update(['module' => 'Accounting']);
        }

        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            foreach ($permissions as $p) {
                $superAdmin->givePermissionTo($p);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_payments', 'bulk_purchase_payment_id')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->dropForeign(['bulk_purchase_payment_id']);
                $table->dropColumn('bulk_purchase_payment_id');
            });
        }

        Permission::whereIn('name', ['edit payable payment', 'delete payable payment'])->delete();
    }
};
