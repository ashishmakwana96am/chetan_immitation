<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class PurchaseBatchService
{
    /**
     * Get active purchase price batches with combined remaining stock for a product / variant at a location.
     * Uses current live inventory stock and groups options by unique purchase_price.
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int|null $locationId
     * @param int|null $excludeOrderId Exclude order items of a specific order (for order editing)
     * @return array
     */
    public static function getAvailableBatches(int $productId, ?int $productVariantId = null, ?int $locationId = null, ?int $excludeOrderId = null): array
    {
        // 1. Get current actual live stock at location
        $liveStockQuery = DB::table('inventories')
            ->where('product_id', $productId)
            ->whereNull('deleted_at');

        if ($locationId) {
            $liveStockQuery->where('location_id', $locationId);
        }

        $totalLiveStock = (int) $liveStockQuery->sum('quantity');

        if ($totalLiveStock <= 0) {
            return [];
        }

        // 2. Fetch distinct purchase prices from approved purchases AND transfer bills sent to this location
        $purchaseItems = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->whereNull('purchase_items.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->where('purchases.status', 2)
            ->when($productVariantId, fn($q) => $q->where('purchase_items.product_variant_id', $productVariantId), fn($q) => $q->whereNull('purchase_items.product_variant_id'))
            ->select(
                DB::raw("'purchase' as batch_type"),
                'purchase_items.id as purchase_item_id',
                'purchase_items.quantity',
                'purchase_items.purchase_price',
                'purchase_items.created_at'
            )
            ->get();

        $transferItems = collect();
        if ($locationId) {
            $transferItems = DB::table('purchase_bill_items')
                ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                ->where('purchase_bill_items.product_id', $productId)
                ->whereNull('purchase_bill_items.deleted_at')
                ->whereNull('purchase_bills.deleted_at')
                ->where('purchase_bills.status', 2)
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
        }

        $items = $purchaseItems->merge($transferItems)->sortByDesc('created_at')->values();

        // 3. Fallback if no purchase items exist: use product/variant default purchase price
        if ($items->isEmpty()) {
            $defaultPrice = 0.0;
            if ($productVariantId) {
                $defaultPrice = (float) (ProductVariant::where('id', $productVariantId)->value('purchase_price') ?? 0);
            }
            if ($defaultPrice <= 0) {
                $defaultPrice = (float) (Product::where('id', $productId)->value('purchase_price') ?? 0);
            }

            return [[
                'purchase_item_id' => null,
                'purchase_price'   => $defaultPrice,
                'available_qty'    => $totalLiveStock,
                'label'            => '₹' . number_format($defaultPrice, 2),
            ]];
        }

        // 4. Group purchase items by purchase_price
        $priceGroups = [];
        foreach ($items as $item) {
            $priceKey = (string) number_format((float) $item->purchase_price, 2, '.', '');
            if (!isset($priceGroups[$priceKey])) {
                $priceGroups[$priceKey] = [
                    'purchase_item_id' => (int) $item->purchase_item_id,
                    'purchase_price'   => (float) $item->purchase_price,
                ];
            }
        }

        // 5. If all purchases have the exact same price, map total live stock to that single price option
        if (count($priceGroups) === 1) {
            $singleGroup = reset($priceGroups);
            return [[
                'purchase_item_id' => $singleGroup['purchase_item_id'],
                'purchase_price'   => $singleGroup['purchase_price'],
                'available_qty'    => $totalLiveStock,
                'label'            => '₹' . number_format($singleGroup['purchase_price'], 2),
            ]];
        }

        // 6. If multiple distinct prices exist, calculate per-item remaining stock capped by live stock
        $groupedByPrice = [];
        $allocatedSum = 0;

        foreach ($items as $item) {
            if ($item->batch_type === 'transfer') {
                $allocatedQty = (float) $item->quantity;

                $soldQuery = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.product_id', $productId)
                    ->whereNull('order_items.deleted_at')
                    ->whereNull('orders.deleted_at')
                    ->where('orders.status', Order::STATUS_APPROVE);

                if ($locationId) {
                    $soldQuery->where('orders.location_id', $locationId);
                }
                if ($excludeOrderId) {
                    $soldQuery->where('orders.id', '!=', $excludeOrderId);
                }

                $soldQuery->where('order_items.purchase_price', (float)$item->purchase_price);

                $soldQty = (float) $soldQuery->sum(DB::raw('order_items.quantity * CASE 
                    WHEN order_items.custom_size_value IS NOT NULL AND order_items.custom_size_value > 0 THEN order_items.custom_size_value
                    WHEN order_items.pair_type = "pair" THEN 2.0
                    ELSE 1.0
                END'));
            } else {
                $allocQuery = DB::table('purchase_allocations')
                    ->where('purchase_item_id', $item->purchase_item_id);
                if ($locationId) {
                    $allocQuery->where('location_id', $locationId);
                }
                $allocatedQty = (float) $allocQuery->sum('quantity');
                if ($allocatedQty <= 0) {
                    $allocatedQty = (float) $item->quantity;
                }

                $soldQuery = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.purchase_item_id', $item->purchase_item_id)
                    ->whereNull('order_items.deleted_at')
                    ->whereNull('orders.deleted_at')
                    ->where('orders.status', Order::STATUS_APPROVE);

                if ($excludeOrderId) {
                    $soldQuery->where('orders.id', '!=', $excludeOrderId);
                }

                $soldQty = (float) $soldQuery->sum(DB::raw('order_items.quantity * CASE 
                    WHEN order_items.custom_size_value IS NOT NULL AND order_items.custom_size_value > 0 THEN order_items.custom_size_value
                    WHEN order_items.pair_type = "pair" THEN 2.0
                    ELSE 1.0
                END'));
            }

            $remainingQty = max(0, $allocatedQty - $soldQty);
            if ($remainingQty > 0 || count($priceGroups) > 1) {
                $priceKey = (string) number_format((float) $item->purchase_price, 2, '.', '');
                if (!isset($groupedByPrice[$priceKey])) {
                    $groupedByPrice[$priceKey] = [
                        'purchase_item_id' => (int) $item->purchase_item_id,
                        'purchase_price'   => (float) $item->purchase_price,
                        'available_qty'    => 0,
                    ];
                }
                $groupedByPrice[$priceKey]['available_qty'] += (int) $remainingQty;
                $allocatedSum += (int) $remainingQty;
            }
        }

        if (empty($groupedByPrice)) {
            foreach ($priceGroups as $pg) {
                $groupedByPrice[] = [
                    'purchase_item_id' => $pg['purchase_item_id'],
                    'purchase_price'   => $pg['purchase_price'],
                    'available_qty'    => $totalLiveStock,
                ];
            }
        }

        $batches = [];
        foreach ($groupedByPrice as $b) {
            $qty = $totalLiveStock > 0 && $allocatedSum > 0
                ? (int) min($b['available_qty'], $totalLiveStock)
                : $b['available_qty'];

            if ($qty > 0) {
                $batches[] = [
                    'purchase_item_id' => $b['purchase_item_id'],
                    'purchase_price'   => $b['purchase_price'],
                    'available_qty'    => $qty,
                    'label'            => '₹' . number_format($b['purchase_price'], 2),
                ];
            }
        }

        return array_values($batches);
    }

    /**
     * Calculate exact total cost price for a sold line item by allocating quantity across
     * available purchase batches (multi-batch FIFO allocation if selected batch quantity is exceeded).
     *
     * @param int $productId
     * @param int|null $productVariantId
     * @param int|null $locationId
     * @param float $totalQtySold Physical quantity sold
     * @param int|null $selectedPurchaseItemId Selected batch ID
     * @param float|null $selectedUnitCost Selected unit cost price
     * @param int|null $excludeOrderId
     * @return array ['total_cost' => float, 'primary_purchase_item_id' => int|null]
     */
    public static function calculateTotalCostPrice(
        int $productId,
        ?int $productVariantId,
        ?int $locationId,
        float $totalQtySold,
        ?int $selectedPurchaseItemId = null,
        ?float $selectedUnitCost = null,
        ?int $excludeOrderId = null
    ): array {
        if ($totalQtySold <= 0) {
            return ['total_cost' => 0.0, 'primary_purchase_item_id' => $selectedPurchaseItemId];
        }

        $batches = self::getAvailableBatches($productId, $productVariantId, $locationId, $excludeOrderId);

        if (empty($batches)) {
            // Fallback if no batches exist
            $unitCost = $selectedUnitCost ?? 0.0;
            if ($unitCost <= 0) {
                if ($productVariantId) {
                    $unitCost = (float) (ProductVariant::where('id', $productVariantId)->value('purchase_price') ?? 0);
                }
                if ($unitCost <= 0) {
                    $unitCost = (float) (Product::where('id', $productId)->value('purchase_price') ?? 0);
                }
            }
            return [
                'total_cost' => round($totalQtySold * $unitCost, 2),
                'primary_purchase_item_id' => $selectedPurchaseItemId,
            ];
        }

        $qtyRemaining = $totalQtySold;
        $totalCost = 0.0;
        $primaryItemId = $selectedPurchaseItemId;

        // 1. If user selected a specific batch, consume its available stock first
        if ($selectedPurchaseItemId || $selectedUnitCost) {
            $matchedIndex = null;
            foreach ($batches as $idx => $b) {
                if (($selectedPurchaseItemId && (int)$b['purchase_item_id'] === (int)$selectedPurchaseItemId) ||
                    ($selectedUnitCost && abs((float)$b['purchase_price'] - (float)$selectedUnitCost) < 0.01)) {
                    $matchedIndex = $idx;
                    break;
                }
            }

            if ($matchedIndex !== null) {
                $batch = $batches[$matchedIndex];
                $primaryItemId = $batch['purchase_item_id'];
                $takeQty = min($qtyRemaining, (float)$batch['available_qty']);
                $totalCost += $takeQty * (float)$batch['purchase_price'];
                $qtyRemaining -= $takeQty;

                // Remove selected batch from remaining batches pool
                array_splice($batches, $matchedIndex, 1);
            }
        }

        // 2. Allocate remaining sold quantity across other available batches (FIFO)
        foreach ($batches as $b) {
            if ($qtyRemaining <= 0) {
                break;
            }
            if ($primaryItemId === null) {
                $primaryItemId = $b['purchase_item_id'];
            }
            $takeQty = min($qtyRemaining, (float)$b['available_qty']);
            $totalCost += $takeQty * (float)$b['purchase_price'];
            $qtyRemaining -= $takeQty;
        }

        // 3. If quantity requested exceeds total available batch stock, compute unallocated balance at fallback unit cost
        if ($qtyRemaining > 0) {
            $fallbackUnitCost = $selectedUnitCost ?? 0.0;
            if ($fallbackUnitCost <= 0 && $productVariantId) {
                $fallbackUnitCost = (float) (ProductVariant::where('id', $productVariantId)->value('purchase_price') ?? 0);
            }
            if ($fallbackUnitCost <= 0) {
                $fallbackUnitCost = (float) (Product::where('id', $productId)->value('purchase_price') ?? 0);
            }
            $totalCost += $qtyRemaining * $fallbackUnitCost;
        }

        return [
            'total_cost' => round($totalCost, 2),
            'primary_purchase_item_id' => $primaryItemId,
        ];
    }
}
