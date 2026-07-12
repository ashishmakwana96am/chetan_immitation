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
            $isPair = $item->product && $item->product->pair_product;
            foreach ($item->allocations as $allocation) {
                $qtyToAdd = $isPair ? $allocation->quantity * 2 : $allocation->quantity;

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
            $isPair = $item->product && $item->product->pair_product;
            foreach ($item->allocations as $allocation) {
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $allocation->location_id)
                    ->first();

                if ($inventory) {
                    $oldQty = $inventory->quantity;
                    $qtyToSubtract = $isPair ? $allocation->quantity * 2 : $allocation->quantity;
                    $newQty = max(0, $inventory->quantity - $qtyToSubtract);
                    $inventory->update(['quantity' => $newQty]);

                    ActivityLogger::log('Inventory', 'update', $inventory, ['quantity' => $oldQty], ['quantity' => $newQty], 'Stock reversed for purchase #' . $purchase->invoice_no . ' edit');
                }
            }
        }
    }
}
