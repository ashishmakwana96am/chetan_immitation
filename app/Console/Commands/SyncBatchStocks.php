<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Product;

use App\Services\PurchaseBatchService;
use Illuminate\Console\Command;

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

        $this->info("Successfully synchronized purchase batch stocks across all {$locations->count()} locations and {$products->count()} products ({$totalCount} combinations).");
        return Command::SUCCESS;
    }
}
