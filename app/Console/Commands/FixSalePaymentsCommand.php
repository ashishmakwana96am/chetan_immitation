<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SalePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSalePaymentsCommand extends Command
{
    protected $signature = 'sales:fix-payment-breakdown 
                            {order_no? : Specific Order Number like SA-57} 
                            {--dry-run : Perform a dry run simulation without saving changes}';

    protected $description = 'Fix sales with duplicated or inflated cash + online payment breakdown';

    public function handle()
    {
        $orderNo = $this->argument('order_no');
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->newLine();
            $this->warn("=======================================================");
            $this->warn("   DRY RUN MODE ACTIVE: NO DATABASE CHANGES WILL BE SAVED  ");
            $this->warn("=======================================================");
            $this->newLine();
        }

        // Only search active (non-deleted) sales orders
        $query = Order::where('order_type', 'sale');

        if ($orderNo) {
            $cleanNo = trim($orderNo);
            $query->where(function ($q) use ($cleanNo) {
                $q->where('order_no', $cleanNo)
                  ->orWhere('order_no', 'like', "%{$cleanNo}");
            });
        }

        $orders = $query->get();
        $fixedCount = 0;

        if ($isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($orders as $order) {
                $final = (float) $order->final_amount;
                $cash = (float) $order->paid_cash_amount;
                $online = (float) $order->paid_online_amount;
                $method = $order->payment_method ?: 'cash';

                // Fetch active (non-deleted) sale payments
                $spList = SalePayment::where('order_id', $order->id)->get();

                $orderHasIssue = false;

                // Check order level issue
                if (
                    ($cash + $online > $final + 0.01) ||
                    ($cash > 0 && $online > 0 && $method !== 'online_cash') ||
                    ($method === 'cash' && $online > 0) ||
                    ($method === 'online' && $cash > 0)
                ) {
                    $orderHasIssue = true;
                }

                // Check payment level issue
                $spIssueCount = 0;
                foreach ($spList as $sp) {
                    $spAmount = (float) $sp->amount;
                    $spCash = (float) $sp->cash_amount;
                    $spOnline = (float) $sp->online_amount;
                    $spMethod = $sp->payment_method ?: $method;

                    if (
                        ($spCash + $spOnline > $spAmount + 0.01) ||
                        ($spCash > 0 && $spOnline > 0 && $spMethod !== 'online_cash') ||
                        ($spMethod === 'cash' && $spOnline > 0) ||
                        ($spMethod === 'online' && $spCash > 0)
                    ) {
                        $spIssueCount++;
                    }
                }

                if ($orderHasIssue || $spIssueCount > 0) {
                    // Determine resolved payment method
                    $newMethod = $method;
                    if ($method === 'online_cash') {
                        $newCash = min($cash, $final);
                        $newOnline = max(0, round($final - $newCash, 2));
                    } elseif ($method === 'online' || ($online > 0 && $cash <= 0)) {
                        $newMethod = 'online';
                        $newOnline = $final;
                        $newCash = 0.0;
                    } else {
                        $newMethod = 'cash';
                        $newCash = $final;
                        $newOnline = 0.0;
                    }

                    if (!$isDryRun) {
                        $order->update([
                            'paid_cash_amount'   => $newCash,
                            'paid_online_amount' => $newOnline,
                            'payment_method'     => $newMethod,
                        ]);

                        foreach ($spList as $sp) {
                            $spAmount = (float) $sp->amount;
                            if ($newMethod === 'online') {
                                $sp->update([
                                    'cash_amount'    => 0.0,
                                    'online_amount'  => $spAmount,
                                    'payment_method' => 'online',
                                ]);
                            } elseif ($newMethod === 'cash') {
                                $sp->update([
                                    'cash_amount'    => $spAmount,
                                    'online_amount'  => 0.0,
                                    'payment_method' => 'cash',
                                ]);
                            } else {
                                $cCap = min((float) $sp->cash_amount, $spAmount);
                                $oCap = max(0, round($spAmount - $cCap, 2));
                                $sp->update([
                                    'cash_amount'    => $cCap,
                                    'online_amount'  => $oCap,
                                    'payment_method' => 'online_cash',
                                ]);
                            }
                        }
                    }

                    $this->info("Order {$order->order_no} " . ($isDryRun ? "[WOULD BE FIXED]" : "[FIXED]") . " - Final: ₹{$final} | Old: (Cash: ₹{$cash}, Online: ₹{$online}, Method: {$method}) => New: (Cash: ₹{$newCash}, Online: ₹{$newOnline}, Method: {$newMethod})");
                    $fixedCount++;
                }
            }
        } finally {
            if ($isDryRun) {
                DB::rollBack();
            }
        }

        $this->newLine();
        if ($isDryRun) {
            $this->warn("Dry run complete. Found {$fixedCount} active orders that need payment breakdown fix. 0 database records modified.");
            $this->info("To apply these fixes for real, run the command WITHOUT --dry-run.");
        } else {
            $this->info("Successfully fixed total {$fixedCount} active sales orders.");
        }

        return 0;
    }
}
