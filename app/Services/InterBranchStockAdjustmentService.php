<?php

namespace App\Services;

use App\Models\PurchaseBill;
use App\Models\PurchaseBillPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterBranchStockAdjustmentService
{
    public static function adjustPendingBalances(PurchaseBill $newBill): array
    {
        $adjustments = [];

        if ((int) $newBill->status !== PurchaseBill::STATUS_ACCEPTED) {
            return $adjustments;
        }

        $fromLocId = (int) $newBill->from_location_id;
        $toLocId = (int) $newBill->to_location_id;

        if (!$fromLocId || !$toLocId || $fromLocId === $toLocId) {
            return $adjustments;
        }

        $newBillTotal = PurchaseStockService::calculatePurchaseBillTotal($newBill);
        if ($newBillTotal <= 0) {
            return $adjustments;
        }

        $newPaid = (float) ($newBill->paid_amount ?? 0);
        $availOffset = max(0.0, round($newBillTotal - $newPaid, 2));

        if ($availOffset <= 0) {
            return $adjustments;
        }

        $reverseBills = PurchaseBill::with(['items.product', 'items.variant'])
            ->where('from_location_id', $toLocId)
            ->where('to_location_id', $fromLocId)
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereIn('payment_status', [
                PurchaseBill::PAYMENT_STATUS_PENDING,
                PurchaseBill::PAYMENT_STATUS_PARTIAL,
            ])
            ->where('id', '!=', $newBill->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($reverseBills->isEmpty()) {
            return $adjustments;
        }

        $userId = auth()->id() ?? $newBill->accepted_by ?? $newBill->created_by;

        foreach ($reverseBills as $revBill) {
            if ($availOffset <= 0) {
                break;
            }

            $revTotal = PurchaseStockService::calculatePurchaseBillTotal($revBill);
            $revPaid = (float) ($revBill->paid_amount ?? 0);
            $revDue = max(0.0, round($revTotal - $revPaid, 2));

            if ($revDue <= 0) {
                if ($revBill->payment_status !== PurchaseBill::PAYMENT_STATUS_PAID) {
                    PurchaseBill::withoutActivityLogging(fn() => $revBill->update([
                        'payment_status' => PurchaseBill::PAYMENT_STATUS_PAID,
                        'paid_amount' => $revTotal,
                    ]));
                }
                continue;
            }

            $offsetAmt = min($revDue, $availOffset);
            if ($offsetAmt <= 0) {
                continue;
            }

            $newRevPaid = round($revPaid + $offsetAmt, 2);
            $revFinalStatus = ($newRevPaid >= $revTotal)
                ? PurchaseBill::PAYMENT_STATUS_PAID
                : PurchaseBill::PAYMENT_STATUS_PARTIAL;

            PurchaseBillPayment::create([
                'purchase_bill_id' => $revBill->id,
                'stock_return_bill_id' => $newBill->id,
                'amount' => $offsetAmt,
                'notes' => 'Auto-adjusted via Stock Return #' . $newBill->transfer_no,
                'created_by' => $userId,
            ]);

            PurchaseBill::withoutActivityLogging(fn() => $revBill->update([
                'paid_amount' => min($newRevPaid, $revTotal),
                'payment_status' => $revFinalStatus,
            ]));

            $newPaid = round($newPaid + $offsetAmt, 2);
            $newFinalStatus = ($newPaid >= $newBillTotal)
                ? PurchaseBill::PAYMENT_STATUS_PAID
                : PurchaseBill::PAYMENT_STATUS_PARTIAL;

            PurchaseBillPayment::create([
                'purchase_bill_id' => $newBill->id,
                'stock_return_bill_id' => $revBill->id,
                'amount' => $offsetAmt,
                'notes' => 'Auto-offset against pending Bill #' . $revBill->transfer_no,
                'created_by' => $userId,
            ]);

            PurchaseBill::withoutActivityLogging(fn() => $newBill->update([
                'paid_amount' => min($newPaid, $newBillTotal),
                'payment_status' => $newFinalStatus,
            ]));

            $availOffset = max(0.0, round($availOffset - $offsetAmt, 2));

            $adjustments[] = [
                'reverse_bill_id' => $revBill->id,
                'reverse_bill_no' => $revBill->transfer_no,
                'return_bill_id' => $newBill->id,
                'return_bill_no' => $newBill->transfer_no,
                'offset_amount' => $offsetAmt,
                'rev_remaining' => max(0.0, round($revTotal - $newRevPaid, 2)),
                'new_remaining' => $availOffset,
            ];
        }

        return $adjustments;
    }

    public static function recalculateBillPaymentStatus(PurchaseBill $bill): void
    {
        $total = PurchaseStockService::calculatePurchaseBillTotal($bill);
        $paid = (float) $bill->payments()->sum('amount');
        $status = ($total > 0 && $paid >= $total)
            ? PurchaseBill::PAYMENT_STATUS_PAID
            : ($paid > 0 ? PurchaseBill::PAYMENT_STATUS_PARTIAL : PurchaseBill::PAYMENT_STATUS_PENDING);

        PurchaseBill::withoutActivityLogging(fn() => $bill->update([
            'paid_amount'    => min($paid, $total),
            'payment_status' => $status,
        ]));
    }

    public static function reconcileBill(PurchaseBill $bill): void
    {
        $offsets = PurchaseBillPayment::where('stock_return_bill_id', $bill->id)
            ->orWhere(fn ($query) => $query->where('purchase_bill_id', $bill->id)->whereNotNull('stock_return_bill_id'))
            ->orderBy('created_at')->orderBy('id')->get()
            ->groupBy(fn ($payment) => (int) $payment->purchase_bill_id === $bill->id
                ? $payment->stock_return_bill_id : $payment->purchase_bill_id);

        $billTotal = PurchaseStockService::calculatePurchaseBillTotal($bill);
        $manualPaid = (float) $bill->payments()->whereNull('stock_return_bill_id')->sum('amount');
        $available = max(0.0, round($billTotal - $manualPaid, 2));

        foreach ($offsets as $otherId => $payments) {
            $other = PurchaseBill::find($otherId);
            $ownPayments = $payments->where('purchase_bill_id', $bill->id);
            $otherPayments = $payments->where('purchase_bill_id', $otherId);
            $amount = 0.0;

            if ($other && $other->status === PurchaseBill::STATUS_ACCEPTED
                && (int) $other->from_location_id === (int) $bill->to_location_id
                && (int) $other->to_location_id === (int) $bill->from_location_id) {
                $otherTotal = PurchaseStockService::calculatePurchaseBillTotal($other);
                $otherPaid = (float) $other->payments()->sum('amount') - (float) $otherPayments->sum('amount');
                $otherAvailable = max(0.0, round($otherTotal - $otherPaid, 2));
                $amount = min($available, $otherAvailable, round((float) $ownPayments->sum('amount'), 2), round((float) $otherPayments->sum('amount'), 2));
            }

            // Keep existing payment IDs and dates on both bills, reducing the newest offsets first.
            foreach ([$ownPayments, $otherPayments] as $side) {
                $remaining = $amount;
                foreach ($side as $payment) {
                    $kept = min($remaining, (float) $payment->amount);
                    $remaining = round($remaining - $kept, 2);
                    if ($kept <= 0) {
                        $payment->delete();
                    } elseif ($kept < (float) $payment->amount) {
                        $payment->update(['amount' => $kept]);
                    }
                }
            }

            $available = round($available - $amount, 2);
            if ($other) {
                self::recalculateBillPaymentStatus($other);
            }
        }

        self::recalculateBillPaymentStatus($bill);
    }

    public static function handleBillDeletion(PurchaseBill $bill): void
    {
        $incoming = $bill->returnAdjustments();
        $linkedBillIds = $incoming->pluck('purchase_bill_id')
            ->merge($bill->payments()->whereNotNull('stock_return_bill_id')->pluck('stock_return_bill_id'));

        $incoming->delete();
        $bill->delete();

        foreach (PurchaseBill::whereKey($linkedBillIds->unique()->all())->get() as $linkedBill) {
            self::recalculateBillPaymentStatus($linkedBill);
        }
    }

    public static function syncAllHistoricalData(): array
    {
        return DB::transaction(function () {
            $autoPayments = PurchaseBillPayment::whereNotNull('stock_return_bill_id')->get();
            foreach ($autoPayments as $ap) {
                $ap->delete();
            }

            $allAcceptedBills = PurchaseBill::with(['items.product', 'items.variant', 'payments'])
                ->where('status', PurchaseBill::STATUS_ACCEPTED)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($allAcceptedBills as $b) {
                $bTotal = PurchaseStockService::calculatePurchaseBillTotal($b);
                $manualPaid = (float) $b
                    ->payments()
                    ->whereNull('stock_return_bill_id')
                    ->sum('amount');

                $bStatus = ($bTotal > 0 && $manualPaid >= $bTotal)
                    ? PurchaseBill::PAYMENT_STATUS_PAID
                    : ($manualPaid > 0 ? PurchaseBill::PAYMENT_STATUS_PARTIAL : PurchaseBill::PAYMENT_STATUS_PENDING);

                PurchaseBill::withoutActivityLogging(fn() => $b->update([
                    'paid_amount' => min($manualPaid, $bTotal),
                    'payment_status' => $bStatus,
                ]));
            }

            $totalAdjustmentsCount = 0;
            $totalAdjustedAmount = 0.0;

            foreach ($allAcceptedBills as $b) {
                $b->refresh();
                $b->load(['items.product', 'items.variant']);

                if ($b->payment_status === PurchaseBill::PAYMENT_STATUS_PAID) {
                    continue;
                }

                $res = self::adjustPendingBalances($b);
                if (!empty($res)) {
                    $totalAdjustmentsCount += count($res);
                    $totalAdjustedAmount += array_sum(array_column($res, 'offset_amount'));
                }
            }

            return [
                'status' => 'success',
                'adjustments_count' => $totalAdjustmentsCount,
                'total_amount' => round($totalAdjustedAmount, 2),
            ];
        });
    }
}
