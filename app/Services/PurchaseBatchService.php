<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class PurchaseBatchService
{
    /**
     * Ensure dedicated purchase_batch_stocks table exists in the database.
     */
    public static function ensureBatchStocksTable(): void
    {
        // Table created via migration 2026_08_31_180000_create_purchase_batch_stocks_table.php
    }

    /**
     * Synchronize and populate purchase_batch_stocks for a specific product/variant at a location.
     */
    public static function syncProductBatchStocks(int $locationId, int $productId, ?int $productVariantId = null): void
    {
        self::ensureBatchStocksTable();

        // 1. Get total live physical stock from inventories
        $liveStockQuery = DB::table('inventories')
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->whereNull('deleted_at');

        $totalLiveStock = (int) $liveStockQuery->sum('quantity');

        if ($totalLiveStock <= 0) {
            DB::table('purchase_batch_stocks')
                ->where('location_id', $locationId)
                ->where('product_id', $productId)
                ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId), fn($q) => $q->whereNull('product_variant_id'))
                ->update(['quantity' => 0, 'updated_at' => now()]);
            return;
        }

        // 2. Fetch all purchase items and transfer items for this product
        $purchaseItems = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->whereNull('purchase_items.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->whereIn('purchases.status', [1, 2])
            ->when($productVariantId, fn($q) => $q->where('purchase_items.product_variant_id', $productVariantId), fn($q) => $q->whereNull('purchase_items.product_variant_id'))
            ->select(
                DB::raw("'purchase' as batch_type"),
                'purchase_items.id as purchase_item_id',
                'purchase_items.quantity',
                'purchase_items.purchase_price',
                'purchase_items.created_at'
            )
            ->get();

        $transferItems = DB::table('purchase_bill_items')
            ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
            ->where('purchase_bill_items.product_id', $productId)
            ->whereNull('purchase_bill_items.deleted_at')
            ->whereNull('purchase_bills.deleted_at')
            ->whereIn('purchase_bills.status', [1, 2])
            ->where('purchase_bills.to_location_id', $locationId)
            ->when($productVariantId, fn($q) => $q->where('purchase_bill_items.product_variant_id', $productVariantId), fn($q) => $q->whereNull('purchase_bill_items.product_variant_id'))
            ->select(
                DB::raw("'transfer' as batch_type"),
                'purchase_bill_items.id as purchase_item_id',
                'purchase_bill_items.quantity',
                'purchase_bill_items.purchase_price',
                'purchase_bills.created_at'
            )
            ->get();

        $items = $purchaseItems->merge($transferItems)->sortByDesc('created_at')->values();

        if ($items->isEmpty()) {
            $defaultPrice = 0.0;
            if ($productVariantId) {
                $defaultPrice = (float) (ProductVariant::where('id', $productVariantId)->value('purchase_price') ?? 0);
            }
            if ($defaultPrice <= 0) {
                $defaultPrice = (float) (Product::where('id', $productId)->value('purchase_price') ?? 0);
            }

            self::upsertBatchRecord($locationId, $productId, $productVariantId, null, $defaultPrice, $totalLiveStock);
            return;
        }

        // 3. Group items by price and calculate available stock
        $groupedByPrice = [];
        foreach ($items as $item) {
            $priceKey = (string) number_format((float) $item->purchase_price, 2, '.', '');

            if ($item->batch_type === 'transfer') {
                $allocatedQty = (float) $item->quantity;
            } else {
                $allocQuery = DB::table('purchase_allocations')
                    ->where('purchase_item_id', $item->purchase_item_id)
                    ->where('location_id', $locationId);
                $allocatedQty = (float) $allocQuery->sum('quantity');
                if ($allocatedQty <= 0) {
                    $allocatedQty = (float) $item->quantity;
                }
            }

            $soldQuery = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.product_id', $productId)
                ->where('orders.location_id', $locationId)
                ->whereNull('order_items.deleted_at')
                ->whereNull('orders.deleted_at')
                ->where('orders.status', Order::STATUS_APPROVE)
                ->where('order_items.purchase_price', (float)$item->purchase_price);

            $soldQty = (float) $soldQuery->sum(DB::raw('order_items.quantity * CASE 
                WHEN order_items.custom_size_value IS NOT NULL AND order_items.custom_size_value > 0 THEN order_items.custom_size_value
                WHEN order_items.pair_type = "pair" THEN 2.0
                ELSE 1.0
            END'));

            $remainingQty = max(0, $allocatedQty - $soldQty);

            if (!isset($groupedByPrice[$priceKey])) {
                $groupedByPrice[$priceKey] = [
                    'purchase_item_id' => (int) $item->purchase_item_id,
                    'purchase_price'   => (float) $item->purchase_price,
                    'available_qty'    => 0,
                ];
            }
            $groupedByPrice[$priceKey]['available_qty'] += (int) $remainingQty;
        }

        // 4. Waterfall allocation against totalLiveStock
        $remainingLive = $totalLiveStock;
        foreach ($groupedByPrice as $priceKey => $b) {
            if ($remainingLive <= 0) {
                self::upsertBatchRecord($locationId, $productId, $productVariantId, $b['purchase_item_id'], $b['purchase_price'], 0);
                continue;
            }

            $batchQty = (int) min($b['available_qty'], $remainingLive);
            if ($batchQty > 0) {
                self::upsertBatchRecord($locationId, $productId, $productVariantId, $b['purchase_item_id'], $b['purchase_price'], $batchQty);
                $remainingLive -= $batchQty;
            } else {
                self::upsertBatchRecord($locationId, $productId, $productVariantId, $b['purchase_item_id'], $b['purchase_price'], 0);
            }
        }
    }

    /**
     * Helper to upsert a batch stock record.
     */
    private static function upsertBatchRecord(int $locationId, int $productId, ?int $productVariantId, ?int $purchaseItemId, float $purchasePrice, float $quantity): void
    {
        $priceVal = (float) number_format($purchasePrice, 2, '.', '');
        $existing = DB::table('purchase_batch_stocks')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId), fn($q) => $q->whereNull('product_variant_id'))
            ->where('purchase_price', $priceVal)
            ->first();

        if ($existing) {
            DB::table('purchase_batch_stocks')
                ->where('id', $existing->id)
                ->update([
                    'quantity' => $quantity,
                    'purchase_item_id' => $purchaseItemId ?? $existing->purchase_item_id,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('purchase_batch_stocks')->insert([
                'location_id' => $locationId,
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'purchase_item_id' => $purchaseItemId,
                'purchase_price' => $priceVal,
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get active purchase price batches directly from dedicated purchase_batch_stocks table.
     */
    public static function getAvailableBatches(int $productId, ?int $productVariantId = null, ?int $locationId = null, ?int $excludeOrderId = null): array
    {
        self::ensureBatchStocksTable();

        if (!$locationId) {
            return [];
        }

        // Check if batch records exist for this location and product; if not, auto-sync once!
        $count = DB::table('purchase_batch_stocks')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId), fn($q) => $q->whereNull('product_variant_id'))
            ->count();

        if ($count === 0) {
            self::syncProductBatchStocks($locationId, $productId, $productVariantId);
        }

        // Query active batch stocks for this location where quantity > 0
        $rows = DB::table('purchase_batch_stocks')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId), fn($q) => $q->whereNull('product_variant_id'))
            ->where('quantity', '>', 0)
            ->orderBy('id', 'desc')
            ->get();

        $batches = [];
        foreach ($rows as $b) {
            $batches[] = [
                'purchase_item_id' => $b->purchase_item_id,
                'purchase_price'   => (float) $b->purchase_price,
                'available_qty'    => (int) $b->quantity,
                'label'            => '₹' . number_format((float)$b->purchase_price, 2),
            ];
        }

        return array_values($batches);
    }

    /**
     * Deduct batch stock at a location.
     */
    public static function deductBatchStock(int $locationId, int $productId, ?int $productVariantId, float $purchasePrice, float $qty): void
    {
        self::ensureBatchStocksTable();
        $priceVal = (float) number_format($purchasePrice, 2, '.', '');

        $row = DB::table('purchase_batch_stocks')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId), fn($q) => $q->whereNull('product_variant_id'))
            ->where('purchase_price', $priceVal)
            ->first();

        if ($row) {
            $newQty = max(0, (float)$row->quantity - $qty);
            DB::table('purchase_batch_stocks')
                ->where('id', $row->id)
                ->update(['quantity' => $newQty, 'updated_at' => now()]);
        }
    }

    /**
     * Add batch stock at a location.
     */
    public static function addBatchStock(int $locationId, int $productId, ?int $productVariantId, ?int $purchaseItemId, float $purchasePrice, float $qty): void
    {
        self::ensureBatchStocksTable();
        $priceVal = (float) number_format($purchasePrice, 2, '.', '');

        $row = DB::table('purchase_batch_stocks')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn($q) => $q->where('product_variant_id', $productVariantId), fn($q) => $q->whereNull('product_variant_id'))
            ->where('purchase_price', $priceVal)
            ->first();

        if ($row) {
            $newQty = (float)$row->quantity + $qty;
            DB::table('purchase_batch_stocks')
                ->where('id', $row->id)
                ->update(['quantity' => $newQty, 'purchase_item_id' => $purchaseItemId ?? $row->purchase_item_id, 'updated_at' => now()]);
        } else {
            DB::table('purchase_batch_stocks')->insert([
                'location_id' => $locationId,
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'purchase_item_id' => $purchaseItemId,
                'purchase_price' => $priceVal,
                'quantity' => $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Calculate exact total cost price for a sold line item by allocating quantity across
     * available purchase batches (multi-batch FIFO allocation if selected batch quantity is exceeded).
     */
    public static function calculateSoldItemCost(
        int $productId,
        ?int $productVariantId,
        ?int $locationId,
        float $totalQtySold,
        ?int $selectedPurchaseItemId = null,
        ?float $selectedUnitCost = null,
        ?int $excludeOrderId = null
    ): array {
        $batches = self::getAvailableBatches($productId, $productVariantId, $locationId, $excludeOrderId);

        if (empty($batches)) {
            $fallbackCost = $selectedUnitCost ?? 0.0;
            if ($fallbackCost <= 0) {
                if ($productVariantId) {
                    $fallbackCost = (float) (ProductVariant::where('id', $productVariantId)->value('purchase_price') ?? 0);
                }
                if ($fallbackCost <= 0) {
                    $fallbackCost = (float) (Product::where('id', $productId)->value('purchase_price') ?? 0);
                }
            }
            return [
                'total_cost' => round($totalQtySold * $fallbackCost, 2),
                'primary_purchase_item_id' => $selectedPurchaseItemId,
            ];
        }

        if ($selectedPurchaseItemId || ($selectedUnitCost !== null && $selectedUnitCost >= 0)) {
            usort($batches, function ($a, $b) use ($selectedPurchaseItemId, $selectedUnitCost) {
                if ($selectedPurchaseItemId && $a['purchase_item_id'] == $selectedPurchaseItemId) return -1;
                if ($selectedPurchaseItemId && $b['purchase_item_id'] == $selectedPurchaseItemId) return 1;
                if ($selectedUnitCost !== null && abs($a['purchase_price'] - $selectedUnitCost) < 0.01) return -1;
                if ($selectedUnitCost !== null && abs($b['purchase_price'] - $selectedUnitCost) < 0.01) return 1;
                return 0;
            });
        }

        $remainingToAllocate = $totalQtySold;
        $totalCost = 0.0;
        $primaryPurchaseItemId = null;

        foreach ($batches as $index => $batch) {
            if ($remainingToAllocate <= 0) break;

            if ($index === 0 && $batch['purchase_item_id']) {
                $primaryPurchaseItemId = $batch['purchase_item_id'];
            }

            $avail = (float) $batch['available_qty'];
            $take = min($remainingToAllocate, $avail > 0 ? $avail : $remainingToAllocate);

            $totalCost += ($take * (float) $batch['purchase_price']);
            $remainingToAllocate -= $take;
        }

        if ($remainingToAllocate > 0) {
            $lastPrice = !empty($batches) ? (float) end($batches)['purchase_price'] : ($selectedUnitCost ?? 0.0);
            $totalCost += ($remainingToAllocate * $lastPrice);
        }

        return [
            'total_cost' => round($totalCost, 2),
            'primary_purchase_item_id' => $primaryPurchaseItemId ?? $selectedPurchaseItemId,
        ];
    }
}
