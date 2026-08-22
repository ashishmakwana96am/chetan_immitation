<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierAdvancePayment extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'supplier_id',
        'bulk_purchase_payment_id',
        'total_amount',
        'used_amount',
        'remaining_amount',
        'payment_method',
        'notes',
        'created_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function bulkPurchasePayment()
    {
        return $this->belongsTo(BulkPurchasePayment::class, 'bulk_purchase_payment_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function adjustAdvanceForPurchase(Purchase $purchase, ?float $maxAmountToDeduct = null): float
    {
        $supplierId = $purchase->supplier_id;
        if (!$supplierId) return 0.0;

        if ((int) $purchase->payment_status === Purchase::PAYMENT_STATUS_PENDING) {
            return 0.0;
        }

        $suppBalance = SupplierBalance::where('supplier_id', $supplierId)->first();
        if (!$suppBalance || $suppBalance->balance <= 0) return 0.0;

        $billTotal = (float) $purchase->total_amount;
        $currentPaid = (float) PurchasePayment::where('purchase_id', $purchase->id)->sum('amount');
        $dueAmt = round(max(0.0, $billTotal - $currentPaid), 2);
        if ($dueAmt <= 0) return 0.0;

        if ($maxAmountToDeduct !== null) {
            $dueAmt = min($dueAmt, max(0.0, round($maxAmountToDeduct, 2)));
        }
        if ($dueAmt <= 0) return 0.0;

        $availAdvance = (float) $suppBalance->balance;
        $adjustAmt = round(min($availAdvance, $dueAmt), 2);

        if ($adjustAmt <= 0) return 0.0;

        $advanceRecords = self::where('supplier_id', $supplierId)
            ->where('remaining_amount', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $remToDeduct = $adjustAmt;
        $cashDeducted = 0.0;
        $bankDeducted = 0.0;

        foreach ($advanceRecords as $adv) {
            if ($remToDeduct <= 0) break;
            $rem = (float) $adv->remaining_amount;
            $deduct = min($rem, $remToDeduct);

            $adv->used_amount = round((float) $adv->used_amount + $deduct, 2);
            $adv->remaining_amount = round($rem - $deduct, 2);
            $adv->save();

            if ($adv->payment_method === 'cash') {
                $cashDeducted += $deduct;
            } else {
                $bankDeducted += $deduct;
            }

            $remToDeduct = round($remToDeduct - $deduct, 2);
        }

        $suppBalance->balance = round(max(0.0, (float) $suppBalance->balance - $adjustAmt), 2);
        $suppBalance->cash_balance = round(max(0.0, (float) $suppBalance->cash_balance - $cashDeducted), 2);
        $suppBalance->bank_balance = round(max(0.0, (float) $suppBalance->bank_balance - $bankDeducted), 2);
        $suppBalance->save();

        $newPaid = round($currentPaid + $adjustAmt, 2);
        $finalStatus = ($newPaid >= $billTotal) ? Purchase::PAYMENT_STATUS_PAID : Purchase::PAYMENT_STATUS_PARTIAL;

        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount'      => $adjustAmt,
            'created_by'  => auth()->id(),
        ]);

        Purchase::withoutEvents(fn () => Purchase::withoutActivityLogging(fn () => $purchase->update([
            'paid_amount'    => min($newPaid, $billTotal),
            'payment_status' => $finalStatus,
        ])));

        $purchase->paid_amount = min($newPaid, $billTotal);
        $purchase->payment_status = $finalStatus;

        return $adjustAmt;
    }
}
