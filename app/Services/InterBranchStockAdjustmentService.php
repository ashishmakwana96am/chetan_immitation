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

    public static function revertAdjustments(PurchaseBill $bill): array
    {
        $affectedBillIds = [];

        $linkedPayments = PurchaseBillPayment::where('stock_return_bill_id', $bill->id)->get();
        foreach ($linkedPayments as $lp) {
            $affectedBillIds[] = $lp->purchase_bill_id;
            $otherBill = PurchaseBill::find($lp->purchase_bill_id);
            $lp->delete();
            if ($otherBill) {
                self::recalculateBillPaymentStatus($otherBill);
            }
        }

        $ownAutoPayments = PurchaseBillPayment::where('purchase_bill_id', $bill->id)
            ->whereNotNull('stock_return_bill_id')
            ->get();
        foreach ($ownAutoPayments as $oap) {
            $affectedBillIds[] = $oap->stock_return_bill_id;
            $otherBill = PurchaseBill::find($oap->stock_return_bill_id);
            $oap->delete();
            if ($otherBill) {
                self::recalculateBillPaymentStatus($otherBill);
            }
        }

        self::recalculateBillPaymentStatus($bill);

        return array_values(array_unique(array_filter($affectedBillIds)));
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
        $affectedOtherBillIds = self::revertAdjustments($bill);

        $bill->refresh();
        if ((int) $bill->status === PurchaseBill::STATUS_ACCEPTED && $bill->payment_status !== PurchaseBill::PAYMENT_STATUS_PAID) {
            self::adjustPendingBalances($bill);
        }

        foreach ($affectedOtherBillIds as $otherId) {
            $otherBill = PurchaseBill::find($otherId);
            if ($otherBill && (int) $otherBill->status === PurchaseBill::STATUS_ACCEPTED && $otherBill->payment_status !== PurchaseBill::PAYMENT_STATUS_PAID) {
                self::adjustPendingBalances($otherBill);
            }
        }
    }

    public static function handleBillDeletion(PurchaseBill $bill): void
    {
        $affectedOtherBillIds = self::revertAdjustments($bill);

        $bill->payments()->delete();
        $bill->items()->delete();
        $bill->delete();

        foreach ($affectedOtherBillIds as $otherId) {
            $otherBill = PurchaseBill::find($otherId);
            if ($otherBill && (int) $otherBill->status === PurchaseBill::STATUS_ACCEPTED && $otherBill->payment_status !== PurchaseBill::PAYMENT_STATUS_PAID) {
                self::adjustPendingBalances($otherBill);
            }
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
