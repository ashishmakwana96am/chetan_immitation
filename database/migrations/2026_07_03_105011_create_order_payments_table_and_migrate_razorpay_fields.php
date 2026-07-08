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
        // 1. Create order_payments table
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway')->default('razorpay'); // payment gateway name
            $table->string('gateway_order_id')->nullable();    // razorpay_order_id
            $table->string('gateway_payment_id')->nullable();  // razorpay_payment_id
            $table->string('gateway_signature')->nullable();   // razorpay_signature
            $table->string('status')->default('captured');     // captured / failed / refunded
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->timestamps();
        });

        // 2. Migrate existing razorpay data from orders to order_payments
        $orders = DB::table('orders')
            ->whereNotNull('razorpay_payment_id')
            ->select('id', 'final_amount', 'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature', 'created_at')
            ->get();

        foreach ($orders as $order) {
            DB::table('order_payments')->insert([
                'order_id'           => $order->id,
                'gateway'            => 'razorpay',
                'gateway_order_id'   => $order->razorpay_order_id,
                'gateway_payment_id' => $order->razorpay_payment_id,
                'gateway_signature'  => $order->razorpay_signature,
                'status'             => 'captured',
                'amount'             => $order->final_amount,
                'currency'           => 'INR',
                'created_at'         => $order->created_at,
                'updated_at'         => $order->created_at,
            ]);
        }

        // 3. Drop razorpay columns from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature']);
        });

        // 4. Add permission
        $permission = Permission::firstOrCreate(
            ['name' => 'view payment reports', 'guard_name' => 'web'],
            ['module' => 'Reports']
        );
        $permission->update(['module' => 'Reports']);

        // Assign to super-admin
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        // 5. Add module entry for sidebar
        $reportsParent = DB::table('modules')->whereNull('parent_id')->where('name', 'Reports & Analytics')->first();
        if ($reportsParent) {
            // Get current max sort order under reports
            $maxSort = DB::table('modules')->where('parent_id', $reportsParent->id)->max('sort_order') ?? 5;
            DB::table('modules')->insert([
                'parent_id'      => $reportsParent->id,
                'name'           => 'Payment Report',
                'icon'           => 'ti ti-credit-card',
                'route'          => 'admin.reports.payments',
                'active_pattern' => 'admin/reports/payments',
                'permission'     => 'view payment reports',
                'sort_order'     => $maxSort + 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Restore razorpay columns to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('razorpay_order_id')->nullable()->after('coupon_id');
            $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            $table->string('razorpay_signature')->nullable()->after('razorpay_payment_id');
        });

        // Migrate data back
        $payments = DB::table('order_payments')->where('gateway', 'razorpay')->get();
        foreach ($payments as $payment) {
            DB::table('orders')->where('id', $payment->order_id)->update([
                'razorpay_order_id'   => $payment->gateway_order_id,
                'razorpay_payment_id' => $payment->gateway_payment_id,
                'razorpay_signature'  => $payment->gateway_signature,
            ]);
        }

        Schema::dropIfExists('order_payments');

        // Remove permission & module
        Permission::where('name', 'view payment reports')->delete();
        DB::table('modules')->where('route', 'admin.reports.payments')->delete();
    }
};
