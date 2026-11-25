<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Location;
use App\Models\LocationBalance;
use App\Models\LocationBalanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseItem;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\PurchaseBatchService;
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
        $syncTargets = [];

        foreach ($purchase->items as $item) {
            $product = $item->product;
            $multiplier = self::multiplierFor($item);

            if ($item->allocations && $item->allocations->isNotEmpty()) {
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

                    $syncTargets[$allocation->location_id . '_' . $item->product_id . '_' . ($item->product_variant_id ?? 0)] = [
                        'location_id'        => (int) $allocation->location_id,
                        'product_id'         => (int) $item->product_id,
                        'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                    ];
                }
            } else {
                $purchLoc = (int) ($purchase->location_id ?: 1);
                $syncTargets[$purchLoc . '_' . $item->product_id . '_' . ($item->product_variant_id ?? 0)] = [
                    'location_id'        => $purchLoc,
                    'product_id'         => (int) $item->product_id,
                    'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                ];
            }
        }

        foreach ($syncTargets as $st) {
            PurchaseBatchService::syncProductBatchStocks($st['location_id'], $st['product_id'], $st['product_variant_id']);
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

    public static function reverse(Purchase $purchase, string $reason = 'edit'): void
    {
        $purchase->load(['items.allocations.location', 'items.product', 'items.variant.attributeValue']);
        $stockChanges = [];
        $syncTargets = [];

        foreach ($purchase->items as $item) {
            $product = $item->product;
            $multiplier = self::multiplierFor($item);

            if ($item->allocations && $item->allocations->isNotEmpty()) {
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

                        $syncTargets[$allocation->location_id . '_' . $item->product_id . '_' . ($item->product_variant_id ?? 0)] = [
                            'location_id'        => (int) $allocation->location_id,
                            'product_id'         => (int) $item->product_id,
                            'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                        ];
                    }
                }
            } else {
                $purchLoc = (int) ($purchase->location_id ?: 1);
                $syncTargets[$purchLoc . '_' . $item->product_id . '_' . ($item->product_variant_id ?? 0)] = [
                    'location_id'        => $purchLoc,
                    'product_id'         => (int) $item->product_id,
                    'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                ];
            }
        }

        foreach ($syncTargets as $st) {
            PurchaseBatchService::syncProductBatchStocks($st['location_id'], $st['product_id'], $st['product_variant_id']);
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

    public static function getFifoAllocatedSalesForPurchase(Purchase $purchase): array
    {
        $purchase->loadMissing(['items.allocations']);
        $purchaseItemIds = $purchase->items->pluck('id')->all();

        $purchaseItemSignatures = $purchase->items->map(function ($pi) {
            return [
                'id'                 => $pi->id,
                'product_id'         => (int) $pi->product_id,
                'product_variant_id' => (int) ($pi->product_variant_id ?? 0),
                'purchase_price'     => (float) $pi->purchase_price,
                'quantity'           => (int) $pi->quantity,
            ];
        });

        $allocatedSales = [];
        $allocatedCapacities = [];

        foreach ($purchaseItemSignatures as $sig) {
            $key = $sig['product_id'] . '_' . $sig['product_variant_id'] . '_' . number_format((float)$sig['purchase_price'], 2, '.', '');
            $allocatedCapacities[$key] = ($allocatedCapacities[$key] ?? 0) + $sig['quantity'];
        }

        $directOrderItems = OrderItem::query()
            ->with(['product', 'order'])
            ->whereIn('purchase_item_id', $purchaseItemIds)
            ->whereNull('deleted_at')
            ->whereHas('order', fn($q) => $q->whereNull('deleted_at')->where('order_type', 'sale')->where('status', '!=', Order::STATUS_CANCELLED))
            ->orderBy('id', 'asc')
            ->get();

        foreach ($directOrderItems as $oi) {
            $key = $oi->product_id . '_' . (int)($oi->product_variant_id ?? 0) . '_' . number_format((float)($oi->purchase_price ?? 0), 2, '.', '');
            $cap = $allocatedCapacities[$key] ?? 0;
            if ($cap <= 0) continue;

            $takeQty = min((int)$oi->quantity, $cap);
            $allocatedCapacities[$key] -= $takeQty;

            $allocatedSales[$oi->id] = [
                'order_item' => $oi,
                'quantity'   => $takeQty,
                'order_id'   => $oi->order_id,
                'key'        => $key,
            ];
        }

        foreach ($purchaseItemSignatures as $sig) {
            $pId = $sig['product_id'];
            $vId = $sig['product_variant_id'];
            $price = $sig['purchase_price'];
            $key = $pId . '_' . $vId . '_' . number_format((float)$price, 2, '.', '');
            $remainingCap = $allocatedCapacities[$key] ?? 0;
            if ($remainingCap <= 0) {
                continue;
            }

            $priorPurchasesQty = (int) \Illuminate\Support\Facades\DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchase_items.product_id', $pId)
                ->when($vId > 0, fn($q) => $q->where('purchase_items.product_variant_id', $vId), fn($q) => $q->whereNull('purchase_items.product_variant_id'))
                ->whereBetween('purchase_items.purchase_price', [$price - 0.05, $price + 0.05])
                ->whereNull('purchase_items.deleted_at')
                ->whereNull('purchases.deleted_at')
                ->where('purchases.status', Purchase::STATUS_APPROVE)
                ->where(function ($q) use ($purchase) {
                    if ($purchase->created_at) {
                        $q->where('purchases.created_at', '<', $purchase->created_at)
                          ->orWhere(function ($tie) use ($purchase) {
                              $tie->where('purchases.created_at', '=', $purchase->created_at)
                                  ->where('purchases.id', '<', $purchase->id);
                          });
                    } else {
                        $q->where('purchases.id', '<', $purchase->id);
                    }
                })
                ->sum('purchase_items.quantity');

            $windowStart = $priorPurchasesQty;
            $windowEnd = $priorPurchasesQty + $remainingCap;

            $allCandidateSales = OrderItem::query()
                ->with(['product', 'order'])
                ->where('order_items.product_id', $pId)
                ->when($vId > 0, fn($q) => $q->where('order_items.product_variant_id', $vId), fn($q) => $q->whereNull('order_items.product_variant_id'))
                ->whereNull('order_items.purchase_item_id')
                ->whereNull('order_items.deleted_at')
                ->whereHas('order', fn($q) => $q->whereNull('deleted_at')->where('order_type', 'sale')->where('status', '!=', Order::STATUS_CANCELLED))
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereNull('orders.deleted_at')
                ->orderBy('orders.created_at', 'asc')
                ->orderBy('orders.id', 'asc')
                ->orderBy('order_items.id', 'asc')
                ->select('order_items.*')
                ->get();

            $accumulatedSales = 0;
            foreach ($allCandidateSales as $saleItem) {
                if (!self::isMatchingOrderItemSingle($saleItem, $sig)) {
                    continue;
                }

                $sQty = (int) $saleItem->quantity;
                $sStart = $accumulatedSales;
                $sEnd = $accumulatedSales + $sQty;
                $accumulatedSales = $sEnd;

                $overlapStart = max($sStart, $windowStart);
                $overlapEnd = min($sEnd, $windowEnd);
                $overlapQty = max(0, $overlapEnd - $overlapStart);

                if ($overlapQty > 0) {
                    $existingQty = isset($allocatedSales[$saleItem->id]) ? $allocatedSales[$saleItem->id]['quantity'] : 0;
                    $newQty = min((int)$saleItem->quantity, $existingQty + $overlapQty);

                    $allocatedSales[$saleItem->id] = [
                        'order_item' => $saleItem,
                        'quantity'   => $newQty,
                        'order_id'   => $saleItem->order_id,
                        'key'        => $key,
                    ];
                }
            }
        }

        return $allocatedSales;
    }

    public static function getFifoAllocatedTransfersForPurchase(Purchase $purchase): array
    {
        $purchase->loadMissing(['items.allocations.location', 'items.product', 'items.variant']);

        $sourceLocationIds = $purchase->items->flatMap->allocations->pluck('location_id')->filter()->unique()->all();
        if (empty($sourceLocationIds)) {
            $sourceLocationIds = [$purchase->location_id ?: 1];
        }

        $purchaseItemSignatures = $purchase->items->map(function ($pi) {
            return [
                'id'                 => $pi->id,
                'product_id'         => (int) $pi->product_id,
                'product_variant_id' => (int) ($pi->product_variant_id ?? 0),
                'purchase_price'     => (float) $pi->purchase_price,
                'quantity'           => (int) $pi->quantity,
            ];
        });

        $allocatedTransfers = [];
        $allocatedCapacities = [];

        foreach ($purchaseItemSignatures as $sig) {
            $key = $sig['product_id'] . '_' . $sig['product_variant_id'] . '_' . number_format((float)$sig['purchase_price'], 2, '.', '');
            $allocatedCapacities[$key] = ($allocatedCapacities[$key] ?? 0) + $sig['quantity'];
        }

        foreach ($purchaseItemSignatures as $sig) {
            $pId = $sig['product_id'];
            $vId = $sig['product_variant_id'];
            $price = $sig['purchase_price'];
            $key = $pId . '_' . $vId . '_' . number_format((float)$price, 2, '.', '');
            $remainingCap = $allocatedCapacities[$key] ?? 0;
            if ($remainingCap <= 0) {
                continue;
            }

            $priorPurchasedAtSource = (int) \Illuminate\Support\Facades\DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->leftJoin('purchase_allocations', function ($join) {
                    $join->on('purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
                        ->whereNull('purchase_allocations.deleted_at');
                })
                ->where('purchase_items.product_id', $pId)
                ->when($vId > 0, fn($q) => $q->where('purchase_items.product_variant_id', $vId), fn($q) => $q->whereNull('purchase_items.product_variant_id'))
                ->whereBetween('purchase_items.purchase_price', [$price - 0.05, $price + 0.05])
                ->whereNull('purchase_items.deleted_at')
                ->whereNull('purchases.deleted_at')
                ->where('purchases.status', Purchase::STATUS_APPROVE)
                ->where(function ($q) use ($purchase, $sourceLocationIds) {
                    $q->where(function ($sub) use ($sourceLocationIds) {
                        $sub->whereIn('purchase_allocations.location_id', $sourceLocationIds)
                            ->orWhere(function ($fallback) use ($sourceLocationIds) {
                                $fallback->whereNull('purchase_allocations.id')
                                         ->whereIn('purchases.location_id', $sourceLocationIds);
                            });
                    });
                })
                ->where(function ($q) use ($purchase) {
                    if ($purchase->created_at) {
                        $q->where('purchases.created_at', '<', $purchase->created_at)
                          ->orWhere(function ($tie) use ($purchase) {
                              $tie->where('purchases.created_at', '=', $purchase->created_at)
                                  ->where('purchases.id', '<', $purchase->id);
                          });
                    } else {
                        $q->where('purchases.id', '<', $purchase->id);
                    }
                })
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(purchase_allocations.quantity, purchase_items.quantity)'));

            $windowStart = $priorPurchasedAtSource;
            $windowEnd = $priorPurchasedAtSource + $remainingCap;

            $candidateTransferItems = PurchaseBillItem::query()
                ->with(['purchaseBill.fromLocation', 'purchaseBill.toLocation', 'product', 'variant'])
                ->where('purchase_bill_items.product_id', $pId)
                ->when($vId > 0, fn($q) => $q->where('purchase_bill_items.product_variant_id', $vId), fn($q) => $q->whereNull('purchase_bill_items.product_variant_id'))
                ->whereBetween('purchase_bill_items.purchase_price', [$price - 0.05, $price + 0.05])
                ->whereNull('purchase_bill_items.deleted_at')
                ->whereHas('purchaseBill', function ($q) use ($sourceLocationIds) {
                    $q->whereNull('deleted_at')
                      ->where('status', PurchaseBill::STATUS_ACCEPTED)
                      ->whereIn('from_location_id', $sourceLocationIds);
                })
                ->join('purchase_bills', 'purchase_bills.id', '=', 'purchase_bill_items.purchase_bill_id')
                ->whereNull('purchase_bills.deleted_at')
                ->orderBy('purchase_bills.created_at', 'asc')
                ->orderBy('purchase_bills.id', 'asc')
                ->orderBy('purchase_bill_items.id', 'asc')
                ->select('purchase_bill_items.*')
                ->get();

            $accumulatedTransfers = 0;
            foreach ($candidateTransferItems as $tbItem) {
                $tQty = (int) $tbItem->quantity;
                $tStart = $accumulatedTransfers;
                $tEnd = $accumulatedTransfers + $tQty;
                $accumulatedTransfers = $tEnd;

                $overlapStart = max($tStart, $windowStart);
                $overlapEnd = min($tEnd, $windowEnd);
                $overlapQty = max(0, $overlapEnd - $overlapStart);

                if ($overlapQty > 0) {
                    $existing = isset($allocatedTransfers[$tbItem->id]) ? $allocatedTransfers[$tbItem->id]['quantity'] : 0;
                    $allocatedTransfers[$tbItem->id] = [
                        'transfer_item' => $tbItem,
                        'transfer'      => $tbItem->purchaseBill,
                        'quantity'      => min($tQty, $existing + $overlapQty),
                        'key'           => $key,
                    ];
                }
            }
        }

        return $allocatedTransfers;
    }

    public static function getImpactData(Purchase $purchase): array
    {
        $purchase->load(['items.product', 'items.allocations.location', 'supplier']);

        $purchaseLocationId = $purchase->items->flatMap->allocations->pluck('location_id')->first()
            ?? $purchase->location_id
            ?? 1;

        $allocatedTransfers = self::getFifoAllocatedTransfersForPurchase($purchase);
        $transfersGrouped = [];
        foreach ($allocatedTransfers as $tbItemId => $data) {
            $transfer = $data['transfer'];
            $tbItem = $data['transfer_item'];
            $takeQty = $data['quantity'];
            $tId = $transfer->id;

            $prod = $tbItem->product;
            $vName = $tbItem->variant?->name ?? $tbItem->variant?->attributeValue?->value;
            $prodName = $prod?->name ?? 'Product #' . $tbItem->product_id;
            if ($vName) {
                $prodName .= ' (' . $vName . ')';
            }
            $qtyLabel = self::formatItemQtyLabel($prod, $takeQty, $tbItem->pair_type ?? null, $tbItem->custom_size_value ?? null);

            if (!isset($transfersGrouped[$tId])) {
                $transfersGrouped[$tId] = [
                    'id'            => $transfer->id,
                    'transfer_no'   => $transfer->transfer_no ?: ('PB-' . $transfer->id),
                    'from_location' => $transfer->fromLocation?->name ?? ('Branch #' . $transfer->from_location_id),
                    'to_location'   => $transfer->toLocation?->name ?? ('Branch #' . $transfer->to_location_id),
                    'to_location_id'=> $transfer->to_location_id,
                    'status'        => $transfer->status == PurchaseBill::STATUS_ACCEPTED ? 'Accepted' : 'Pending',
                    'created_at'    => $transfer->created_at->format('d M Y, h:i A'),
                    'total_qty'     => 0,
                    'items'         => [],
                ];
            }

            $transfersGrouped[$tId]['total_qty'] += $takeQty;
            $transfersGrouped[$tId]['items'][] = [
                'product_name' => $prodName,
                'price'        => (float) $tbItem->purchase_price,
                'barcode'      => $tbItem->variant?->barcode ?: ($prod?->barcode ?: ''),
                'quantity'     => $takeQty,
                'qty_label'    => $qtyLabel,
            ];
        }

        $allocatedSales = self::getFifoAllocatedSalesForPurchase($purchase);
        $salesGrouped = [];
        $paymentStatusLabels = [
            Order::PAYMENT_STATUS_PAID    => 'Paid',
            Order::PAYMENT_STATUS_PARTIAL => 'Partial',
            Order::PAYMENT_STATUS_PENDING => 'Pending',
        ];

        foreach ($allocatedSales as $oiId => $data) {
            $oi = $data['order_item'];
            $takeQty = $data['quantity'];
            $order = $oi->order;
            if (!$order) continue;
            $oId = $order->id;

            $prod = $oi->product;
            $vName = $oi->variant?->name ?? $oi->variant?->attributeValue?->value;
            $prodName = $prod?->name ?? 'Product #' . $oi->product_id;
            if ($vName) {
                $prodName .= ' (' . $vName . ')';
            }
            $qtyLabel = self::formatItemQtyLabel($prod, $takeQty, $oi->pair_type ?? null, $oi->custom_size_value ?? null);

            if (!isset($salesGrouped[$oId])) {
                $salesGrouped[$oId] = [
                    'id'               => $order->id,
                    'order_no'         => $order->order_no,
                    'customer_name'    => $order->customer?->name ?? 'Walk-in',
                    'location_name'    => $order->location?->name ?? '-',
                    'location_id'      => $order->location_id,
                    'items_count'      => 0,
                    'matching_items'   => [],
                    'final_amount'     => format_price($order->final_amount),
                    'final_amount_raw' => (float) $order->final_amount,
                    'payment_status'   => $paymentStatusLabels[$order->payment_status] ?? 'Pending',
                    'created_at'       => $order->created_at->format('d M Y, h:i A'),
                ];
            }

            $salesGrouped[$oId]['items_count'] += $takeQty;
            $salesGrouped[$oId]['matching_items'][] = [
                'product_name' => $prodName,
                'barcode'      => $oi->variant?->barcode ?: ($prod?->barcode ?: ''),
                'quantity'     => $takeQty,
                'qty_label'    => $qtyLabel,
            ];
        }

        $salesData = array_values($salesGrouped);

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
        if (!empty($oi->purchase_item_id)) {
            return (int)$oi->purchase_item_id === (int)$sig['id'];
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
        $allocatedSales = self::getFifoAllocatedSalesForPurchase($purchase);
        $soldMap = [];

        foreach ($allocatedSales as $data) {
            $oi = $data['order_item'];
            $takeQty = $data['quantity'];
            $key = $data['key'];

            $parts = explode('_', $key);
            $shortKey = $parts[0] . '_' . ($parts[1] ?? 0);
            $locId = $oi->order?->location_id ?? 0;
            $locKey = $key . '_' . $locId;

            $soldMap[$key] = ($soldMap[$key] ?? 0) + $takeQty;
            $soldMap[$shortKey] = ($soldMap[$shortKey] ?? 0) + $takeQty;
            $soldMap[$locKey] = ($soldMap[$locKey] ?? 0) + $takeQty;
        }

        return $soldMap;
    }

    /**
     * Remove only the items matching this purchase from affected sales orders.
     * If all items in a sales order are removed (0 items left), delete the sales order.
     * Otherwise, recalculate order subtotals, discounts, tax (GST), final_amount,
     * and update payment split and ledger entries.
     */
    public static function adjustOrDeleteSalesForPurchase(Purchase $purchase): void
    {
        $allocatedSales = self::getFifoAllocatedSalesForPurchase($purchase);
        if (empty($allocatedSales)) {
            return;
        }

        $itemsToRemove = [];
        $affectedOrderIds = [];

        foreach ($allocatedSales as $oiId => $data) {
            $itemsToRemove[$oiId] = $data['quantity'];
            $affectedOrderIds[] = $data['order_id'];
        }

        $affectedOrderIds = array_unique($affectedOrderIds);
        if (empty($affectedOrderIds) || empty($itemsToRemove)) {
            return;
        }

        $orders = Order::with(['items', 'salePayments', 'customer'])->whereIn('id', $affectedOrderIds)->get();

        foreach ($orders as $order) {
            $orderItemIds = array_keys($itemsToRemove);
            $matchingItems = $order->items->whereIn('id', $orderItemIds);
            if ($matchingItems->isEmpty()) {
                continue;
            }

            $allOrderItemsFullyRemoved = true;
            foreach ($order->items as $it) {
                $rmQty = $itemsToRemove[$it->id] ?? 0;
                if ($rmQty < (int)$it->quantity) {
                    $allOrderItemsFullyRemoved = false;
                    break;
                }
            }

            if ($allOrderItemsFullyRemoved) {
                foreach ($matchingItems as $itemToDelete) {
                    $rmQty = $itemsToRemove[$itemToDelete->id] ?? (int)$itemToDelete->quantity;
                    $prod = $itemToDelete->product ?? Product::find($itemToDelete->product_id);
                    $multiplier = self::multiplierForProduct($prod, $itemToDelete->pair_type ?? null, $itemToDelete->custom_size_value ?? null);
                    $qtyToRestore = (int) round($rmQty * $multiplier);

                    $inventory = Inventory::firstOrCreate(
                        [
                            'product_id'  => $itemToDelete->product_id,
                            'location_id' => $order->location_id,
                        ],
                        [
                            'quantity'   => 0,
                            'created_by' => auth()->id(),
                        ]
                    );
                    $inventory->increment('quantity', $qtyToRestore);

                    PurchaseBatchService::addBatchStock(
                        (int)$order->location_id,
                        (int)$itemToDelete->product_id,
                        !empty($itemToDelete->product_variant_id) ? (int)$itemToDelete->product_variant_id : null,
                        $itemToDelete->purchase_item_id,
                        (float)($itemToDelete->purchase_price ?? 0),
                        (float)$qtyToRestore
                    );
                }
                $order->delete();
                continue;
            }

            foreach ($matchingItems as $itemToDelete) {
                $rmQty = $itemsToRemove[$itemToDelete->id] ?? (int)$itemToDelete->quantity;
                $prod = $itemToDelete->product ?? Product::find($itemToDelete->product_id);
                $multiplier = self::multiplierForProduct($prod, $itemToDelete->pair_type ?? null, $itemToDelete->custom_size_value ?? null);
                $qtyToRestore = (int) round($rmQty * $multiplier);

                $inventory = Inventory::firstOrCreate(
                    [
                        'product_id'  => $itemToDelete->product_id,
                        'location_id' => $order->location_id,
                    ],
                    [
                        'quantity'   => 0,
                        'created_by' => auth()->id(),
                    ]
                );
                $inventory->increment('quantity', $qtyToRestore);

                PurchaseBatchService::addBatchStock(
                    (int)$order->location_id,
                    (int)$itemToDelete->product_id,
                    !empty($itemToDelete->product_variant_id) ? (int)$itemToDelete->product_variant_id : null,
                    $itemToDelete->purchase_item_id,
                    (float)($itemToDelete->purchase_price ?? 0),
                    (float)$qtyToRestore
                );

                if ($rmQty >= (int)$itemToDelete->quantity) {
                    $itemToDelete->delete();
                } else {
                    $newQty = (int)$itemToDelete->quantity - $rmQty;
                    $unitPrice = (float) $itemToDelete->price;
                    $itemToDelete->update([
                        'quantity' => $newQty,
                        'total'    => round($newQty * $unitPrice, 2),
                    ]);
                }
            }

            // Reload remaining items
            $order->load('items');
            $remainingItems = $order->items;

            $newSubtotal = 0.0;
            foreach ($remainingItems as $item) {
                $newSubtotal += (float) $item->total;
            }

            // Recalculate order discount
            $discVal = (float) ($order->order_discount_value ?? 0);
            $discType = $order->order_discount_type ?? 'flat';
            $orderDiscountAmount = 0.0;

            if ($discVal > 0) {
                if ($discType === 'percentage') {
                    $orderDiscountAmount = $newSubtotal * ($discVal / 100);
                } else {
                    $orderDiscountAmount = min($discVal, $newSubtotal);
                }
            }
            if ($orderDiscountAmount > $newSubtotal) {
                $orderDiscountAmount = $newSubtotal;
            }

            $finalBeforeTaxShipping = max(0.0, $newSubtotal - $orderDiscountAmount);

            // Recalculate GST if is_gst
            $newTaxAmount = 0.0;
            if ($order->is_gst && ($order->source ?? 'POS') !== 'ONLINE') {
                $gstRate = (float) Setting::getValue('purchase_gst_rate', 3);
                $halfRate = $gstRate / 2;
                $cgst = round($finalBeforeTaxShipping * ($halfRate / 100), 2);
                $sgst = round($finalBeforeTaxShipping * ($halfRate / 100), 2);
                $newTaxAmount = $cgst + $sgst;
            }

            $shippingCharge = (float) ($order->shipping_charge ?? 0);
            $newFinalAmount = round($finalBeforeTaxShipping + $newTaxAmount + $shippingCharge);

            // Recalculate payment status & paid amounts
            $oldPaidCash = (float) ($order->paid_cash_amount ?? 0);
            $oldPaidOnline = (float) ($order->paid_online_amount ?? 0);
            $oldPaidTotal = $oldPaidCash + $oldPaidOnline;

            $newPaidCash = 0.0;
            $newPaidOnline = 0.0;
            $newPaymentStatus = (int) $order->payment_status;

            if ($newPaymentStatus === Order::PAYMENT_STATUS_PAID) {
                if ($oldPaidTotal > 0) {
                    if ($oldPaidCash > 0 && $oldPaidOnline <= 0) {
                        $newPaidCash = $newFinalAmount;
                        $newPaidOnline = 0.0;
                    } elseif ($oldPaidOnline > 0 && $oldPaidCash <= 0) {
                        $newPaidCash = 0.0;
                        $newPaidOnline = $newFinalAmount;
                    } else {
                        // Split proportionally
                        $ratio = $oldPaidTotal > 0 ? ($newFinalAmount / $oldPaidTotal) : 1.0;
                        $newPaidCash = round($oldPaidCash * $ratio, 2);
                        $newPaidOnline = round($newFinalAmount - $newPaidCash, 2);
                    }
                } else {
                    $isOnline = in_array(strtolower($order->payment_method ?? ''), ['online', 'upi', 'razorpay', 'bank_transfer', 'bank transfer'], true);
                    if ($isOnline) {
                        $newPaidOnline = $newFinalAmount;
                    } else {
                        $newPaidCash = $newFinalAmount;
                    }
                }
            } elseif ($newPaymentStatus === Order::PAYMENT_STATUS_PARTIAL) {
                if ($oldPaidTotal >= $newFinalAmount && $newFinalAmount > 0) {
                    $newPaymentStatus = Order::PAYMENT_STATUS_PAID;
                    if ($oldPaidCash > 0 && $oldPaidOnline <= 0) {
                        $newPaidCash = $newFinalAmount;
                        $newPaidOnline = 0.0;
                    } elseif ($oldPaidOnline > 0 && $oldPaidCash <= 0) {
                        $newPaidCash = 0.0;
                        $newPaidOnline = $newFinalAmount;
                    } else {
                        $ratio = $oldPaidTotal > 0 ? ($newFinalAmount / $oldPaidTotal) : 1.0;
                        $newPaidCash = round($oldPaidCash * $ratio, 2);
                        $newPaidOnline = round($newFinalAmount - $newPaidCash, 2);
                    }
                } else {
                    $newPaidCash = $oldPaidCash;
                    $newPaidOnline = $oldPaidOnline;
                }
            } else {
                $newPaidCash = 0.0;
                $newPaidOnline = 0.0;
                $newPaymentStatus = Order::PAYMENT_STATUS_PENDING;
            }

            // Update order (which triggers OrderObserver to update Location & Customer Ledgers)
            $order->update([
                'tax_amount'         => $newTaxAmount,
                'final_amount'       => $newFinalAmount,
                'paid_cash_amount'   => $newPaidCash,
                'paid_online_amount' => $newPaidOnline,
                'payment_status'     => $newPaymentStatus,
            ]);

            // Sync sale_payments table if records exist
            $newPaidTotal = $newPaidCash + $newPaidOnline;
            $salePayments = $order->salePayments()->latest()->get();
            if ($salePayments->isNotEmpty()) {
                $currentSpTotal = (float) $salePayments->sum('amount');
                $excess = max(0.0, round($currentSpTotal - $newPaidTotal, 2));
                if ($excess > 0) {
                    $diffToReduce = $excess;
                    foreach ($salePayments as $sp) {
                        if ($diffToReduce <= 0) break;
                        $spAmt = (float) $sp->amount;
                        if ($spAmt <= $diffToReduce) {
                            $diffToReduce -= $spAmt;
                            $sp->delete();
                        } else {
                            $reducedAmt = $spAmt - $diffToReduce;
                            $sp->update([
                                'amount'        => $reducedAmt,
                                'cash_amount'   => min((float)$sp->cash_amount, $reducedAmt),
                                'online_amount' => max(0.0, $reducedAmt - min((float)$sp->cash_amount, $reducedAmt)),
                            ]);
                            $diffToReduce = 0;
                        }
                    }
                }
            }

            ActivityLogger::log(
                'Sales',
                'update',
                $order,
                ['final_amount' => (float)$order->getOriginal('final_amount')],
                ['final_amount' => $newFinalAmount],
                'Sale #' . $order->order_no . ' recalculated after purchase #' . $purchase->invoice_no . ' deletion'
            );
        }
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

        $allocatedTransfers = self::getFifoAllocatedTransfersForPurchase($purchase);

        $transfersInByProductLoc = [];
        $transfersOutByProductLoc = [];
        $allTransferLocations = [];

        foreach ($allocatedTransfers as $data) {
            $tbItem = $data['transfer_item'];
            $transfer = $data['transfer'] ?? $tbItem->purchaseBill;
            $takeQty = $data['quantity'];
            $key = $data['key'];

            $multiplier = self::multiplierForProduct($tbItem->product, $tbItem->pair_type, $tbItem->custom_size_value);
            $qty = (int) round($takeQty * $multiplier);

            $toKey = $key . '_' . $transfer->to_location_id;
            $transfersInByProductLoc[$toKey] = ($transfersInByProductLoc[$toKey] ?? 0) + $qty;

            $fromKey = $key . '_' . $transfer->from_location_id;
            $transfersOutByProductLoc[$fromKey] = ($transfersOutByProductLoc[$fromKey] ?? 0) + $qty;

            $allTransferLocations[] = (int) $transfer->to_location_id;
            $allTransferLocations[] = (int) $transfer->from_location_id;
        }

        $affectedLocations = [$purchaseLocationId];
        $stockChanges = [];

        if ($reason === 'edit') {
            foreach ($purchase->items as $item) {
                $product = $item->product;
                $multiplier = self::multiplierFor($item);
                $totalPurchasedQty = (int) round($item->quantity * $multiplier);
                $priceKey = number_format((float)$item->purchase_price, 2, '.', '');

                $sourceInv = Inventory::where('product_id', $item->product_id)->where('location_id', $purchaseLocationId)->first();
                if ($sourceInv) {
                    $oldSrcQty = (int) $sourceInv->quantity;
                    $deductSrc = $totalPurchasedQty;
                    $newSrcQty = $oldSrcQty - $deductSrc;
                    $sourceInv->update(['quantity' => $newSrcQty]);

                    $srcLocObj = Location::find($purchaseLocationId);
                    $stockChanges[] = [
                        'product_name' => $product?->name ?? ('Product #' . $item->product_id),
                        'barcode'      => $product?->barcode ?: '-',
                        'location'     => $srcLocObj?->name ?? ('Location #' . $purchaseLocationId),
                        'old_quantity' => $oldSrcQty,
                        'new_quantity' => $newSrcQty,
                        'qty_deducted' => '-' . $deductSrc,
                    ];
                    PurchaseBatchService::deductBatchStock($purchaseLocationId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$item->purchase_price, (float)$deductSrc);
                }
            }
        } else {
            $soldMap = self::getSoldQuantityForPurchase($purchase);

            foreach ($purchase->items as $item) {
                $product = $item->product;
                $multiplier = self::multiplierFor($item);
                $totalPurchasedQty = (int) round($item->quantity * $multiplier);
                $priceKey = number_format((float)$item->purchase_price, 2, '.', '');
                $itemKey = $item->product_id . '_' . ($item->product_variant_id ?? 0) . '_' . $priceKey;

                $locationsToCheck = array_unique(array_merge([$purchaseLocationId], $allTransferLocations));

                foreach ($locationsToCheck as $locId) {
                    $affectedLocations[] = $locId;
                    $allocatedAtLoc = 0;
                    if ($item->allocations && $item->allocations->isNotEmpty()) {
                        $allocRow = $item->allocations->firstWhere('location_id', $locId);
                        if ($allocRow) {
                            $allocatedAtLoc = (int) round($allocRow->quantity * $multiplier);
                        }
                    } elseif ((int)$locId === (int)$purchaseLocationId) {
                        $allocatedAtLoc = $totalPurchasedQty;
                    }

                    $tIn = $transfersInByProductLoc[$itemKey . '_' . $locId] ?? 0;
                    $tOut = $transfersOutByProductLoc[$itemKey . '_' . $locId] ?? 0;
                    $netStockAtLoc = max(0, $allocatedAtLoc + $tIn - $tOut);

                    $soldAtLoc = $soldMap[$itemKey . '_' . $locId] ?? 0;
                    $soldStockAtLoc = (int) round($soldAtLoc * $multiplier);
                    $deductForSales = $deleteSales ? 0 : $soldStockAtLoc;
                    $locDeductQty = max(0, $netStockAtLoc - $deductForSales);

                    $inv = Inventory::where('product_id', $item->product_id)->where('location_id', $locId)->first();
                    if ($inv) {
                        $oldQty = (int) $inv->quantity;
                        $deduct = min($oldQty, $locDeductQty);
                        $newQty = max(0, $oldQty - $deduct);
                        $inv->update(['quantity' => $newQty]);

                        if ($deduct > 0) {
                            $locObj = Location::find($locId);
                            $stockChanges[] = [
                                'product_id'   => (int) $item->product_id,
                                'product_name' => $product?->name ?? ('Product #' . $item->product_id),
                                'barcode'      => $product?->barcode ?: '-',
                                'location'     => $locObj?->name ?? ('Location #' . $locId),
                                'old_quantity' => $oldQty,
                                'new_quantity' => $newQty,
                                'qty_deducted' => '-' . $deduct,
                            ];
                        }
                    }
                    PurchaseBatchService::deductBatchStock($locId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$item->purchase_price, (float)$locDeductQty);
                }
            }

            $billItemsChangedTransferIds = [];
            foreach ($allocatedTransfers as $data) {
                $tbItem = $data['transfer_item'];
                $transfer = $data['transfer'] ?? $tbItem->purchaseBill;
                $deductBillItemQty = $data['quantity'];

                $newBillItemQty = max(0, (int)$tbItem->quantity - $deductBillItemQty);
                if ($newBillItemQty <= 0) {
                    $tbItem->delete();
                } else {
                    $tbItem->update(['quantity' => $newBillItemQty]);
                }
                $billItemsChangedTransferIds[] = $transfer->id;
            }

            $affectedTransfers = PurchaseBill::with('items')->whereIn('id', array_unique($billItemsChangedTransferIds))->get();
            foreach ($affectedTransfers as $transfer) {
                if ($transfer->status == PurchaseBill::STATUS_REJECTED) continue;

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
            $oldStockSnapshot = array_map(fn($sc) => [
                'product_id'   => $sc['product_id'] ?? null,
                'product_name' => $sc['product_name'],
                'barcode'      => $sc['barcode'],
                'location'     => $sc['location'],
                'stock'        => $sc['old_quantity'],
            ], $stockChanges);

            $newStockSnapshot = array_map(fn($sc) => [
                'product_id'   => $sc['product_id'] ?? null,
                'product_name' => $sc['product_name'],
                'barcode'      => $sc['barcode'],
                'location'     => $sc['location'],
                'stock'        => $sc['new_quantity'],
                'qty_deducted' => $sc['qty_deducted'],
            ], $stockChanges);

            ActivityLogger::log(
                'Inventory',
                'update',
                $purchase,
                ['stock_items' => $oldStockSnapshot],
                ['stock_items' => $newStockSnapshot],
                'Stock reversed across branches for purchase #' . $purchase->invoice_no . ' (' . $reason . ')'
            );
        }
    }

    public static function calculatePurchaseBillTotal(PurchaseBill $transfer): float
    {
        $transfer->load(['items.product', 'items.variant']);
        $totalAmount = 0.0;
        foreach ($transfer->items as $item) {
            if ($item->deleted_at !== null) {
                continue;
            }
            unset($unitPrice);
            $product = $item->product;
            $basePrice = (float) (($item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));

            if ($product && $product->pair_product) {
                $selectedSize = (float) ($item->custom_size_value ?? 0);
                $sizes = ($item->variant && !empty($item->variant->custom_sizes))
                    ? $item->variant->custom_sizes
                    : ($product->custom_sizes ?? []);
                $sizesCollection = collect($sizes)->pluck('size')->map(fn($s) => (float)$s)->filter(fn($s) => $s > 0);
                $maxSize = $sizesCollection->count() > 0 ? (float)$sizesCollection->max() : ($product ? (float)$product->getPairPackSize() : 2.0);
                if ($maxSize <= 0) {
                    $maxSize = 2.0;
                }

                if ($selectedSize <= 0) {
                    if (($item->pair_type ?? '') === 'single') {
                        $selectedSize = 1.0;
                    } else {
                        $unitPrice = $basePrice;
                    }
                }

                if (!isset($unitPrice)) {
                    $unitPrice = (float) ($basePrice * ($selectedSize / $maxSize));
                }
            } else {
                $unitPrice = $basePrice;
            }

            $totalAmount += ($unitPrice * (int)$item->quantity);
        }
        return round($totalAmount, 2);
    }

    public static function adjustPurchaseBillLocationBalance(PurchaseBill $purchaseBill, float $amountToReverse): void
    {
        $stockReturnOffset = (float) $purchaseBill->payments()->whereNotNull('stock_return_bill_id')->sum('amount');
        if ($stockReturnOffset > 0) {
            $amountToReverse = max(0.0, round($amountToReverse - $stockReturnOffset, 2));
        }

        if ($amountToReverse <= 0) {
            return;
        }

        $balanceType = strtolower($purchaseBill->payment_method ?? 'cash') === 'online'
            ? LocationBalanceTransaction::BALANCE_TYPE_BANK
            : LocationBalanceTransaction::BALANCE_TYPE_CASH;

        $balanceCol = $balanceType === LocationBalanceTransaction::BALANCE_TYPE_BANK
            ? 'bank_balance'
            : 'cash_balance';

        $outNote = 'Purchase Bill Out #' . $purchaseBill->transfer_no;
        $inNote  = 'Purchase Bill In #' . $purchaseBill->transfer_no;

        $fromBalance = LocationBalance::where('location_id', $purchaseBill->from_location_id)->lockForUpdate()->first();
        if ($fromBalance) {
            $fromBalance->update([$balanceCol => (float) $fromBalance->{$balanceCol} - $amountToReverse]);
        }

        $toBalance = LocationBalance::where('location_id', $purchaseBill->to_location_id)->lockForUpdate()->first();
        if ($toBalance) {
            $toBalance->update([$balanceCol => (float) $toBalance->{$balanceCol} + $amountToReverse]);
        }

        $existingOut = LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->from_location_id)->first();
        if ($existingOut) {
            $newAmount = max(0.0, (float)$existingOut->amount - $amountToReverse);
            if ($newAmount <= 0) {
                $existingOut->delete();
            } else {
                $existingOut->update(['amount' => $newAmount, 'balance_after' => $fromBalance ? (float)$fromBalance->{$balanceCol} : 0]);
            }
        }

        $existingIn = LocationBalanceTransaction::where('notes', 'LIKE', '%' . $purchaseBill->transfer_no)->where('location_id', $purchaseBill->to_location_id)->first();
        if ($existingIn) {
            $newAmount = max(0.0, (float)$existingIn->amount - $amountToReverse);
            if ($newAmount <= 0) {
                $existingIn->delete();
            } else {
                $existingIn->update(['amount' => $newAmount, 'balance_after' => $toBalance ? (float)$toBalance->{$balanceCol} : 0]);
            }
        }

        LocationBalanceTransaction::syncLocationBalance($purchaseBill->from_location_id, $balanceType);
        LocationBalanceTransaction::syncLocationBalance($purchaseBill->to_location_id, $balanceType);
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

    public static function canEditOrDeletePurchaseBill(PurchaseBill $purchaseBill): bool
    {
        if ((int) $purchaseBill->status !== PurchaseBill::STATUS_ACCEPTED) {
            return true;
        }

        $purchaseBill->loadMissing(['items.product', 'items.variant']);

        $destLocationId = (int) $purchaseBill->to_location_id;
        $billAcceptedAt = $purchaseBill->accepted_at ?? $purchaseBill->created_at;

        foreach ($purchaseBill->items as $item) {
            $productId = (int) $item->product_id;
            $variantId = !empty($item->product_variant_id) ? (int) $item->product_variant_id : null;
            $unitPrice = (float) $item->purchase_price;

            $product = $item->product ?? Product::find($productId);
            $multiplier = self::multiplierForProduct($product, $item->pair_type ?? null, $item->custom_size_value ?? null);
            $billPhysicalQty = (float) $item->quantity * $multiplier;

            $soldOrderItems = OrderItem::query()
                ->with(['product', 'order'])
                ->where('product_id', $productId)
                ->when($variantId, fn($q) => $q->where('product_variant_id', $variantId), fn($q) => $q->whereNull('product_variant_id'))
                ->whereNull('deleted_at')
                ->whereHas('order', function ($q) use ($destLocationId) {
                    $q->where('location_id', $destLocationId)
                        ->whereNull('deleted_at')
                        ->where('order_type', 'sale')
                        ->where('status', '!=', 6);
                })
                ->get();

            $totalPhysicalSold = 0.0;
            $sig = [
                'id'                 => 0,
                'product_id'         => $productId,
                'product_variant_id' => $variantId ?? 0,
                'purchase_price'     => $unitPrice,
            ];

            foreach ($soldOrderItems as $soi) {
                if (self::isMatchingOrderItemSingle($soi, $sig)) {
                    $soiMultiplier = self::multiplierForProduct($soi->product ?? $product, $soi->pair_type ?? null, $soi->custom_size_value ?? null);
                    $totalPhysicalSold += ((float) $soi->quantity * $soiMultiplier);
                }
            }

            $priorBillItems = PurchaseBillItem::query()
                ->with(['product', 'transfer'])
                ->where('product_id', $productId)
                ->when($variantId, fn($q) => $q->where('product_variant_id', $variantId), fn($q) => $q->whereNull('product_variant_id'))
                ->whereNull('deleted_at')
                ->whereHas('transfer', function ($q) use ($destLocationId, $purchaseBill, $billAcceptedAt) {
                    $q->where('to_location_id', $destLocationId)
                        ->where('status', PurchaseBill::STATUS_ACCEPTED)
                        ->whereNull('deleted_at')
                        ->where(function ($sub) use ($purchaseBill, $billAcceptedAt) {
                            $sub->where('accepted_at', '<', $billAcceptedAt)
                                ->orWhere(function ($tie) use ($purchaseBill, $billAcceptedAt) {
                                    $tie->where('accepted_at', '=', $billAcceptedAt)
                                        ->where('id', '<', $purchaseBill->id);
                                });
                        });
                })
                ->get();

            $priorTransferredPhysicalQty = 0.0;
            foreach ($priorBillItems as $pbi) {
                if (abs((float)$pbi->purchase_price - $unitPrice) < 0.05) {
                    $pbiMultiplier = self::multiplierForProduct($pbi->product ?? $product, $pbi->pair_type ?? null, $pbi->custom_size_value ?? null);
                    $priorTransferredPhysicalQty += ((float) $pbi->quantity * $pbiMultiplier);
                }
            }

            if ($totalPhysicalSold > $priorTransferredPhysicalQty) {
                return false;
            }

            $destInventory = Inventory::where('product_id', $productId)
                ->where('location_id', $destLocationId)
                ->whereNull('deleted_at')
                ->first();
            $destLiveQty = $destInventory ? (float) $destInventory->quantity : 0.0;
            if ($destLiveQty < $billPhysicalQty) {
                return false;
            }
        }

        return true;
    }
}
