<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Find BT-1 transfer record (including soft deleted)
            $transfer = DB::table('branch_balance_transfers')
                ->where('transfer_no', 'BT-1')
                ->first();

            if (!$transfer) {
                return;
            }

            // 1. If accepted (status = 1), revert balance back to locations
            if ((int) $transfer->status === 1) {
                $balanceColumn = strtolower((string) $transfer->balance_type) === 'bank' ? 'bank_balance' : 'cash_balance';
                $amount        = (float) $transfer->amount;

                // Increase From Location balance
                DB::table('location_balances')
                    ->where('location_id', $transfer->from_location_id)
                    ->increment($balanceColumn, $amount);

                // Decrease To Location balance
                DB::table('location_balances')
                    ->where('location_id', $transfer->to_location_id)
                    ->decrement($balanceColumn, $amount);

                // Revert linked PurchaseBill Payments
                $linkedPayments = DB::table('purchase_bill_payments')
                    ->where('branch_balance_transfer_id', $transfer->id)
                    ->get();

                foreach ($linkedPayments as $pPayment) {
                    $pBill = DB::table('purchase_bills')->where('id', $pPayment->purchase_bill_id)->first();
                    if ($pBill) {
                        $currentPaid = (float) $pBill->paid_amount;
                        $payAmt      = (float) $pPayment->amount;
                        $newPaid     = max(0, round($currentPaid - $payAmt, 2));

                        $totalAmount = (float) ($pBill->total_amount ?? 0);
                        $newStatus   = 1; // Pending
                        if ($newPaid >= $totalAmount && $totalAmount > 0) {
                            $newStatus = 2; // Paid
                        } elseif ($newPaid > 0) {
                            $newStatus = 3; // Partial
                        }

                        DB::table('purchase_bills')
                            ->where('id', $pBill->id)
                            ->update([
                                'paid_amount'    => $newPaid,
                                'payment_status' => $newStatus,
                                'updated_at'     => now(),
                            ]);
                    }
                }

                // Delete linked purchase bill payment records
                DB::table('purchase_bill_payments')
                    ->where('branch_balance_transfer_id', $transfer->id)
                    ->delete();
            }

            // 2. Remove location_balance_transactions entries for BT-1
            DB::table('location_balance_transactions')
                ->where('notes', 'like', '%BT-1%')
                ->delete();

            // 3. Remove branch_balance_transfers entry for BT-1 completely
            DB::table('branch_balance_transfers')
                ->where('id', $transfer->id)
                ->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed for a data cleanup migration
    }
};
