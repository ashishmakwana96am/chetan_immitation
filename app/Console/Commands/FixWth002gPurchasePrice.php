<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWth002gPurchasePrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:wth002g-price {--dry-run : Perform a dry run without changing database records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chronologically consume 4 Pcs of Old Batch (25-07-2026 @ 1485.00) across sales and transfers by exact date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $wthProductIds = DB::table('products')
            ->where('name', 'like', '%WTH002G%')
            ->orWhere('barcode', 'like', '%WTH002G%')
            ->pluck('id');

        if ($wthProductIds->isEmpty()) {
            $this->error('No product found with barcode/name WTH002G.');
            return 1;
        }

        // Fetch old purchase batch (ID 241 @ 1485.00)
        $oldPurchaseItem = DB::table('purchase_items')
            ->whereIn('product_id', $wthProductIds)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->first();

        // 1. Fetch order_items
        $orderItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $wthProductIds)
            ->whereNull('order_items.deleted_at')
            ->whereNull('orders.deleted_at')
            ->select('order_items.id', 'order_items.order_id', 'order_items.quantity', 'order_items.purchase_price', 'orders.created_at as event_date')
            ->get();

        // 2. Fetch transfer_items
        $transferItems = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->whereIn('purchase_bill_items.product_id', $wthProductIds)
            ->whereNull('purchase_bill_items.deleted_at')
            ->whereNull('purchase_bills.deleted_at')
            ->select('purchase_bill_items.id', 'purchase_bill_items.purchase_bill_id as ref_id', 'purchase_bill_items.quantity', 'purchase_bill_items.purchase_price', 'purchase_bills.created_at as event_date')
            ->get();

        // 3. Combine sales & transfers chronologically
        $timeline = collect();

        foreach ($orderItems as $oi) {
            $timeline->push([
                'type'     => 'order_item',
                'id'       => $oi->id,
                'ref_id'   => $oi->order_id,
                'date'     => $oi->event_date,
                'quantity' => max(1, (float)$oi->quantity),
            ]);
        }

        foreach ($transferItems as $ti) {
            $timeline->push([
                'type'     => 'transfer_item',
                'id'       => $ti->id,
                'ref_id'   => $ti->ref_id,
                'date'     => $ti->event_date,
                'quantity' => max(1, (float)$ti->quantity),
            ]);
        }

        $sortedTimeline = $timeline->sortBy('date')->values();

        // FIFO consumption of 4 Pcs old batch @ 1485.00
        $oldBatchRemainingQty = 4.0;
        $processedEvents = [];

        foreach ($sortedTimeline as $event) {
            $qtyNeeded = $event['quantity'];
            $unitPrice = 1440.00;
            $matchedBatchId = null;

            if ($oldBatchRemainingQty > 0) {
                $unitPrice = 1485.00;
                $matchedBatchId = $oldPurchaseItem ? $oldPurchaseItem->id : 241;
                $oldBatchRemainingQty -= $qtyNeeded;
                if ($oldBatchRemainingQty < 0) {
                    $oldBatchRemainingQty = 0;
                }
            }

            $processedEvents[] = [
                'type'             => $event['type'],
                'id'               => $event['id'],
                'ref_id'           => $event['ref_id'],
                'date'             => $event['date'],
                'quantity'         => $qtyNeeded,
                'unit_price'       => $unitPrice,
                'total_price'      => $unitPrice * $qtyNeeded,
                'purchase_item_id' => $matchedBatchId,
            ];
        }

        if ($isDryRun) {
            $this->warn("[DRY RUN MODE] Chronological FIFO Consumption (4 Pcs Old Batch @ 1485.00).");
            $this->info("Initial Old Batch Qty: 4 Pcs @ ₹ 1,485.00\n");

            foreach ($processedEvents as $pe) {
                $label = $pe['type'] === 'order_item' ? "Sale (Order ID {$pe['ref_id']})" : "Transfer (Bill ID {$pe['ref_id']})";
                $batchText = $pe['unit_price'] == 1485.00 ? "OLD Batch (Unit ₹ 1,485.00)" : "NEW Batch (Unit ₹ 1,440.00)";
                $this->line("  - [{$pe['date']}] {$label} Item ID {$pe['id']}: Qty {$pe['quantity']} Pc -> Matched {$batchText}, Total ₹ {$pe['total_price']}");
            }

            $this->info("\nTo execute actual database update, run: php artisan fix:wth002g-price");
            return 0;
        }

        // Apply actual updates
        if ($oldPurchaseItem) {
            DB::table('purchase_items')
                ->where('id', $oldPurchaseItem->id)
                ->update(['purchase_price' => 1485.00]);
        }

        foreach ($processedEvents as $pe) {
            if ($pe['type'] === 'order_item') {
                $updateData = ['purchase_price' => $pe['total_price']];
                if ($pe['purchase_item_id']) {
                    $updateData['purchase_item_id'] = $pe['purchase_item_id'];
                }
                DB::table('order_items')
                    ->where('id', $pe['id'])
                    ->update($updateData);
            } else {
                DB::table('purchase_bill_items')
                    ->where('id', $pe['id'])
                    ->update(['purchase_price' => $pe['unit_price']]);
            }
        }

        $st51Bill = DB::table('purchase_bills')->where('transfer_no', 'like', '%ST-51%')->orWhere('transfer_no', 'like', '%ST-051%')->first();
        if ($st51Bill) {
            DB::table('purchase_bill_items')
                ->where('purchase_bill_id', $st51Bill->id)
                ->whereIn('product_id', $wthProductIds)
                ->update(['purchase_price' => 1485.00]);
        }

        foreach ($wthProductIds as $pid) {
            $locations = DB::table('locations')->pluck('id');
            foreach ($locations as $locId) {
                \App\Services\PurchaseBatchService::syncProductBatchStocks((int)$locId, (int)$pid, null);
            }
        }

        $this->info("Successfully updated order_items, purchase_bill_items and synced batch stocks using strict chronological FIFO batch consumption.");
        return 0;
    }
}
