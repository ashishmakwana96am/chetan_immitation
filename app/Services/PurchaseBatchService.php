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

        // 2. Fetch distinct purchase prices from approved purchases
        $query = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->whereNull('purchase_items.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->where('purchases.status', 2);

        if ($productVariantId) {
            $query->where('purchase_items.product_variant_id', $productVariantId);
        } else {
            $query->whereNull('purchase_items.product_variant_id');
        }

        $items = $query->select(
            'purchase_items.id as purchase_item_id',
            'purchase_items.purchase_price',
            'purchase_items.created_at'
        )
        ->orderBy('purchase_items.created_at', 'desc')
        ->get();

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
            $allocQuery = DB::table('purchase_allocations')
                ->where('purchase_item_id', $item->purchase_item_id);
            if ($locationId) {
                $allocQuery->where('location_id', $locationId);
            }
            $allocatedQty = (float) $allocQuery->sum('quantity');

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

            $remainingQty = max(0, $allocatedQty - $soldQty);
            if ($remainingQty > 0) {
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
            $latestItem = $items->first();
            return [[
                'purchase_item_id' => (int) $latestItem->purchase_item_id,
                'purchase_price'   => (float) $latestItem->purchase_price,
                'available_qty'    => $totalLiveStock,
                'label'            => '₹' . number_format((float) $latestItem->purchase_price, 2),
            ]];
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
}
