<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('payment_method', 'razorpay')
            ->update(['payment_method' => 'online']);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('payment_method', 'online')
            ->whereIn('source', ['ONLINE'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('order_payments')
                    ->whereColumn('order_payments.order_id', 'orders.id')
                    ->where('order_payments.gateway', 'razorpay');
            })
            ->update(['payment_method' => 'razorpay']);
    }
};
