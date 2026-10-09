<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Services\PurchaseBatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:fix 
                            {--barcode= : Fix a single specific barcode (e.g. --barcode=BGR023)}
                            {--file=stock_mismatch_barcodes.txt : Read barcodes from a specific TXT file in the root directory}
                            {--all : Reconcile and fix all mismatched undeleted products in database}
                            {--batch-only : Fix and synchronize ONLY purchase_batch_stocks table without touching inventories}
                            {--detailed : Show all product details in console table}
                            {--dry-run : Simulate the fix without modifying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix inventory and purchase_batch_stocks for mismatched products (supports all barcodes from TXT file, all products, or single barcode).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=====================================================');
        $this->info('       Product Stock Fix & Synchronization           ');
        $this->info('=====================================================');

        $singleBarcode = trim((string) $this->option('barcode'));
        $fileName = $this->option('file') ?: 'stock_mismatch_barcodes.txt';
        $isAll = (bool) $this->option('all');
        $isBatchOnly = (bool) $this->option('batch-only');
        $isDetailed = (bool) $this->option('detailed');
        $isDryRun = (bool) $this->option('dry-run');

        if ($isBatchOnly) {
            $this->info('Mode: Only synchronizing `purchase_batch_stocks` table.');
        } else {
            $this->info('Mode: Synchronizing both `inventories` and `purchase_batch_stocks`.');
        }

        if ($isDryRun) {
            $this->warn('*** DRY-RUN MODE: No database changes will be saved. ***');
        }

        PurchaseBatchService::ensureBatchStocksTable();
        $allLocations = Location::all();
        if ($allLocations->isEmpty()) {
            $this->error('No locations found in the database.');
            return Command::FAILURE;
        }

        // 1. Determine target products
        $productQuery = Product::whereNull('deleted_at')->with(['variants' => function ($q) {
            $q->whereNull('deleted_at');
        }]);

        if ($singleBarcode !== '') {
            $this->info("Targeting single barcode: {$singleBarcode}");
            $productQuery->where('barcode', $singleBarcode);
        } elseif (!$isAll) {
            $filePath = base_path($fileName);
            if (!file_exists($filePath)) {
                $this->error("File not found: {$filePath}");
                $this->line("Please run `php artisan stock:reconcile` first or provide `--barcode=YOUR_BARCODE` or `--all`.");
                return Command::FAILURE;
            }

            $content = trim(file_get_contents($filePath));
            if ($content === '') {
                $this->info("File {$fileName} is empty. No barcodes to fix!");
                return Command::SUCCESS;
            }

            $barcodes = array_values(array_unique(array_filter(array_map('trim', explode(',', $content)))));
            $this->info("Loaded " . count($barcodes) . " barcodes from {$fileName}.");
            $productQuery->whereIn('barcode', $barcodes);
        } else {
            $this->info("Processing all undeleted products in database...");
        }

        $totalCount = $productQuery->count();
        if ($totalCount === 0) {
            $this->warn('No matching undeleted products found.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$totalCount} product(s)...");
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $processedCount = 0;
        $modifiedCount = 0;
        $fixSummary = [];

        $chunkSize = 200;
        $productQuery->chunk($chunkSize, function ($products) use (
            &$processedCount,
            &$modifiedCount,
            &$fixSummary,
            $allLocations,
            $isBatchOnly,
            $isDryRun,
            $isDetailed,
            $progressBar
        ) {
            $productIds = $products->pluck('id')->all();

            // 1. Fetch Purchase Items & Allocations for this chunk
            $purchaseRows = DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->leftJoin('purchase_allocations', function ($join) {
                    $join->on('purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
                        ->whereNull('purchase_allocations.deleted_at');
                })
                ->whereIn('purchase_items.product_id', $productIds)
                ->whereNull('purchase_items.deleted_at')
                ->whereNull('purchases.deleted_at')
                ->where('purchases.status', Purchase::STATUS_APPROVE)
                ->select(
                    'purchase_items.id as purchase_item_id',
                    'purchase_items.product_id',
                    'purchase_items.product_variant_id',
                    'purchase_items.quantity as item_quantity',
                    'purchase_items.custom_size_value',
                    'purchase_allocations.id as allocation_id',
                    'purchase_allocations.location_id as allocation_location_id',
                    'purchase_allocations.quantity as allocation_quantity',
                    'purchases.location_id as purchase_location_id'
                )
                ->get()
                ->groupBy('product_id');

            // 2. Fetch Transfers (Purchase Bills) for this chunk
            $transferRows = DB::table('purchase_bill_items')
                ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                ->whereIn('purchase_bill_items.product_id', $productIds)
                ->whereNull('purchase_bill_items.deleted_at')
                ->whereNull('purchase_bills.deleted_at')
                ->where('purchase_bills.status', PurchaseBill::STATUS_ACCEPTED)
                ->select(
                    'purchase_bill_items.id',
                    'purchase_bill_items.product_id',
                    'purchase_bill_items.product_variant_id',
                    'purchase_bill_items.quantity',
                    'purchase_bill_items.pair_type',
                    'purchase_bill_items.custom_size_value',
                    'purchase_bills.from_location_id',
                    'purchase_bills.to_location_id'
                )
                ->get()
                ->groupBy('product_id');

            // 3. Fetch Sales (Orders) for this chunk
            $orderRows = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('order_items.product_id', $productIds)
                ->whereNull('order_items.deleted_at')
                ->whereNull('orders.deleted_at')
                ->whereIn('orders.status', [
                    Order::STATUS_APPROVE,
                    Order::STATUS_SHIPPED,
                    Order::STATUS_OUT_FOR_DELIVERY,
                    Order::STATUS_DELIVERED,
                ])
                ->select(
                    'order_items.id',
                    'order_items.product_id',
                    'order_items.product_variant_id',
                    'order_items.quantity',
                    'order_items.pair_type',
                    'order_items.custom_size_value',
                    'orders.location_id'
                )
                ->get()
                ->groupBy('product_id');

            // 4. Fetch Live Inventories for this chunk
            $inventoryRows = DB::table('inventories')
                ->whereIn('product_id', $productIds)
                ->whereNull('deleted_at')
                ->select('product_id', 'location_id', 'quantity')
                ->get()
                ->groupBy('product_id');

            // 5. Fetch Live Purchase Batch Stocks for this chunk
            $batchStockRows = DB::table('purchase_batch_stocks')
                ->whereIn('product_id', $productIds)
                ->select('product_id', 'product_variant_id', 'location_id', 'quantity')
                ->get()
                ->groupBy('product_id');

            foreach ($products as $product) {
                $processedCount++;
                $pId = (int) $product->id;
                $variantsById = $product->variants->keyBy('id');

                // A. Purchases Inflow
                $purchasedByLoc = [];
                $purchasedByVariantLoc = [];
                $productPurchaseItems = ($purchaseRows->get($pId) ?? collect())->groupBy('purchase_item_id');

                foreach ($productPurchaseItems as $itemId => $pItemRows) {
                    $firstRow = $pItemRows->first();
                    $vId = $firstRow->product_variant_id ? (int) $firstRow->product_variant_id : null;
                    $variant = $vId ? $variantsById->get($vId) : null;
                    $multiplier = $this->calculatePurchaseMultiplier($product, $firstRow->custom_size_value, $variant);

                    $hasAllocations = $pItemRows->contains(fn($r) => !empty($r->allocation_id));
                    if ($hasAllocations) {
                        foreach ($pItemRows as $row) {
                            if (!empty($row->allocation_id) && !empty($row->allocation_location_id)) {
                                $locId = (int) $row->allocation_location_id;
                                $qty = (float) $row->allocation_quantity * $multiplier;
                                $purchasedByLoc[$locId] = ($purchasedByLoc[$locId] ?? 0.0) + $qty;
                                if ($vId) {
                                    $purchasedByVariantLoc[$vId][$locId] = ($purchasedByVariantLoc[$vId][$locId] ?? 0.0) + $qty;
                                }
                            }
                        }
                    } else {
                        $locId = (int) ($firstRow->purchase_location_id ?: 1);
                        $qty = (float) $firstRow->item_quantity * $multiplier;
                        $purchasedByLoc[$locId] = ($purchasedByLoc[$locId] ?? 0.0) + $qty;
                        if ($vId) {
                            $purchasedByVariantLoc[$vId][$locId] = ($purchasedByVariantLoc[$vId][$locId] ?? 0.0) + $qty;
                        }
                    }
                }

                // B. Transfers In / Out
                $transferInByLoc = [];
                $transferOutByLoc = [];
                $transferInByVariantLoc = [];
                $transferOutByVariantLoc = [];
                $productTransfers = $transferRows->get($pId) ?? collect();

                foreach ($productTransfers as $tItem) {
                    $vId = $tItem->product_variant_id ? (int) $tItem->product_variant_id : null;
                    $multiplier = $this->calculateOrderMultiplier($product, $tItem->pair_type, $tItem->custom_size_value);
                    $qty = (float) $tItem->quantity * $multiplier;
                    $fromLoc = (int) $tItem->from_location_id;
                    $toLoc = (int) $tItem->to_location_id;

                    if ($fromLoc) {
                        $transferOutByLoc[$fromLoc] = ($transferOutByLoc[$fromLoc] ?? 0.0) + $qty;
                        if ($vId) {
                            $transferOutByVariantLoc[$vId][$fromLoc] = ($transferOutByVariantLoc[$vId][$fromLoc] ?? 0.0) + $qty;
                        }
                    }

                    if ($toLoc) {
                        $transferInByLoc[$toLoc] = ($transferInByLoc[$toLoc] ?? 0.0) + $qty;
                        if ($vId) {
                            $transferInByVariantLoc[$vId][$toLoc] = ($transferInByVariantLoc[$vId][$toLoc] ?? 0.0) + $qty;
                        }
                    }
                }

                // C. Sales Outflow
                $soldByLoc = [];
                $soldByVariantLoc = [];
                $productOrders = $orderRows->get($pId) ?? collect();

                foreach ($productOrders as $oItem) {
                    $vId = $oItem->product_variant_id ? (int) $oItem->product_variant_id : null;
                    $multiplier = $this->calculateOrderMultiplier($product, $oItem->pair_type, $oItem->custom_size_value);
                    $qty = (float) $oItem->quantity * $multiplier;
                    $locId = (int) ($oItem->location_id ?: 1);

                    $soldByLoc[$locId] = ($soldByLoc[$locId] ?? 0.0) + $qty;
                    if ($vId) {
                        $soldByVariantLoc[$vId][$locId] = ($soldByVariantLoc[$vId][$locId] ?? 0.0) + $qty;
                    }
                }

                // D. Target expected stock per location
                $targetInventoryByLoc = [];
                $totalExpected = 0;

                foreach ($allLocations as $loc) {
                    $locId = (int) $loc->id;
                    $inflow = ($purchasedByLoc[$locId] ?? 0.0) + ($transferInByLoc[$locId] ?? 0.0);
                    $outflow = ($transferOutByLoc[$locId] ?? 0.0) + ($soldByLoc[$locId] ?? 0.0);
                    $locExpected = max(0, (int) round($inflow - $outflow));
                    $targetInventoryByLoc[$locId] = $locExpected;
                    $totalExpected += $locExpected;
                }

                // Current inventory and batch stock before fix
                $currentInvRows = $inventoryRows->get($pId) ?? collect();
                $currentInventorySum = (int) $currentInvRows->sum('quantity');

                $currentBatchRows = $batchStockRows->get($pId) ?? collect();
                $currentBatchSum = (int) $currentBatchRows->sum('quantity');

                $hasDifference = (abs($currentInventorySum - $totalExpected) > 0.001) || (abs($currentBatchSum - $totalExpected) > 0.001);

                // Apply DB Fix
                if (!$isDryRun) {
                    DB::transaction(function () use ($product, $pId, $allLocations, $targetInventoryByLoc, $isBatchOnly) {
                        // 1. Update inventories table per location (unless --batch-only is specified)
                        if (!$isBatchOnly) {
                            foreach ($allLocations as $loc) {
                                $locId = (int) $loc->id;
                                $targetQty = (int) ($targetInventoryByLoc[$locId] ?? 0);

                                $inv = Inventory::withTrashed()
                                    ->where('product_id', $pId)
                                    ->where('location_id', $locId)
                                    ->first();

                                if ($inv) {
                                    $inv->quantity = $targetQty;
                                    $inv->deleted_at = null;
                                    $inv->save();
                                } else {
                                    Inventory::create([
                                        'product_id' => $pId,
                                        'location_id' => $locId,
                                        'quantity' => $targetQty,
                                        'created_by' => 1,
                                    ]);
                                }
                            }
                        }

                        // 2. Synchronize purchase_batch_stocks via PurchaseBatchService
                        foreach ($allLocations as $loc) {
                            PurchaseBatchService::syncProductBatchStocks($loc->id, $pId, null);
                        }

                        // 3. Clean up any zero quantity or zero price batch rows
                        DB::table('purchase_batch_stocks')
                            ->where('product_id', $pId)
                            ->where(function ($q) {
                                $q->where('quantity', '<=', 0)
                                  ->orWhere('purchase_price', '<=', 0);
                            })
                            ->delete();
                    });
                }

                if ($hasDifference || $isDetailed || $products->count() <= 10) {
                    $fixSummary[] = [
                        'id' => $product->id,
                        'barcode' => $product->barcode ?: '(NO BARCODE)',
                        'name' => $product->name,
                        'old_inv' => $currentInventorySum,
                        'old_batch' => $currentBatchSum,
                        'fixed_stock' => $totalExpected,
                        'status' => $hasDifference ? 'Updated' : 'Matched',
                    ];
                }

                if ($hasDifference) {
                    $modifiedCount++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Clear Caches
        Product::clearMappedCaches();
        Product::clearPreloadedVariantStock();

        // Output table
        if (!empty($fixSummary)) {
            $this->info('--- Products Fix Details ---');
            $tableRows = array_map(fn($item) => [
                $item['id'],
                $item['barcode'],
                mb_strimwidth($item['name'], 0, 30, '...'),
                $item['old_inv'],
                $item['old_batch'],
                $item['fixed_stock'],
                $item['status'],
            ], array_slice($fixSummary, 0, 100));

            $this->table(['ID', 'Barcode', 'Product Name', 'Old Inv', 'Old Batch', 'Fixed Stock', 'Status'], $tableRows);
            if (count($fixSummary) > 100) {
                $this->line('... and ' . (count($fixSummary) - 100) . ' more products.');
            }
        }

        // Summary
        $this->info('=====================================================');
        $this->info('                 Fix Summary Result                  ');
        $this->info('=====================================================');
        $this->line("Total Products Processed : <fg=cyan>{$processedCount}</>");
        $this->line("Products Modified/Synced : <fg=green>{$modifiedCount}</>");
        $this->line("Already Matched Products : <fg=yellow>" . ($processedCount - $modifiedCount) . "</>");

        if ($isDryRun) {
            $this->warn('Notice: This was a dry-run. No database modifications were committed.');
        } else {
            $this->info('✅ Synchronization completed successfully!');
        }

        $this->newLine();
        return Command::SUCCESS;
    }

    /**
     * Calculate purchase unit multiplier (in physical pieces)
     */
    private function calculatePurchaseMultiplier(Product $product, $customSizeValue, ?ProductVariant $variant = null): float
    {
        if (!$product->pair_product) {
            return 1.0;
        }

        if ($customSizeValue !== null && $customSizeValue !== '' && (float) $customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        $sizesSource = ($variant && !empty($variant->custom_sizes)) ? $variant->custom_sizes : ($product->custom_sizes ?? []);
        if (is_array($sizesSource) && count($sizesSource) > 0) {
            $sizes = collect($sizesSource)->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            if ($sizes->count() > 0) {
                return (float) $sizes->max();
            }
        }

        return 2.0;
    }

    /**
     * Calculate sales/transfer unit multiplier (in physical pieces)
     */
    private function calculateOrderMultiplier(Product $product, ?string $pairType, $customSizeValue): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float) $customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        if (!$product->pair_product) {
            return 1.0;
        }

        if ($pairType === 'single') {
            return 1.0;
        }

        return $product->getPairPackSize();
    }
}
