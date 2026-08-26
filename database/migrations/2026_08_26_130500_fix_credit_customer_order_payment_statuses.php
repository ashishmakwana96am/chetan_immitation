<?php

use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\CustomerBalanceTransaction;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix past orders for credit customers where wallet was debited only for cash paid instead of full order final_amount
        DB::transaction(function () {
            $orders = Order::whereNotNull('customer_id')
                ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
                ->get();

            foreach ($orders as $order) {
                $customer = Customer::find($order->customer_id);
                if (!$customer || !$customer->is_credit_customer) {
                    continue;
                }

                $orderNo = $order->order_no ?? $order->order_number;
                if (!$orderNo) {
                    continue;
                }

                $existingTxs = CustomerBalanceTransaction::where('customer_id', $customer->id)
                    ->where('notes', 'LIKE', '%' . $orderNo . '%')
                    ->get();

                foreach ($existingTxs as $existingTx) {
                    $finalAmt = (float) $order->final_amount;
                    if ((float) $existingTx->amount != $finalAmt) {
                        $existingTx->update([
                            'amount' => $finalAmt,
                        ]);
                    }
                }

                // Update sale_payments records for this order
                $finalAmt = (float) $order->final_amount;
                DB::table('sale_payments')
                    ->where('order_id', $order->id)
                    ->whereNull('deleted_at')
                    ->update([
                        'amount'      => $finalAmt,
                        'cash_amount' => $finalAmt,
                    ]);

                $updateData = [
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                ];

                if ((float) $order->paid_cash_amount < $finalAmt && (float) $order->paid_online_amount <= 0) {
                    $updateData['paid_cash_amount'] = $finalAmt;
                }

                $order->update($updateData);
            }
        });

        // Clear dashboard caches to reflect fresh values
        \App\Http\Controllers\DashboardController::clearDashboardCaches();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
