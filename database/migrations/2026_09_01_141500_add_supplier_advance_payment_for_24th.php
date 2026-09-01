<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Find all supplier advance payments on 24th August 2026
        $advancesOn24th = DB::table('supplier_advance_payments')
            ->whereDate('created_at', '2026-08-24')
            ->orderBy('id', 'asc')
            ->get();

        if ($advancesOn24th->count() > 1) {
            // If duplicate record exists, keep original (first) and delete extra created by migration
            $extraAdv = $advancesOn24th->slice(1);
            foreach ($extraAdv as $extra) {
                if ($extra->bulk_purchase_payment_id) {
                    DB::table('bulk_purchase_payments')->where('id', $extra->bulk_purchase_payment_id)->delete();
                }
                DB::table('supplier_advance_payments')->where('id', $extra->id)->delete();
            }
        }

        $existingAdv = DB::table('supplier_advance_payments')
            ->whereDate('created_at', '2026-08-24')
            ->first();

        if ($existingAdv) {
            $paymentDate = $existingAdv->created_at;
            $amount = (float) $existingAdv->total_amount;
            $paymentMethod = $existingAdv->payment_method ?? 'cash';
            $supplierId = $existingAdv->supplier_id;
            $userId = $existingAdv->created_by ?? 1;

            // Ensure Bulk Purchase Payment is linked
            if (!$existingAdv->bulk_purchase_payment_id) {
                $bulkPaymentId = DB::table('bulk_purchase_payments')->insertGetId([
                    'supplier_id'    => $supplierId,
                    'total_amount'   => $amount,
                    'payment_method' => $paymentMethod,
                    'created_by'     => $userId,
                    'created_at'     => $paymentDate,
                    'updated_at'     => $paymentDate,
                ]);

                DB::table('supplier_advance_payments')->where('id', $existingAdv->id)->update([
                    'bulk_purchase_payment_id' => $bulkPaymentId,
                ]);
            }

            // Ensure Cashbook (Location Balance Transaction) exists
            $hasCashbook = DB::table('location_balance_transactions')
                ->where('balance_type', $paymentMethod)
                ->where('amount', $amount)
                ->whereDate('created_at', '2026-08-24')
                ->exists();

            if (!$hasCashbook) {
                $location = Location::where('is_default', 1)->first() ?? Location::first();
                $locationId = $location ? $location->id : 1;

                $balCol = $paymentMethod === 'cash' ? 'cash_balance' : 'bank_balance';
                $locBal = DB::table('location_balances')->where('location_id', $locationId)->first();
                $currentBal = $locBal ? (float) $locBal->{$balCol} : 0.00;
                $newBal = round($currentBal - $amount, 2);

                if ($locBal) {
                    DB::table('location_balances')->where('location_id', $locationId)->update([
                        $balCol      => $newBal,
                        'updated_at' => $paymentDate,
                    ]);
                }

                $supplierObj = Supplier::find($supplierId);
                $suppName = $supplierObj ? $supplierObj->name : 'Supplier';

                DB::table('location_balance_transactions')->insert([
                    'location_id'   => $locationId,
                    'balance_type'  => $paymentMethod,
                    'type'          => 'debit',
                    'amount'        => $amount,
                    'balance_after' => $newBal,
                    'notes'         => 'Advance Payment (₹' . number_format($amount, 2) . ') to ' . $suppName,
                    'created_by'    => $userId,
                    'created_at'    => $paymentDate,
                    'updated_at'    => $paymentDate,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
