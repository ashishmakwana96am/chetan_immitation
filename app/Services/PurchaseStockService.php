<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Purchase;

class PurchaseStockService
{
    /**
     * Increment inventory for every allocation on an approved purchase,
     * doubling the quantity for pair products.
     */
    public static function approve(Purchase $purchase): void
    {
        $purchase->load('items.allocations.location', 'items.product');
        foreach ($purchase->items as $item) {
            $multiplier = self::multiplierFor($item);
            foreach ($item->allocations as $allocation) {
                $qtyToAdd = (int) round($allocation->quantity * $multiplier);

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_id'  => $item->product_id,
                        'location_id' => $allocation->location_id,
                    ],
                    [
                        'quantity'   => 0,
                        'created_by' => auth()->id(),
                    ]
                );

                $oldQty = $inventory->quantity;
                $inventory->increment('quantity', $qtyToAdd);

                ActivityLogger::log('Inventory', 'update', $inventory, ['quantity' => $oldQty], ['quantity' => $oldQty + $qtyToAdd], 'Stock added for purchase #' . $purchase->invoice_no);
            }
        }
    }

    /**
     * Reverse the inventory added by approve(), used when an approved
     * purchase is edited back to a non-approved state.
     */
    public static function reverse(Purchase $purchase): void
    {
        $purchase->load('items.allocations.location', 'items.product');
        foreach ($purchase->items as $item) {
            $multiplier = self::multiplierFor($item);
            foreach ($item->allocations as $allocation) {
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $allocation->location_id)
                    ->first();

                if ($inventory) {
                    $oldQty = $inventory->quantity;
                    $qtyToSubtract = (int) round($allocation->quantity * $multiplier);
                    $newQty = max(0, $inventory->quantity - $qtyToSubtract);
                    $inventory->update(['quantity' => $newQty]);

                    ActivityLogger::log('Inventory', 'update', $inventory, ['quantity' => $oldQty], ['quantity' => $newQty], 'Stock reversed for purchase #' . $purchase->invoice_no . ' edit');
                }
            }
        }
    }

    /**
     * How many stock units one purchased "quantity" unit represents:
     * a chosen custom size (e.g. 4 pcs) for custom_size-mode pair products,
     * 2 for plain pair products, or 1 otherwise.
     */
    private static function multiplierFor(\App\Models\PurchaseItem $item): float
    {
        $product = $item->product;

        if (!$product || !$product->pair_product) {
            return 1.0;
        }

        if ($product->pair_mode === 'custom_size' && $item->custom_size_value) {
            return (float) $item->custom_size_value;
        }

        return 2.0;
    }
}
