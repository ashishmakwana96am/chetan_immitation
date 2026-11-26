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
                    $barcode = $item->variant?->barcode ?: ($product?->barcode ?: '-');
                    $productName = $product?->name ?: ('Product #' . $item->product_id);

                    if ($item->product_variant_id && $item->variant) {
                        $vLabel = trim((string)($item->variant->name ?? $item->variant->attributeValue?->value ?? ''));
                        if ($vLabel !== '') {
                            $productName .= ' (' . $vLabel . ')';
                        }
                    }

                    $stockChanges[] = [
                        'product_id'         => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name'       => $productName,
                        'barcode'            => $barcode,
                        'location'           => $locationName,
                        'quantity'           => $qtyToAdd,
                        'old_quantity'       => $oldQty,
                        'new_quantity'       => $newQty,
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
                'product_id'         => $sc['product_id'] ?? null,
                'product_variant_id' => $sc['product_variant_id'] ?? null,
                'product_name'       => $sc['product_name'],
                'barcode'            => $sc['barcode'],
                'location'           => $sc['location'],
                'stock'              => $sc['old_quantity'],
            ], $stockChanges);

            $newStockSnapshot = array_map(fn($sc) => [
                'product_id'         => $sc['product_id'] ?? null,
                'product_variant_id' => $sc['product_variant_id'] ?? null,
                'product_name'       => $sc['product_name'],
                'barcode'            => $sc['barcode'],
                'location'           => $sc['location'],
                'stock'              => $sc['new_quantity'],
                'qty_added'          => '+' . $sc['quantity'],
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
                        $barcode = $item->variant?->barcode ?: ($product?->barcode ?: '-');
                        $productName = $product?->name ?: ('Product #' . $item->product_id);

                        if ($item->product_variant_id && $item->variant) {
                            $vLabel = trim((string)($item->variant->name ?? $item->variant->attributeValue?->value ?? ''));
                            if ($vLabel !== '') {
                                $productName .= ' (' . $vLabel . ')';
                            }
                        }

                        $stockChanges[] = [
                            'product_id'         => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'product_name'       => $productName,
                            'barcode'            => $barcode,
                            'location'           => $locationName,
                            'quantity'           => $qtyToSubtract,
                            'old_quantity'       => $oldQty,
                            'new_quantity'       => $newQty,
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
                'product_id'         => $sc['product_id'] ?? null,
                'product_variant_id' => $sc['product_variant_id'] ?? null,
                'product_name'       => $sc['product_name'],
                'barcode'            => $sc['barcode'],
                'location'           => $sc['location'],
                'stock'              => $sc['old_quantity'],
            ], $stockChanges);

            $newStockSnapshot = array_map(fn($sc) => [
                'product_id'         => $sc['product_id'] ?? null,
                'product_variant_id' => $sc['product_variant_id'] ?? null,
                'product_name'       => $sc['product_name'],
                'barcode'            => $sc['barcode'],
                'location'           => $sc['location'],
                'stock'              => $sc['new_quantity'],
                'qty_deducted'       => '-' . $sc['quantity'],
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

    public static function getFifoAllocatedSalesForPurchase(Purchase $purchase, ?array $allocatedTransfers = null): array
    {
        $purchase->loadMissing(['items.allocations']);
        $batchKey = fn ($sig) => $sig['product_id'] . '_' . $sig['product_variant_id'] . '_' . number_format($sig['purchase_price'], 2, '.', '');

        $purchaseItemSignatures = $purchase->items->map(function ($pi) {
            return [
                'id'                 => $pi->id,
                'product_id'         => (int) $pi->product_id,
                'product_variant_id' => (int) ($pi->product_variant_id ?? 0),
                'purchase_price'     => (float) $pi->purchase_price,
                'quantity'           => (int) $pi->quantity,
            ];
        });

        $allocatedCapacities = [];

        foreach ($purchaseItemSignatures as $sig) {
            $key = $batchKey($sig);
            $allocatedCapacities[$key] = ($allocatedCapacities[$key] ?? 0) + $sig['quantity'];
        }

        $allocatedSales = self::allocateLinkedBatchSales(
            $purchase,
            $allocatedTransfers ?? self::getFifoAllocatedTransfersForPurchase($purchase),
            $purchaseItemSignatures->keyBy('id')->map($batchKey)->all(),
            $allocatedCapacities
        );

        foreach ($purchaseItemSignatures->unique($batchKey) as $sig) {
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

    private static function allocateLinkedBatchSales(Purchase $purchase, array $transfers, array $purchaseKeys, array &$capacities): array
    {
        $batchIds = array_unique(array_merge(array_keys($purchaseKeys), array_keys($transfers)));
        if (!$batchIds) {
            return [];
        }

        // Sale batch IDs can refer to either table, and their numeric IDs can overlap.
        $purchaseBatches = $purchase->items->keyBy('id');
        $stockByBatch = [];
        foreach ($purchaseBatches as $id => $batch) {
            $key = $purchaseKeys[$id];
            $stockByBatch[$key] = ($stockByBatch[$key] ?? 0) + $batch->quantity * self::multiplierFor($batch);
        }
        $remainingStock = $stockByBatch;
        $otherPurchases = PurchaseItem::withTrashed()->whereIn('id', array_diff($batchIds, array_keys($purchaseKeys)))
            ->with(['invoice' => fn ($query) => $query->withTrashed(), 'allocations' => fn ($query) => $query->withTrashed()])
            ->get()->keyBy('id');
        $possibleTransfers = PurchaseBillItem::withTrashed()->whereIn('id', $batchIds)
            ->with(['purchaseBill' => fn ($query) => $query->withTrashed()])->get()->keyBy('id');
        $sales = OrderItem::with(['product', 'order'])->whereIn('order_items.purchase_item_id', $batchIds)
            ->whereHas('order', fn ($query) => $query->where('order_type', 'sale')->where('status', '!=', Order::STATUS_CANCELLED))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->orderBy('orders.created_at')->orderBy('orders.id')->orderBy('order_items.id')
            ->select('order_items.*')->get();

        $allocated = [];
        $transferUnitsSold = [];
        foreach ($sales as $sale) {
            $id = (int) $sale->purchase_item_id;
            $purchaseBatch = $purchaseBatches->get($id) ?? $otherPurchases->get($id);
            $purchaseInvoice = isset($purchaseKeys[$id]) ? $purchase : $purchaseBatch?->invoice;
            $purchaseLocations = $purchaseBatch?->allocations->pluck('location_id')->all() ?: [$purchaseInvoice?->location_id];
            $transferBatch = $possibleTransfers->get($id);
            $transferBill = $transferBatch?->purchaseBill;
            $isPurchaseSale = self::matchesSaleBatch($sale, $purchaseBatch, $purchaseLocations, $purchaseInvoice?->created_at);
            $isTransferSale = $transferBill?->status == PurchaseBill::STATUS_ACCEPTED
                && self::matchesSaleBatch($sale, $transferBatch, [$transferBill->to_location_id], $transferBill->accepted_at ?? $transferBill->created_at);

            if ($isPurchaseSale && $isTransferSale) {
                continue;
            }

            $saleSize = self::saleStockMultiplier($sale);
            if ($isTransferSale && isset($transfers[$id])) {
                $link = $transfers[$id];
                $transferSize = self::multiplierForProduct($sale->product, $transferBatch->pair_type, $transferBatch->custom_size_value);
                $soldBefore = $transferUnitsSold[$id] ?? 0;
                $soldAfter = $soldBefore + $sale->quantity * $saleSize;
                $transferUnitsSold[$id] = $soldAfter;
                $start = $link['offset'] * $transferSize;
                $end = $start + $link['quantity'] * $transferSize;
                $quantity = (int) floor(max(0, min($soldAfter, $end) - max($soldBefore, $start)) / $saleSize);
                $key = $link['key'];
            } elseif ($isPurchaseSale && isset($purchaseKeys[$id])) {
                $quantity = (int) $sale->quantity;
                $key = $purchaseKeys[$id];
            } else {
                continue;
            }

            $quantity = min($quantity, (int) floor(($remainingStock[$key] ?? 0) / $saleSize));
            if ($quantity > 0) {
                $remainingStock[$key] -= $quantity * $saleSize;
                $allocated[$sale->id] = ['order_item' => $sale, 'quantity' => $quantity, 'order_id' => $sale->order_id, 'key' => $key];
            }
        }

        foreach ($stockByBatch as $key => $stock) {
            if ($stock > 0) {
                $capacities[$key] = (int) floor($capacities[$key] * $remainingStock[$key] / $stock);
            }
        }

        return $allocated;
    }

    private static function matchesSaleBatch(OrderItem $sale, PurchaseItem|PurchaseBillItem|null $batch, array $locations, ?Carbon $availableAt): bool
    {
        return $batch && (int) $sale->product_id === (int) $batch->product_id
            && (int) $sale->product_variant_id === (int) $batch->product_variant_id
            && in_array($sale->order->location_id, array_filter($locations))
            && (!$availableAt || $sale->order->created_at >= $availableAt);
    }

    private static function saleStockMultiplier(OrderItem $sale): float
    {
        return (float) $sale->custom_size_value > 0 ? (float) $sale->custom_size_value
            : ($sale->product?->pair_product && $sale->pair_type === 'pair' ? 2.0 : 1.0);
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
        $batchKey = fn ($sig) => $sig['product_id'] . '_' . $sig['product_variant_id'] . '_' . number_format($sig['purchase_price'], 2, '.', '');

        foreach ($purchaseItemSignatures as $sig) {
            $key = $batchKey($sig);
            $allocatedCapacities[$key] = ($allocatedCapacities[$key] ?? 0) + $sig['quantity'];
        }

        foreach ($purchaseItemSignatures->unique($batchKey) as $sig) {
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
                        'offset'        => $overlapStart - $tStart,
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

        $allocatedSales = self::getFifoAllocatedSalesForPurchase($purchase, $allocatedTransfers);
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

    public static function getConsumedStockForPurchase(Purchase $purchase): array
    {
        $purchase->loadMissing('items.allocations');
        $sourceLocations = $purchase->items->flatMap->allocations->pluck('location_id');
        if ($sourceLocations->isEmpty()) {
            $sourceLocations->push($purchase->location_id ?: 1);
        }

        $sold = [];
        $usedAtSource = [];
        $allocatedTransfers = self::getFifoAllocatedTransfersForPurchase($purchase);
        foreach (self::getFifoAllocatedSalesForPurchase($purchase, $allocatedTransfers) as $data) {
            $item = $data['order_item'];
            $key = $item->product_id . '_' . ($item->product_variant_id ?? 0);
            $qty = (int) round($data['quantity'] * self::saleStockMultiplier($item));
            $sold[$key] = ($sold[$key] ?? 0) + $qty;
            if ($sourceLocations->contains($item->order?->location_id)) {
                $usedAtSource[$key] = ($usedAtSource[$key] ?? 0) + $qty;
            }
        }

        foreach ($allocatedTransfers as $data) {
            $item = $data['transfer_item'];
            $key = $item->product_id . '_' . ($item->product_variant_id ?? 0);
            $qty = (int) round($data['quantity'] * self::multiplierForProduct($item->product, $item->pair_type, $item->custom_size_value));
            $usedAtSource[$key] = ($usedAtSource[$key] ?? 0) + $qty;
        }

        foreach ($sold as $key => $qty) {
            $usedAtSource[$key] = max($usedAtSource[$key] ?? 0, $qty);
        }

        return $usedAtSource;
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
                    $multiplier = self::saleStockMultiplier($itemToDelete);
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
                        (float) $itemToDelete->purchase_price / max(1, $itemToDelete->quantity * $multiplier),
                        (float)$qtyToRestore
                    );
                }
                $order->delete();
                continue;
            }

            foreach ($matchingItems as $itemToDelete) {
                $rmQty = $itemsToRemove[$itemToDelete->id] ?? (int)$itemToDelete->quantity;
                $multiplier = self::saleStockMultiplier($itemToDelete);
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
                    (float) $itemToDelete->purchase_price / max(1, $itemToDelete->quantity * $multiplier),
                    (float)$qtyToRestore
                );

                if ($rmQty >= (int)$itemToDelete->quantity) {
                    $itemToDelete->delete();
                } else {
                    $newQty = (int)$itemToDelete->quantity - $rmQty;
                    $remainingFraction = $newQty / $itemToDelete->quantity;
                    $itemToDelete->update([
                        'quantity'        => $newQty,
                        'purchase_price'  => round((float) $itemToDelete->purchase_price * $remainingFraction, 2),
                        'discount_amount' => round((float) $itemToDelete->discount_amount * $remainingFraction, 2),
                        'discount_value'  => $itemToDelete->discount_type === 'flat'
                            ? round((float) $itemToDelete->discount_value * $remainingFraction, 2) : $itemToDelete->discount_value,
                        'total'           => round((float) $itemToDelete->total * $remainingFraction, 2),
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
    public static function reversePurchaseWithTransfers(Purchase $purchase, bool $deleteSales = false, string $reason = 'deletion', bool $purchaseOnly = false): void
    {
        $purchase->load(['items.allocations.location', 'items.product', 'items.variant.attributeValue']);
        $purchaseLocationId = $purchase->items->flatMap->allocations->pluck('location_id')->first()
            ?? $purchase->location_id
            ?? 1;

        $productIds = $purchase->items->pluck('product_id')->unique()->all();

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
                $quantitiesByLocation = $item->allocations->isEmpty()
                    ? [$purchaseLocationId => $item->quantity]
                    : $item->allocations->groupBy('location_id')->map(fn ($allocations) => $allocations->sum('quantity'));

                foreach ($quantitiesByLocation as $locationId => $qty) {
                    $sourceInv = Inventory::where('product_id', $item->product_id)->where('location_id', $locationId)->lockForUpdate()->first();
                    if (!$sourceInv) {
                        continue;
                    }

                    $affectedLocations[] = (int) $locationId;
                    $oldSrcQty = (int) $sourceInv->quantity;
                    $deductSrc = (int) round($qty * $multiplier);
                    $newSrcQty = $oldSrcQty - $deductSrc;
                    $sourceInv->update(['quantity' => $newSrcQty]);

                    $srcLocObj = Location::find($locationId);
                    $vLabel = ($item->product_variant_id && $item->variant) ? trim((string)($item->variant->name ?? $item->variant->attributeValue?->value ?? '')) : '';
                    $pName = $product?->name ?? ('Product #' . $item->product_id);
                    if ($vLabel !== '') {
                        $pName .= ' (' . $vLabel . ')';
                    }
                    $vBarcode = $item->variant?->barcode ?: ($product?->barcode ?: '-');
                    $stockChanges[] = [
                        'product_id'         => (int) $item->product_id,
                        'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                        'product_name'       => $pName,
                        'barcode'            => $vBarcode,
                        'location'           => $srcLocObj?->name ?? ('Location #' . $locationId),
                        'old_quantity'       => $oldSrcQty,
                        'new_quantity'       => $newSrcQty,
                        'qty_deducted'       => '-' . $deductSrc,
                    ];
                    PurchaseBatchService::deductBatchStock((int) $locationId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$item->purchase_price, (float)$deductSrc);
                }
            }
        } else {
            $soldStockByBatchAndLocation = [];
            if (!$deleteSales) {
                foreach (self::getFifoAllocatedSalesForPurchase($purchase, $allocatedTransfers) as $sale) {
                    $orderItem = $sale['order_item'];
                    $key = $sale['key'] . '_' . $orderItem->order->location_id;
                    $sold = (int) round($sale['quantity'] * self::saleStockMultiplier($orderItem));
                    $soldStockByBatchAndLocation[$key] = ($soldStockByBatchAndLocation[$key] ?? 0) + $sold;
                }
            }

            // Several lines can contribute to the same batch. Subtract its movements once.
            $batches = [];
            foreach ($purchase->items as $item) {
                $priceKey = number_format((float)$item->purchase_price, 2, '.', '');
                $itemKey = $item->product_id . '_' . ($item->product_variant_id ?? 0) . '_' . $priceKey;
                $batches[$itemKey]['item'] = $item;
                $quantities = $item->allocations->isEmpty() ? [$purchaseLocationId => $item->quantity]
                    : $item->allocations->groupBy('location_id')->map(fn ($rows) => $rows->sum('quantity'));
                foreach ($quantities as $locId => $quantity) {
                    $pcs = (int) round($quantity * self::multiplierFor($item));
                    $batches[$itemKey]['stock'][$locId] = ($batches[$itemKey]['stock'][$locId] ?? 0) + $pcs;
                }
            }

            foreach ($batches as $itemKey => $batch) {
                $item = $batch['item'];
                $product = $item->product;
                $locationsToCheck = array_keys($batch['stock']);
                if (!$purchaseOnly) {
                    $locationsToCheck = array_unique(array_merge($locationsToCheck, $allTransferLocations));
                }

                foreach ($locationsToCheck as $locId) {
                    $affectedLocations[] = $locId;
                    $key = $itemKey . '_' . $locId;
                    $tIn = $purchaseOnly ? 0 : ($transfersInByProductLoc[$key] ?? 0);
                    $tOut = $transfersOutByProductLoc[$key] ?? 0;
                    $sold = $soldStockByBatchAndLocation[$key] ?? 0;
                    $locDeductQty = max(0, ($batch['stock'][$locId] ?? 0) + $tIn - $tOut - $sold);
                    if ($locDeductQty === 0) {
                        continue;
                    }

                    $inv = Inventory::where('product_id', $item->product_id)->where('location_id', $locId)->lockForUpdate()->first();
                    if ($inv) {
                        $oldQty = (int) $inv->quantity;
                        $deduct = min($oldQty, $locDeductQty);
                        $newQty = max(0, $oldQty - $deduct);
                        $inv->update(['quantity' => $newQty]);

                        if ($deduct > 0) {
                            $locObj = Location::find($locId);
                            $vLabel = ($item->product_variant_id && $item->variant) ? trim((string)($item->variant->name ?? $item->variant->attributeValue?->value ?? '')) : '';
                            $pName = $product?->name ?? ('Product #' . $item->product_id);
                            if ($vLabel !== '') {
                                $pName .= ' (' . $vLabel . ')';
                            }
                            $vBarcode = $item->variant?->barcode ?: ($product?->barcode ?: '-');
                            $stockChanges[] = [
                                'product_id'         => (int) $item->product_id,
                                'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                                'product_name'       => $pName,
                                'barcode'            => $vBarcode,
                                'location'           => $locObj?->name ?? ('Location #' . $locId),
                                'old_quantity'       => $oldQty,
                                'new_quantity'       => $newQty,
                                'qty_deducted'       => '-' . $deduct,
                            ];
                        }
                    }
                    PurchaseBatchService::deductBatchStock($locId, (int)$item->product_id, !empty($item->product_variant_id) ? (int)$item->product_variant_id : null, (float)$item->purchase_price, (float)$locDeductQty);
                }
            }

            $billItemsChangedTransferIds = [];
            foreach ($purchaseOnly ? [] : $allocatedTransfers as $data) {
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
                if ($remainingItemsCount === 0) {
                    PurchaseBillLedgerService::reverse($transfer);
                    InterBranchStockAdjustmentService::handleBillDeletion($transfer);
                    continue;
                }

                self::reconcileReducedPurchaseBill($transfer);
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
                'product_id'         => $sc['product_id'] ?? null,
                'product_variant_id' => $sc['product_variant_id'] ?? null,
                'product_name'       => $sc['product_name'],
                'barcode'            => $sc['barcode'],
                'location'           => $sc['location'],
                'stock'              => $sc['old_quantity'],
            ], $stockChanges);

            $newStockSnapshot = array_map(fn($sc) => [
                'product_id'         => $sc['product_id'] ?? null,
                'product_variant_id' => $sc['product_variant_id'] ?? null,
                'product_name'       => $sc['product_name'],
                'barcode'            => $sc['barcode'],
                'location'           => $sc['location'],
                'stock'              => $sc['new_quantity'],
                'qty_deducted'       => $sc['qty_deducted'],
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

    private static function reconcileReducedPurchaseBill(PurchaseBill $bill): void
    {
        $total = self::calculatePurchaseBillTotal($bill);
        $payments = $bill->payments()->whereNull('stock_return_bill_id')->latest()->orderByDesc('id')->get();
        $hasOffsets = $bill->payments()->whereNotNull('stock_return_bill_id')->exists() || $bill->returnAdjustments()->exists();
        $legacyPaid = $payments->isEmpty() && !$hasOffsets ? (float) $bill->paid_amount : 0.0;
        $manualPaid = $legacyPaid + (float) $payments->sum('amount');
        $excess = round($manualPaid - $total, 2);

        if ($excess > 0) {
            PurchaseBillLedgerService::reverse($bill, $excess);
            foreach ($payments as $payment) {
                if ($excess <= 0) {
                    break;
                }
                $amount = (float) $payment->amount;
                if ($amount <= $excess) {
                    $payment->delete();
                    $excess = round($excess - $amount, 2);
                } else {
                    $payment->update(['amount' => round($amount - $excess, 2)]);
                    break;
                }
            }
        }

        if ($legacyPaid > 0) {
            $paid = min($legacyPaid, $total);
            $bill->update([
                'paid_amount' => $paid,
                'payment_status' => $paid >= $total && $total > 0 ? PurchaseBill::PAYMENT_STATUS_PAID
                    : ($paid > 0 ? PurchaseBill::PAYMENT_STATUS_PARTIAL : PurchaseBill::PAYMENT_STATUS_PENDING),
            ]);
            return;
        }

        InterBranchStockAdjustmentService::reconcileBill($bill);
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
