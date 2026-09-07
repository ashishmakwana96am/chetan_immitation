<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseBillItem;
use App\Services\PurchaseBatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBatchStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-batch-stocks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize and populate purchase_batch_stocks table for all products and locations based on purchase history and live inventory.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting purchase_batch_stocks synchronization...');

        PurchaseBatchService::ensureBatchStocksTable();

        // 1. Repair any existing zero price purchase bill items
        $zeroBillItems = PurchaseBillItem::where(function ($q) {
            $q->whereNull('purchase_price')->orWhere('purchase_price', '<=', 0);
        })->get();

        $fixedCount = 0;
        foreach ($zeroBillItems as $item) {
            $fallbackPrice = PurchaseBatchService::resolveFallbackPurchasePrice($item->product_id, $item->product_variant_id);
            if ($fallbackPrice > 0) {
                $item->update(['purchase_price' => $fallbackPrice]);
                $fixedCount++;
            }
        }
        if ($fixedCount > 0) {
            $this->info("Fixed {$fixedCount} purchase bill items that had zero/null purchase prices.");
        }

        // 2. Sync all products and variants across all locations
        $locations = Location::all();
        $products = Product::with('variants')->get();

        $totalCount = 0;

        foreach ($locations as $location) {
            foreach ($products as $product) {
                if ($product->type === 'variable' && $product->variants->count() > 0) {
                    foreach ($product->variants as $variant) {
                        PurchaseBatchService::syncProductBatchStocks($location->id, $product->id, $variant->id);
                        $totalCount++;
                    }
                } else {
                    PurchaseBatchService::syncProductBatchStocks($location->id, $product->id, null);
                    $totalCount++;
                }
            }
        }

        // 3. Clean up zero price zero quantity batch records
        DB::table('purchase_batch_stocks')->where('purchase_price', 0)->delete();

        $this->info("Successfully synchronized purchase batch stocks across all {$locations->count()} locations and {$products->count()} products ({$totalCount} combinations).");
        return Command::SUCCESS;
    }
}
