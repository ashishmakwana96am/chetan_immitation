<?php

namespace App\Services;

use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PurchaseBillLedgerService
{
    public static function post(PurchaseBill $bill, float $amount, ?PurchaseBillPayment $payment = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $balanceType = $bill->payment_method === 'online' ? LocationBalanceTransaction::BALANCE_TYPE_BANK : LocationBalanceTransaction::BALANCE_TYPE_CASH;
        $balanceColumn = $balanceType . '_balance';
        $balances = LocationBalance::whereIn('location_id', [$bill->from_location_id, $bill->to_location_id])
            ->orderBy('location_id')->lockForUpdate()->get()->keyBy('location_id');

        foreach (self::sides($bill) as [$location, $type, $note]) {
            $balance = $balances->get($location) ?? LocationBalance::where('location_id', $location)->firstOrFail();
            $entry = LocationBalanceTransaction::firstOrNew([
                'location_id' => $location,
                'balance_type' => $balanceType,
                'type' => $type,
                'notes' => $payment ? $note . ' (Payment #' . $payment->id . ')' : $note,
            ]);
            if ($payment && $entry->exists) {
                continue;
            }

            $balanceAfter = round((float) $balance->{$balanceColumn} + ($type === LocationBalanceTransaction::TYPE_CREDIT ? $amount : -$amount), 2);
            $balance->update([$balanceColumn => $balanceAfter]);
            $entry->fill([
                'amount' => round((float) $entry->amount + $amount, 2),
                'balance_after' => $balanceAfter,
                'created_by' => $payment?->created_by ?? auth()->id(),
            ]);
            if ($payment) {
                $entry->created_at = $payment->created_at;
            }
            $entry->save();
        }
    }

    public static function reverse(PurchaseBill $bill, ?float $amount = null): void
    {
        foreach (self::sides($bill) as [, , $note]) {
            $remaining = $amount;
            $entries = self::entriesFor($note)->orderByDesc('created_at')->orderByDesc('id')->lockForUpdate()->get();
            foreach ($entries as $entry) {
                if ($remaining === null) {
                    $entry->delete();
                    continue;
                }
                if ($remaining <= 0) {
                    break;
                }
                $reversed = min($remaining, (float) $entry->amount);
                $updatedAmount = round($entry->amount - $reversed, 2);
                $remaining = round($remaining - $reversed, 2);
                if ($updatedAmount > 0) {
                    $entry->update(['amount' => $updatedAmount]);
                } else {
                    $entry->delete();
                }
            }
            self::syncBalances($entries);
        }
    }

    public static function moveToUpdatedBranches(PurchaseBill $bill): void
    {
        foreach (self::sides($bill) as [$location, , $note]) {
            $entries = self::entriesFor($note)->where('location_id', '!=', $location)->lockForUpdate()->get();
            foreach ($entries as $entry) {
                LocationBalanceTransaction::whereKey($entry->id)->update(['location_id' => $location]);
            }
            self::syncBalances($entries);
            foreach ($entries->pluck('balance_type')->unique() as $balanceType) {
                LocationBalanceTransaction::syncLocationBalance($location, $balanceType);
            }
        }
    }

    private static function sides(PurchaseBill $bill): array
    {
        return [
            [$bill->from_location_id, LocationBalanceTransaction::TYPE_CREDIT, 'Purchase Bill Out #' . $bill->transfer_no],
            [$bill->to_location_id, LocationBalanceTransaction::TYPE_DEBIT, 'Purchase Bill In #' . $bill->transfer_no],
        ];
    }

    private static function entriesFor(string $note): Builder
    {
        return LocationBalanceTransaction::where(fn ($query) => $query->where('notes', $note)->orWhere('notes', 'like', $note . ' (Payment #%)'));
    }

    private static function syncBalances(Collection $entries): void
    {
        foreach ($entries->unique(fn ($entry) => $entry->location_id . '_' . $entry->balance_type) as $entry) {
            LocationBalanceTransaction::syncLocationBalance($entry->location_id, $entry->balance_type);
        }
    }
}
