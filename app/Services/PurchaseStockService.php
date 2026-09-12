<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseItem;
use Carbon\Carbon;

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

    public static function getImpactData(Purchase $purchase): array
    {
        $purchase->load(['items.product', 'items.allocations.location', 'supplier']);

        $purchaseItemIds = $purchase->items->pluck('id')->all();
        $productIds = $purchase->items->pluck('product_id')->unique()->all();

        $purchaseLocationId = $purchase->items->flatMap->allocations->pluck('location_id')->first()
            ?? $purchase->location_id
            ?? 1;

        // Build signatures of items in this purchase to match by product, variant, and exact purchase_price
        $purchaseItemSignatures = $purchase->items->map(function ($pi) {
            return [
                'id'                 => $pi->id,
                'product_id'         => (int) $pi->product_id,
                'product_variant_id' => (int) ($pi->product_variant_id ?? 0),
                'purchase_price'     => (float) $pi->purchase_price,
            ];
        });

        // 1. Transfers: Find PurchaseBills where items match product, variant, and exact purchase_price of THIS purchase
        $transfers = PurchaseBill::with(['fromLocation', 'toLocation', 'items.product', 'items.variant'])
            ->where('from_location_id', $purchaseLocationId)
            ->whereNull('deleted_at')
            ->where('status', '!=', PurchaseBill::STATUS_REJECTED)
            ->where('created_at', '>=', $purchase->created_at->subMinutes(5))
            ->get();

        $toLocationIds = $transfers->pluck('to_location_id')->unique()->all();
        $allRelevantLocationIds = array_unique(array_merge([$purchaseLocationId], $toLocationIds));

        $transfersGrouped = [];
        foreach ($transfers as $transfer) {
            $matchingItems = [];
            $totalQty = 0;
            foreach ($transfer->items as $tbItem) {
                // Must match product, variant AND exact purchase_price of THIS purchase!
                $matchesThisPurchase = $purchaseItemSignatures->first(function ($sig) use ($tbItem) {
                    return $sig['product_id'] === (int) $tbItem->product_id
                        && $sig['product_variant_id'] === (int) ($tbItem->product_variant_id ?? 0)
                        && abs($sig['purchase_price'] - (float) $tbItem->purchase_price) < 0.01;
                });

                if (!$matchesThisPurchase) continue;

                $prod = $tbItem->product;
                $vName = $tbItem->variant?->name ?? $tbItem->variant?->attributeValue?->value;
                $prodName = $prod?->name ?? 'Product #' . $tbItem->product_id;
                if ($vName) {
                    $prodName .= ' (' . $vName . ')';
                }
                $qtyLabel = self::formatItemQtyLabel($prod, $tbItem->quantity, $tbItem->pair_type ?? null, $tbItem->custom_size_value ?? null);
                $matchingItems[] = [
                    'product_name' => $prodName,
                    'price'        => (float) $tbItem->purchase_price,
                    'barcode'      => $tbItem->variant?->barcode ?: ($prod?->barcode ?: ''),
                    'quantity'     => (int) $tbItem->quantity,
                    'qty_label'    => $qtyLabel,
                ];
                $totalQty += (int) $tbItem->quantity;
            }

            if (!empty($matchingItems)) {
                $transfersGrouped[] = [
                    'id'            => $transfer->id,
                    'transfer_no'   => $transfer->transfer_no ?: ('PB-' . $transfer->id),
                    'from_location' => $transfer->fromLocation?->name ?? ('Branch #' . $transfer->from_location_id),
                    'to_location'   => $transfer->toLocation?->name ?? ('Branch #' . $transfer->to_location_id),
                    'to_location_id'=> $transfer->to_location_id,
                    'status'        => $transfer->status == PurchaseBill::STATUS_ACCEPTED ? 'Accepted' : 'Pending',
                    'created_at'    => $transfer->created_at->format('d M Y, h:i A'),
                    'total_qty'     => $totalQty,
                    'items'         => $matchingItems,
                ];
            }
        }

        $orderIdsDirect = OrderItem::whereIn('purchase_item_id', $purchaseItemIds)
            ->whereNull('deleted_at')
            ->whereHas('order', fn($q) => $q->whereNull('deleted_at')->where('order_type', 'sale')->where('status', '!=', 3))
            ->pluck('order_id')
            ->unique()
            ->all();

        $matchingOrderItems = OrderItem::query()
            ->with(['product', 'order'])
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->whereHas('order', fn($q) => $q->whereNull('deleted_at')->where('order_type', 'sale')->where('status', '!=', 3))
            ->get();

        $orderIdsLocation = [];
        foreach ($matchingOrderItems as $moi) {
            foreach ($purchaseItemSignatures as $sig) {
                if (self::isMatchingOrderItemSingle($moi, $sig)) {
                    $orderIdsLocation[] = $moi->order_id;
                    break;
                }
            }
        }

        $allOrderIds = array_unique(array_merge($orderIdsDirect, $orderIdsLocation));

        $orders = Order::with(['customer', 'location', 'items.product', 'items.variant'])
            ->whereIn('id', $allOrderIds)
            ->orderByDesc('id')
            ->get();

        $salesData = $orders->map(function ($order) use ($purchaseItemSignatures) {
            $paymentStatusLabels = [
                1 => 'Pending',
                2 => 'Paid',
                3 => 'Partially Paid',
            ];

            $matchingItems = [];
            $totalSoldQtyFromThisPurchase = 0;

            foreach ($order->items as $oi) {
                foreach ($purchaseItemSignatures as $sig) {
                    if (self::isMatchingOrderItemSingle($oi, $sig)) {
                        $prod = $oi->product;
                        $vName = $oi->variant?->name ?? $oi->variant?->attributeValue?->value;
                        $prodName = $prod?->name ?? 'Product #' . $oi->product_id;
                        if ($vName) {
                            $prodName .= ' (' . $vName . ')';
                        }
                        $qtyLabel = self::formatItemQtyLabel($prod, $oi->quantity, $oi->pair_type ?? null, $oi->custom_size_value ?? null);
                        $matchingItems[] = [
                            'product_name' => $prodName,
                            'barcode'      => $oi->variant?->barcode ?: ($prod?->barcode ?: ''),
                            'quantity'     => (int) $oi->quantity,
                            'qty_label'    => $qtyLabel,
                        ];
                        $totalSoldQtyFromThisPurchase += (int) $oi->quantity;
                        break;
                    }
                }
            }

            return [
                'id'               => $order->id,
                'order_no'         => $order->order_no,
                'customer_name'    => $order->customer?->name ?? 'Walk-in',
                'location_name'    => $order->location?->name ?? '-',
                'location_id'      => $order->location_id,
                'items_count'      => $totalSoldQtyFromThisPurchase > 0 ? $totalSoldQtyFromThisPurchase : (int) $order->items->sum('quantity'),
                'matching_items'   => $matchingItems,
                'final_amount'     => format_price($order->final_amount),
                'final_amount_raw' => (float) $order->final_amount,
                'payment_status'   => $paymentStatusLabels[$order->payment_status] ?? 'Pending',
                'created_at'       => $order->created_at->format('d M Y, h:i A'),
            ];
        })->values()->all();

        return [
            'purchase' => [
                'id'            => $purchase->id,
                'invoice_no'    => $purchase->invoice_no,
                'supplier_name' => $purchase->supplier?->name ?? '-',
                'total_amount'  => format_price($purchase->total_amount),
                'created_at'    => $purchase->created_at->format('d M Y, h:i A'),
                'location_id'   => $purchaseLocationId,
            ],
            'transfers'     => array_values($transfersGrouped),
            'sales'         => $salesData,
            'has_transfers' => !empty($transfersGrouped),
            'has_sales'     => !empty($salesData),
        ];
    }

    /**
     * Check if an OrderItem belongs to a specific purchase item batch signature.
     */
    public static function isMatchingOrderItemSingle($oi, array $sig): bool
    {
        if (!empty($oi->purchase_item_id) && (int)$oi->purchase_item_id === (int)$sig['id']) {
            return true;
        }

        if ((int)$oi->product_id !== (int)$sig['product_id']) {
            return false;
        }

        $oiVarId = !empty($oi->product_variant_id) ? (int)$oi->product_variant_id : 0;
        if ($oiVarId !== (int)$sig['product_variant_id']) {
            return false;
        }

        $targetPrice = (float)$sig['purchase_price'];
        $rawPrice = (float)($oi->purchase_price ?? 0);

        // 1. Direct price match
        if (abs($targetPrice - $rawPrice) < 0.05) {
            return true;
        }

        // 2. Unit price match (rawPrice / quantity)
        $qty = max(1.0, (float)($oi->quantity ?? 1));
        if (abs($targetPrice - ($rawPrice / $qty)) < 0.05) {
            return true;
        }

        // 3. Physical unit price match (rawPrice / (quantity * multiplier))
        $product = $oi->product ?? Product::find($oi->product_id);
        $multiplier = self::multiplierForProduct($product, $oi->pair_type ?? null, $oi->custom_size_value ?? null);
        $physQty = max(1.0, $qty * $multiplier);
        if (abs($targetPrice - ($rawPrice / $physQty)) < 0.05) {
            return true;
        }

        // 4. Pair multiplier match (rawPrice / 2)
        if (abs($targetPrice - ($rawPrice / 2.0)) < 0.05) {
            return true;
        }

        return false;
    }

    /**
     * Get mapping of sold quantities for each product/variant/purchase_price in a purchase.
     */
    public static function getSoldQuantityForPurchase(Purchase $purchase): array
    {
        $purchaseItemIds = $purchase->items->pluck('id')->all();
        $productIds = $purchase->items->pluck('product_id')->unique()->all();

        $purchaseItemSignatures = $purchase->items->map(function ($pi) {
            return [
                'id'                 => $pi->id,
                'product_id'         => (int) $pi->product_id,
                'product_variant_id' => (int) ($pi->product_variant_id ?? 0),
                'purchase_price'     => (float) $pi->purchase_price,
            ];
        });

        $orderItemsDirect = OrderItem::query()
            ->with('product')
            ->whereIn('purchase_item_id', $purchaseItemIds)
            ->whereNull('deleted_at')
            ->whereHas('order', fn($q) => $q->whereNull('deleted_at')->where('order_type', 'sale')->where('status', '!=', 3))
            ->get();

        $orderItemsByProd = OrderItem::query()
            ->with('product')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->whereHas('order', fn($q) => $q->whereNull('deleted_at')->where('order_type', 'sale')->where('status', '!=', 3))
            ->get();

        $allSoldItems = $orderItemsDirect->merge($orderItemsByProd)->unique('id');

        $soldMap = [];
        foreach ($allSoldItems as $oi) {
            foreach ($purchaseItemSignatures as $sig) {
                if (self::isMatchingOrderItemSingle($oi, $sig)) {
                    $priceKey = number_format((float)$sig['purchase_price'], 2, '.', '');
                    $key = $sig['product_id'] . '_' . $sig['product_variant_id'] . '_' . $priceKey;
                    $soldMap[$key] = ($soldMap[$key] ?? 0) + (int)$oi->quantity;

                    $shortKey = $sig['product_id'] . '_' . $sig['product_variant_id'];
                    $soldMap[$shortKey] = ($soldMap[$shortKey] ?? 0) + (int)$oi->quantity;
                    break;
                }
            }
        }

        return $soldMap;
    }

    /**
     * Multi-branch stock reversal including destination branches where stock was transferred,
     * matching by product, variant AND exact purchase_price batch.
     */
    public static function reversePurchaseWithTransfers(Purchase $purchase, bool $deleteSales = false, string $reason = 'deletion'): void
    {
        $purchase->load(['items.allocations.location', 'items.product', 'items.variant.attributeValue']);
        $purchaseLocationId = $purchase->items->flatMap->allocations->pluck('location_id')->first()
            ?? $purchase->location_id
            ?? 1;

        $productIds = $purchase->items->pluck('product_id')->unique()->all();

        $purchaseItemSignatures = $purchase->items->map(function ($pi) {
            return [
                'id'                 => $pi->id,
                'product_id'         => (int) $pi->product_id,
                'product_variant_id' => (int) ($pi->product_variant_id ?? 0),
                'purchase_price'     => (float) $pi->purchase_price,
            ];
        });

        $transfers = PurchaseBill::with(['items.product', 'items.variant'])
            ->where('from_location_id', $purchaseLocationId)
            ->where('created_at', '>=', $purchase->created_at->subMinutes(5))
            ->whereNull('deleted_at')
            ->get();

        $transferredStockByProductLoc = [];
        foreach ($transfers as $transfer) {
            if ($transfer->status == PurchaseBill::STATUS_REJECTED) continue;
            foreach ($transfer->items as $tbItem) {
                // Must match product, variant AND exact purchase_price of this purchase!
                $matchesThisPurchase = $purchaseItemSignatures->first(function ($sig) use ($tbItem) {
                    return $sig['product_id'] === (int) $tbItem->product_id
                        && $sig['product_variant_id'] === (int) ($tbItem->product_variant_id ?? 0)
                        && abs($sig['purchase_price'] - (float) $tbItem->purchase_price) < 0.01;
                });
                if (!$matchesThisPurchase) continue;

                $multiplier = self::multiplierForProduct($tbItem->product, $tbItem->pair_type, $tbItem->custom_size_value);
                $qty = (int) round($tbItem->quantity * $multiplier);
                $priceKey = number_format((float)$tbItem->purchase_price, 2, '.', '');
                $key = $tbItem->product_id . '_' . ($tbItem->product_variant_id ?? 0) . '_' . $priceKey . '_' . $transfer->to_location_id;
                $transferredStockByProductLoc[$key] = ($transferredStockByProductLoc[$key] ?? 0) + $qty;
            }
        }

        $affectedLocations = [$purchaseLocationId];
        $stockChanges = [];

        foreach ($purchase->items as $item) {
            $product = $item->product;
            $multiplier = self::multiplierFor($item);
            $totalPurchasedQty = (int) round($item->quantity * $multiplier);
            $priceKey = number_format((float)$item->purchase_price, 2, '.', '');

            $transferredOutQty = 0;
            foreach ($transferredStockByProductLoc as $k => $tQty) {
                $prefix = $item->product_id . '_' . ($item->product_variant_id ?? 0) . '_' . $priceKey . '_';
                if (str_starts_with($k, $prefix)) {
                    $locId = (int) substr($k, strlen($prefix));
                    $affectedLocations[] = $locId;

                    $destInv = Inventory::where('product_id', $item->product_id)->where('location_id', $locId)->first();
                    if ($destInv) {
                        $oldDestQty = (int) $destInv->quantity;
                        $deductDest = min($oldDestQty, $tQty);
                        $destInv->update(['quantity' => max(0, $oldDestQty - $deductDest)]);

                        $locObj = Location::find($locId);
                        $stockChanges[] = [
                            'product_name' => $product?->name ?? ('Product #' . $item->product_id),
                            'barcode'      => $product?->barcode ?: '-',
                            'location'     => $locObj?->name ?? ('Location #' . $locId),
                            'old_quantity' => $oldDestQty,
                            'new_quantity' => max(0, $oldDestQty - $deductDest),
                            'qty_deducted' => '-' . $deductDest,
                        ];
                    }
                    PurchaseBatchService::deductBatchStock($locId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$item->purchase_price, (float)$tQty);
                    $transferredOutQty += $tQty;
                }
            }

            $sourceDeductQty = max(0, $totalPurchasedQty - $transferredOutQty);
            $sourceInv = Inventory::where('product_id', $item->product_id)->where('location_id', $purchaseLocationId)->first();
            if ($sourceInv) {
                $oldSrcQty = (int) $sourceInv->quantity;
                $deductSrc = min($oldSrcQty, $sourceDeductQty);
                $sourceInv->update(['quantity' => max(0, $oldSrcQty - $deductSrc)]);

                $srcLocObj = Location::find($purchaseLocationId);
                $stockChanges[] = [
                    'product_name' => $product?->name ?? ('Product #' . $item->product_id),
                    'barcode'      => $product?->barcode ?: '-',
                    'location'     => $srcLocObj?->name ?? ('Location #' . $purchaseLocationId),
                    'old_quantity' => $oldSrcQty,
                    'new_quantity' => max(0, $oldSrcQty - $deductSrc),
                    'qty_deducted' => '-' . $deductSrc,
                ];
            }
            PurchaseBatchService::deductBatchStock($purchaseLocationId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$item->purchase_price, (float)$sourceDeductQty);
        }

        foreach ($transfers as $transfer) {
            if ($transfer->status == PurchaseBill::STATUS_REJECTED) continue;

            $billItemsChanged = false;
            foreach ($transfer->items as $tbItem) {
                // Must match product, variant AND exact purchase_price of an item in this purchase!
                $matchingPurchaseItem = $purchase->items->first(function ($pi) use ($tbItem) {
                    return (int)$pi->product_id === (int)$tbItem->product_id
                        && (int)($pi->product_variant_id ?? 0) === (int)($tbItem->product_variant_id ?? 0)
                        && abs((float)$pi->purchase_price - (float)$tbItem->purchase_price) < 0.01;
                });

                if (!$matchingPurchaseItem) continue;

                $deductBillItemQty = (int) min((int)$tbItem->quantity, (int)$matchingPurchaseItem->quantity);
                $newBillItemQty = max(0, (int)$tbItem->quantity - $deductBillItemQty);

                if ($newBillItemQty <= 0) {
                    $tbItem->delete();
                } else {
                    $tbItem->update(['quantity' => $newBillItemQty]);
                }
                $billItemsChanged = true;
            }

            if ($billItemsChanged) {
                $transfer->load('items');
                $remainingItemsCount = $transfer->items()->count();
                $oldPaidAmount = (float) ($transfer->paid_amount ?? 0);

                if ($remainingItemsCount === 0) {
                    if ($oldPaidAmount > 0) {
                        self::adjustPurchaseBillLocationBalance($transfer, $oldPaidAmount);
                    }
                    $transfer->payments()->delete();
                    $transfer->delete();
                } else {
                    $newTotalAmount = self::calculatePurchaseBillTotal($transfer);
                    $newPaidAmount = min($oldPaidAmount, $newTotalAmount);
                    $excessPaid = max(0.0, round($oldPaidAmount - $newPaidAmount, 2));

                    if ($excessPaid > 0) {
                        self::adjustPurchaseBillLocationBalance($transfer, $excessPaid);
                        $payments = $transfer->payments()->latest()->get();
                        $diffToReduce = $excessPaid;
                        foreach ($payments as $p) {
                            if ($diffToReduce <= 0) break;
                            if ((float)$p->amount <= $diffToReduce) {
                                $diffToReduce -= (float)$p->amount;
                                $p->delete();
                            } else {
                                $p->update(['amount' => (float)$p->amount - $diffToReduce]);
                                $diffToReduce = 0;
                            }
                        }
                    }

                    $newPaymentStatus = ($newPaidAmount >= $newTotalAmount && $newTotalAmount > 0)
                        ? PurchaseBill::PAYMENT_STATUS_PAID
                        : ($newPaidAmount > 0 ? PurchaseBill::PAYMENT_STATUS_PARTIAL : PurchaseBill::PAYMENT_STATUS_PENDING);

                    $transfer->update([
                        'paid_amount'    => $newPaidAmount,
                        'payment_status' => $newPaymentStatus,
                    ]);
                }
            }
        }

        $affectedLocations = array_unique($affectedLocations);
        foreach ($affectedLocations as $locId) {
            foreach ($productIds as $prodId) {
                PurchaseBatchService::syncProductBatchStocks($locId, $prodId);
            }
        }

        if (!empty($stockChanges)) {
            ActivityLogger::log(
                'Inventory',
                'update',
                $purchase,
                ['stock_items' => array_map(fn($sc) => ['product' => $sc['product_name'], 'location' => $sc['location'], 'stock' => $sc['old_quantity']], $stockChanges)],
                ['stock_items' => array_map(fn($sc) => ['product' => $sc['product_name'], 'location' => $sc['location'], 'stock' => $sc['new_quantity'], 'deducted' => $sc['qty_deducted']], $stockChanges)],
                'Stock reversed across branches for purchase #' . $purchase->invoice_no . ' (' . $reason . ')'
            );
        }
    }

    public static function calculatePurchaseBillTotal(PurchaseBill $transfer): float
    {
        $transfer->load(['items.product', 'items.variant']);
        $totalAmount = 0.0;
        foreach ($transfer->items as $item) {
            $product = $item->product;
            $basePrice = (float) (($item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));

            if ($product && $product->pair_product && (float)$item->custom_size_value > 0) {
                $sizes = ($item->variant && !empty($item->variant->custom_sizes))
                    ? $item->variant->custom_sizes
                    : ($product->custom_sizes ?? []);
                $maxSize = collect($sizes)->pluck('size')->map(fn($s) => (float)$s)->filter(fn($s) => $s > 0)->max() ?: 1.0;
                $unitPrice = (float) ($basePrice * ((float)$item->custom_size_value / $maxSize));
            } else {
                $unitPrice = $basePrice;
            }

            $totalAmount += ($unitPrice * (int)$item->quantity);
        }
        return round($totalAmount, 2);
    }

    public static function adjustPurchaseBillLocationBalance(PurchaseBill $purchaseBill, float $amountToReverse): void
    {
        if ($amountToReverse <= 0) {
            return;
        }

        $balanceType = strtolower($purchaseBill->payment_method ?? 'cash') === 'online'
            ? \App\Models\LocationBalanceTransaction::BALANCE_TYPE_BANK
            : \App\Models\LocationBalanceTransaction::BALANCE_TYPE_CASH;

        $balanceCol = $balanceType === \App\Models\LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        $outNote = 'Purchase Bill Out #' . $purchaseBill->transfer_no;
        $inNote  = 'Purchase Bill In #' . $purchaseBill->transfer_no;

        $fromBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->first();
        if ($fromBalance) {
            $fromBalance->update([$balanceCol => (float) $fromBalance->{$balanceCol} - $amountToReverse]);
        }

        $toBalance = \App\Models\LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->first();
        if ($toBalance) {
            $toBalance->update([$balanceCol => (float) $toBalance->{$balanceCol} + $amountToReverse]);
        }

        $existingOut = \App\Models\LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->from_location_id)->first();
        if ($existingOut) {
            $newAmount = max(0.0, (float)$existingOut->amount - $amountToReverse);
            if ($newAmount <= 0) {
                $existingOut->delete();
            } else {
                $existingOut->update(['amount' => $newAmount, 'balance_after' => $fromBalance ? (float)$fromBalance->{$balanceCol} : 0]);
            }
        }

        $existingIn = \App\Models\LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->to_location_id)->first();
        if ($existingIn) {
            $newAmount = max(0.0, (float)$existingIn->amount - $amountToReverse);
            if ($newAmount <= 0) {
                $existingIn->delete();
            } else {
                $existingIn->update(['amount' => $newAmount, 'balance_after' => $toBalance ? (float)$toBalance->{$balanceCol} : 0]);
            }
        }

        \App\Models\LocationBalanceTransaction::syncLocationBalance($purchaseBill->from_location_id, $balanceType);
        \App\Models\LocationBalanceTransaction::syncLocationBalance($purchaseBill->to_location_id, $balanceType);
    }

    /**
     * How many stock units one purchased "quantity" unit represents:
     * a chosen custom size (e.g. 4 pcs) for custom_size-mode pair products,
     * 2 for plain pair products, or 1 otherwise.
     */
    private static function multiplierFor(PurchaseItem $item): float
    {
        return self::multiplierForProduct($item->product, $item->pair_type ?? null, $item->custom_size_value ?? null);
    }

    public static function multiplierForProduct(?Product $product, ?string $pairType = null, $customSizeValue = null): float
    {
        if (!$product || !$product->pair_product) {
            return 1.0;
        }

        if ($customSizeValue !== null && (float)$customSizeValue > 0) {
            return (float) $customSizeValue;
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

    public static function formatItemQtyLabel(?Product $prod, int|float $quantity, ?string $pairType = null, $customSizeValue = null): string
    {
        $qty = (int) $quantity;
        if (!$prod || !$prod->pair_product) {
            return $qty . ' pcs';
        }

        $multiplier = self::multiplierForProduct($prod, $pairType, $customSizeValue);
        $totalPcs = (int) round($qty * $multiplier);

        if ($customSizeValue !== null && (float)$customSizeValue > 0) {
            $szNum = rtrim(rtrim(number_format((float)$customSizeValue, 2), '0'), '.');
            return $qty . ' &times; ' . $szNum . 'pcs (' . $totalPcs . ' pcs)';
        }

        if ($pairType === 'pair' || $multiplier == 2.0) {
            return $qty . ' Pair (' . $totalPcs . ' pcs)';
        }

        return $qty . ' pcs';
    }
}
