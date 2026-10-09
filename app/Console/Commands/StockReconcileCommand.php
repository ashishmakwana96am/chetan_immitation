<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:reconcile 
                            {--output=stock_mismatch_barcodes.txt : Output TXT filename in the root directory}
                            {--detailed : Output detailed mismatch breakdown to the console}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile undeleted products stock against inventory and purchase_batch_stocks, and generate comma-separated mismatch barcodes TXT file.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=====================================================');
        $this->info('  Starting Product Stock Reconciliation Command');
        $this->info('=====================================================');

        $outputFileName = $this->option('output') ?: 'stock_mismatch_barcodes.txt';
        $isDetailed = (bool) $this->option('detailed');
        $outputPath = base_path($outputFileName);

        $totalProductsProcessed = 0;
        $matchedCount = 0;
        $mismatchedCount = 0;
        $mismatchedBarcodes = [];
        $missingBarcodeProducts = [];
        $mismatchDetails = [];

        $query = Product::whereNull('deleted_at')->with(['variants' => function ($q) {
            $q->whereNull('deleted_at');
        }]);

        $totalCount = $query->count();
        if ($totalCount === 0) {
            $this->warn('No undeleted products found to process.');
            file_put_contents($outputPath, '');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalCount} undeleted products to process.");
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $chunkSize = 200;
        $query->chunk($chunkSize, function ($products) use (
            &$totalProductsProcessed,
            &$matchedCount,
            &$mismatchedCount,
            &$mismatchedBarcodes,
            &$missingBarcodeProducts,
            &$mismatchDetails,
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

            // 6. Process each product
            foreach ($products as $product) {
                $totalProductsProcessed++;
                $pId = $product->id;
                $variantsById = $product->variants->keyBy('id');

                // A. Calculate Inbound Purchases
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

                // B. Calculate Transfers In and Out
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

                // C. Calculate Outbound Sales
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

                // D. Aggregate Live Inventory and Live Batch Stocks
                $productInventories = $inventoryRows->get($pId) ?? collect();
                $actualInventoryByLoc = [];
                foreach ($productInventories as $inv) {
                    $locId = (int) $inv->location_id;
                    $actualInventoryByLoc[$locId] = ($actualInventoryByLoc[$locId] ?? 0.0) + (float) $inv->quantity;
                }

                $productBatchStocks = $batchStockRows->get($pId) ?? collect();
                $actualBatchByLoc = [];
                $actualBatchByVariantLoc = [];
                $actualBatchByVariant = [];

                foreach ($productBatchStocks as $bs) {
                    $locId = (int) $bs->location_id;
                    $vId = $bs->product_variant_id ? (int) $bs->product_variant_id : null;
                    $qty = (float) $bs->quantity;

                    $actualBatchByLoc[$locId] = ($actualBatchByLoc[$locId] ?? 0.0) + $qty;
                    if ($vId) {
                        $actualBatchByVariantLoc[$vId][$locId] = ($actualBatchByVariantLoc[$vId][$locId] ?? 0.0) + $qty;
                        $actualBatchByVariant[$vId] = ($actualBatchByVariant[$vId] ?? 0.0) + $qty;
                    }
                }

                // E. Collect All Location IDs involved
                $allLocIds = array_unique(array_merge(
                    array_keys($purchasedByLoc),
                    array_keys($transferInByLoc),
                    array_keys($transferOutByLoc),
                    array_keys($soldByLoc),
                    array_keys($actualInventoryByLoc),
                    array_keys($actualBatchByLoc)
                ));

                // F. Compute Expected Stocks
                $expectedByLoc = [];
                $totalExpectedStock = 0.0;

                foreach ($allLocIds as $locId) {
                    $inflow = ($purchasedByLoc[$locId] ?? 0.0) + ($transferInByLoc[$locId] ?? 0.0);
                    $outflow = ($transferOutByLoc[$locId] ?? 0.0) + ($soldByLoc[$locId] ?? 0.0);
                    $locExpected = max(0.0, $inflow - $outflow);
                    $expectedByLoc[$locId] = $locExpected;
                    $totalExpectedStock += $locExpected;
                }

                $totalActualInventory = array_sum($actualInventoryByLoc);
                $totalActualBatch = array_sum($actualBatchByLoc);

                // G. Check for Mismatch
                $isMismatch = false;
                $mismatchReason = [];

                // Compare Product Overall Totals
                if (abs($totalExpectedStock - $totalActualInventory) > 0.001) {
                    $isMismatch = true;
                    $mismatchReason[] = "Expected ({$totalExpectedStock}) != Inventory ({$totalActualInventory})";
                }
                if (abs($totalExpectedStock - $totalActualBatch) > 0.001) {
                    $isMismatch = true;
                    $mismatchReason[] = "Expected ({$totalExpectedStock}) != Batch ({$totalActualBatch})";
                }
                if (abs($totalActualInventory - $totalActualBatch) > 0.001) {
                    $isMismatch = true;
                    $mismatchReason[] = "Inventory ({$totalActualInventory}) != Batch ({$totalActualBatch})";
                }

                // Compare Location-wise Stock
                foreach ($allLocIds as $locId) {
                    $expL = (float) ($expectedByLoc[$locId] ?? 0.0);
                    $invL = (float) ($actualInventoryByLoc[$locId] ?? 0.0);
                    $batL = (float) ($actualBatchByLoc[$locId] ?? 0.0);

                    if (abs($expL - $invL) > 0.001 || abs($expL - $batL) > 0.001 || abs($invL - $batL) > 0.001) {
                        $isMismatch = true;
                        $mismatchReason[] = "Loc #{$locId} [Exp:{$expL}, Inv:{$invL}, Batch:{$batL}]";
                    }
                }

                // Variable Product Variant-wise Checks
                if ($product->type === 'variable' && $product->variants->isNotEmpty()) {
                    foreach ($product->variants as $variant) {
                        $vId = (int) $variant->id;
                        $vExpectedTotal = 0.0;

                        foreach ($allLocIds as $locId) {
                            $vIn = ($purchasedByVariantLoc[$vId][$locId] ?? 0.0) + ($transferInByVariantLoc[$vId][$locId] ?? 0.0);
                            $vOut = ($transferOutByVariantLoc[$vId][$locId] ?? 0.0) + ($soldByVariantLoc[$vId][$locId] ?? 0.0);
                            $vLocExpected = max(0.0, $vIn - $vOut);
                            $vExpectedTotal += $vLocExpected;

                            $vLocBatch = (float) ($actualBatchByVariantLoc[$vId][$locId] ?? 0.0);
                            if (abs($vLocExpected - $vLocBatch) > 0.001) {
                                $isMismatch = true;
                                $mismatchReason[] = "Variant #{$vId} Loc #{$locId} [Exp:{$vLocExpected}, Batch:{$vLocBatch}]";
                            }
                        }

                        $vActualBatchTotal = (float) ($actualBatchByVariant[$vId] ?? 0.0);
                        if (abs($vExpectedTotal - $vActualBatchTotal) > 0.001) {
                            $isMismatch = true;
                            $mismatchReason[] = "Variant #{$vId} Total [Exp:{$vExpectedTotal}, Batch:{$vActualBatchTotal}]";
                        }
                    }
                }

                // H. Record Result
                if ($isMismatch) {
                    $mismatchedCount++;
                    $barcode = trim((string) $product->barcode);

                    if ($barcode !== '') {
                        $mismatchedBarcodes[] = $barcode;
                    } else {
                        $missingBarcodeProducts[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                        ];
                    }

                    if ($isDetailed) {
                        $mismatchDetails[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'barcode' => $barcode ?: '(NO BARCODE)',
                            'expected' => $totalExpectedStock,
                            'inventory' => $totalActualInventory,
                            'batch' => $totalActualBatch,
                            'reasons' => implode('; ', array_unique($mismatchReason)),
                        ];
                    }
                } else {
                    $matchedCount++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Remove duplicate barcodes and ensure comma-separated format
        $uniqueBarcodes = array_values(array_unique(array_filter($mismatchedBarcodes)));
        $commaSeparatedBarcodes = implode(',', $uniqueBarcodes);

        // Write to root TXT file
        try {
            file_put_contents($outputPath, $commaSeparatedBarcodes);
            $fileWriteSuccess = true;
        } catch (\Throwable $e) {
            $fileWriteSuccess = false;
            $this->error("Failed to write to file {$outputPath}: " . $e->getMessage());
        }

        // Show Detailed Table if requested
        if ($isDetailed && !empty($mismatchDetails)) {
            $this->info('--- Mismatch Details ---');
            $tableRows = array_map(fn($d) => [
                $d['id'],
                $d['barcode'],
                mb_strimwidth($d['name'], 0, 30, '...'),
                $d['expected'],
                $d['inventory'],
                $d['batch'],
                mb_strimwidth($d['reasons'], 0, 50, '...'),
            ], array_slice($mismatchDetails, 0, 50));

            $this->table(['ID', 'Barcode', 'Product Name', 'Expected', 'Inventory', 'Batch', 'Reasons'], $tableRows);
            if (count($mismatchDetails) > 50) {
                $this->line('... and ' . (count($mismatchDetails) - 50) . ' more mismatch products.');
            }
        }

        // Display Summary
        $this->info('=====================================================');
        $this->info('              Reconciliation Summary');
        $this->info('=====================================================');
        $this->line("Total Products Processed : <fg=cyan>{$totalProductsProcessed}</>");
        $this->line("Matched Products         : <fg=green>{$matchedCount}</>");
        $this->line("Mismatched Products       : <fg=yellow>{$mismatchedCount}</>");
        $this->line("Unique Barcodes in File  : <fg=magenta>" . count($uniqueBarcodes) . "</>");

        if (!empty($missingBarcodeProducts)) {
            $this->warn("Warning: " . count($missingBarcodeProducts) . " mismatched product(s) have empty/missing barcodes:");
            foreach (array_slice($missingBarcodeProducts, 0, 10) as $mbp) {
                $this->line("  - [ID #{$mbp['id']}] {$mbp['name']}");
            }
            if (count($missingBarcodeProducts) > 10) {
                $this->line("  ... and " . (count($missingBarcodeProducts) - 10) . " more.");
            }
        }

        if ($fileWriteSuccess) {
            $this->newLine();
            $this->info("✅ TXT file generated successfully at:");
            $this->line("👉 <fg=yellow>{$outputPath}</>");
            $this->line("File size: " . strlen($commaSeparatedBarcodes) . " bytes (" . count($uniqueBarcodes) . " barcodes)");
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
