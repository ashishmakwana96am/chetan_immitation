<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use Illuminate\Support\Facades\DB;

class RecalculateLocationBalances extends Command
{
    protected $signature = 'recalculate:location-balances';
    protected $description = 'Recalculate running balance_after for all location balance transactions and sync location_balances table.';

    public function handle(): int
    {
        $this->info('Starting recalculation of location balances and transaction balance_after...');

        $locations = Location::all();

        DB::transaction(function () use ($locations) {
            foreach ($locations as $loc) {
                foreach ([LocationBalanceTransaction::BALANCE_TYPE_CASH, LocationBalanceTransaction::BALANCE_TYPE_BANK] as $balanceType) {
                    $transactions = LocationBalanceTransaction::where('location_id', $loc->id)
                        ->where('balance_type', $balanceType)
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $purchasesToCheck = [];
                    $ordersToCheck = [];

                    foreach ($transactions as $tx) {
                        $notes = $tx->notes ?? '';
                        if (preg_match('/Purchase\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                            $purchasesToCheck[$matches[1]] = true;
                        } elseif (preg_match('/\[Inv:\s*([^\]]+)\]/i', $notes, $matches)) {
                            $purchasesToCheck[trim($matches[1])] = true;
                        }
                        if (preg_match('/Sale\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                            $ordersToCheck[$matches[1]] = true;
                        }
                    }

                    $existingPurchases = !empty($purchasesToCheck)
                        ? \App\Models\Purchase::whereIn('invoice_no', array_keys($purchasesToCheck))->pluck('invoice_no')->flip()
                        : collect();

                    $existingOrders = !empty($ordersToCheck)
                        ? \App\Models\Order::whereIn('order_no', array_keys($ordersToCheck))->where('location_id', $loc->id)->pluck('order_no')->flip()
                        : collect();

                    $runningBalance = 0.0;
                    foreach ($transactions as $tx) {
                        $notes = $tx->notes ?? '';
                        $isGhost = false;

                        if (preg_match('/Purchase\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                            if (!$existingPurchases->has($matches[1])) {
                                $isGhost = true;
                            }
                        } elseif (preg_match('/\[Inv:\s*([^\]]+)\]/i', $notes, $matches)) {
                            if (!$existingPurchases->has(trim($matches[1]))) {
                                $isGhost = true;
                            }
                        }
                        if (preg_match('/Sale\s+#([^\s\[\]]+)/i', $notes, $matches)) {
                            if (!$existingOrders->has($matches[1])) {
                                $isGhost = true;
                            }
                        }

                        if ($isGhost) {
                            $tx->delete();
                            continue;
                        }

                        $amt = (float) $tx->amount;
                        if ($tx->type === LocationBalanceTransaction::TYPE_CREDIT) {
                            $runningBalance += $amt;
                        } else {
                            $runningBalance -= $amt;
                        }

                        $tx->update(['balance_after' => round($runningBalance, 2)]);
                    }

                    $balanceCol = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK ? 'bank_balance' : 'cash_balance';
                    $balanceRecord = LocationBalance::firstOrCreate(['location_id' => $loc->id]);
                    $balanceRecord->update([$balanceCol => round($runningBalance, 2)]);

                    $this->info("Location ID {$loc->id} ({$loc->name}) [{$balanceType}]: Synced balance = " . round($runningBalance, 2));
                }
            }
        });

        $this->info('Recalculation finished successfully!');
        return 0;
    }
}
