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
        $purchase->load(['items.allocations.location', 'items.product', 'items.variant.attributeValue']);
        $stockChanges = [];

        foreach ($purchase->items as $item) {
            $product = $item->product;
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

                $oldQty = (int) $inventory->quantity;
                $inventory->increment('quantity', $qtyToAdd);
                $newQty = $oldQty + $qtyToAdd;

                $locationName = $allocation->location?->name ?? ('Location #' . $allocation->location_id);
                $barcode = $product?->barcode ?: '-';
                $productName = $product?->name ?: ('Product #' . $item->product_id);

                if ($item->product_variant_id && $item->variant) {
                    $vLabel = trim((string)($item->variant->name ?? $item->variant->attributeValue?->value ?? ''));
                    if ($vLabel !== '') {
                        $productName .= ' (' . $vLabel . ')';
                    }
                }

                $stockChanges[] = [
                    'product_id'   => $item->product_id,
                    'product_name' => $productName,
                    'barcode'      => $barcode,
                    'location'     => $locationName,
                    'quantity'     => $qtyToAdd,
                    'old_quantity' => $oldQty,
                    'new_quantity' => $newQty,
                ];
            }
        }

        if (!empty($stockChanges)) {
            $oldStockSnapshot = array_map(fn($sc) => [
                'product_name' => $sc['product_name'],
                'barcode'      => $sc['barcode'],
                'location'     => $sc['location'],
                'stock'        => $sc['old_quantity'],
            ], $stockChanges);

            $newStockSnapshot = array_map(fn($sc) => [
                'product_name' => $sc['product_name'],
                'barcode'      => $sc['barcode'],
                'location'     => $sc['location'],
                'stock'        => $sc['new_quantity'],
                'qty_added'    => '+' . $sc['quantity'],
            ], $stockChanges);

            ActivityLogger::log(
                'Inventory',
                'update',
                $purchase,
                ['stock_items' => $oldStockSnapshot],
                ['stock_items' => $newStockSnapshot],
                'Stock added for purchase #' . $purchase->invoice_no . ' (' . count($stockChanges) . ' item' . (count($stockChanges) > 1 ? 's' : '') . ')'
            );
        }
    }

    /**
     * Reverse the inventory added by approve(), used when an approved
     * purchase is edited back to a non-approved state or deleted.
     */
    public static function reverse(Purchase $purchase, string $reason = 'edit'): void
    {
        $purchase->load(['items.allocations.location', 'items.product', 'items.variant.attributeValue']);
        $stockChanges = [];

        foreach ($purchase->items as $item) {
            $product = $item->product;
            $multiplier = self::multiplierFor($item);

            foreach ($item->allocations as $allocation) {
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $allocation->location_id)
                    ->first();

                if ($inventory) {
                    $oldQty = (int) $inventory->quantity;
                    $qtyToSubtract = (int) round($allocation->quantity * $multiplier);
                    $newQty = max(0, $inventory->quantity - $qtyToSubtract);
                    $inventory->update(['quantity' => $newQty]);

                    $locationName = $allocation->location?->name ?? ('Location #' . $allocation->location_id);
                    $barcode = $product?->barcode ?: '-';
                    $productName = $product?->name ?: ('Product #' . $item->product_id);

                    if ($item->product_variant_id && $item->variant) {
                        $vLabel = trim((string)($item->variant->name ?? $item->variant->attributeValue?->value ?? ''));
                        if ($vLabel !== '') {
                            $productName .= ' (' . $vLabel . ')';
                        }
                    }

                    $stockChanges[] = [
                        'product_id'   => $item->product_id,
                        'product_name' => $productName,
                        'barcode'      => $barcode,
                        'location'     => $locationName,
                        'quantity'     => $qtyToSubtract,
                        'old_quantity' => $oldQty,
                        'new_quantity' => $newQty,
                    ];
                }
            }
        }

        if (!empty($stockChanges)) {
            $oldStockSnapshot = array_map(fn($sc) => [
                'product_name' => $sc['product_name'],
                'barcode'      => $sc['barcode'],
                'location'     => $sc['location'],
                'stock'        => $sc['old_quantity'],
            ], $stockChanges);

            $newStockSnapshot = array_map(fn($sc) => [
                'product_name' => $sc['product_name'],
                'barcode'      => $sc['barcode'],
                'location'     => $sc['location'],
                'stock'        => $sc['new_quantity'],
                'qty_deducted' => '-' . $sc['quantity'],
            ], $stockChanges);

            ActivityLogger::log(
                'Inventory',
                'update',
                $purchase,
                ['stock_items' => $oldStockSnapshot],
                ['stock_items' => $newStockSnapshot],
                'Stock reversed for purchase #' . $purchase->invoice_no . ' (' . $reason . ')'
            );
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

        if ($item->custom_size_value && (float)$item->custom_size_value > 0) {
            return (float) $item->custom_size_value;
        }

        $customSizes = $product->custom_sizes;
        if (is_array($customSizes) && count($customSizes) > 0) {
            $sizes = collect($customSizes)->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            if ($sizes->count() > 0) {
                return (float) $sizes->max();
            }
        }

        return 2.0;
    }
}
