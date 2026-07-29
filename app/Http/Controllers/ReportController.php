<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogger;
use App\Services\ReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    protected $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }
    public function products()
    {
        $this->authorize('view product reports');

        $categories = Category::where('status', 1)->orderBy('name')->get();

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        $products = Product::with(['category', 'primaryImage', 'inventories', 'variants.attributeValue.attribute'])
            ->orderBy('name')
            ->get();

        $productsList = collect();
        foreach ($products as $product) {
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                $parentLocStock  = [];
                $hasVariantStock = false;
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent     = (int) ($variantStock[$location->id]['parent'] ?? 0);
                    if ($locVariantSum > 0) {
                        $hasVariantStock = true;
                        $parentLocStock[$location->id] = $locVariantSum;
                    } else {
                        $parentLocStock[$location->id] = max(0, $locParent);
                    }
                }
                $parentStock = array_sum($parentLocStock);

                if ($parentStock <= 0) {
                    foreach ($locations as $location) {
                        $inventory = $product->inventories->firstWhere('location_id', $location->id);
                        $parentLocStock[$location->id] = $inventory ? (int) $inventory->quantity : 0;
                    }
                    $parentStock = array_sum($parentLocStock);
                }

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $parentStock,
                    'formatted_stock' => $product->formatStockDisplay($parentStock),
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                    'image_url'      => $product->primary_image_url,
                ]);

                // Variant rows
                foreach ($product->variants as $v) {
                    $vStock = 0;
                    if ($user->location_id && !$user->hasRole('super-admin')) {
                        $vStock = max(0, (int) ($variantStock[$user->location_id]['variants'][$v->id] ?? 0));
                    } else {
                        foreach ($locations as $location) {
                            $vStock += max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                        }
                    }

                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';

                    $productsList->push([
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'barcode'        => $product->barcode,
                        'category'       => $product->category->name ?? '-',
                        'category_id'    => $product->category_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price'     => $v->sale_price,
                        'total_stock'    => $vStock,
                        'formatted_stock' => $product->formatStockDisplay($vStock),
                        'status'         => $v->status,
                        'is_parent'      => false,
                        'variant_name'   => "{$attrName}: {$valName}",
                        'image_url'      => $product->primary_image_url,
                    ]);
                }
            } else {
                $totalStock = $product->inventories
                    ->when($user->location_id && !$user->hasRole('super-admin'), fn($col) => $col->where('location_id', $user->location_id))
                    ->sum('quantity');

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $totalStock,
                    'formatted_stock' => $product->formatStockDisplay($totalStock),
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                    'image_url'      => $product->primary_image_url,
                ]);
            }
        }

        $totalProducts = Product::count();
        $activeProductCount = Product::where('status', 1)->count();

        // Derived from $productsList (already ledger-aware for variable products) rather than a
        // raw inventories-table query, which misreports variable products as sold out.
        $soldoutProductCount = $productsList
            ->where('is_parent', true)
            ->where('status', 1)
            ->filter(fn ($p) => $p['total_stock'] <= 0)
            ->count();

        return view('reports.products', ['products' => $productsList, 'categories' => $categories,'totalProducts' => $totalProducts, 'activeProductCount' => $activeProductCount, 'soldoutProductCount' => $soldoutProductCount]);
    }

    public function stockInventory(Request $request)
    {
        $this->authorize('view stock inventory reports');

        $user      = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId   = $isRestricted ? $user->location_id : null;

        $fromDate = $request->query('from_date');
        $toDate   = $request->query('to_date');

        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $categories = Category::where('status', 1)->orderBy('name')->get();

        $products = Product::with(['category', 'primaryImage', 'inventories.location', 'variants.attributeValue.attribute'])
            ->orderBy('name')
            ->get();

        // Last purchase date per (product, variant) — one aggregate query for every product, no N+1.
        $lastPurchaseRows = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', Purchase::STATUS_APPROVE)
            ->when($fromDate, fn ($q) => $q->whereDate('purchases.created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('purchases.created_at', '<=', $toDate))
            ->groupBy('purchase_items.product_id', 'purchase_items.product_variant_id')
            ->select('purchase_items.product_id', 'purchase_items.product_variant_id', DB::raw('MAX(purchases.created_at) as last_purchase_at'))
            ->get();

        $variantLastPurchase = [];
        $productLastPurchase = [];
        foreach ($lastPurchaseRows as $row) {
            $variantKey = $row->product_id . ':' . ($row->product_variant_id ?? 0);
            $variantLastPurchase[$variantKey] = $row->last_purchase_at;

            if (!isset($productLastPurchase[$row->product_id]) || $row->last_purchase_at > $productLastPurchase[$row->product_id]) {
                $productLastPurchase[$row->product_id] = $row->last_purchase_at;
            }
        }

        $buildAgeInfo = function (?string $lastPurchaseAt) {
            if (!$lastPurchaseAt) {
                return [
                    'last_purchase_date' => null,
                    'last_purchase_display' => '-',
                    'age_days' => null,
                    'age_display' => 'No Purchase History',
                    'age_sort' => PHP_INT_MAX, // never-purchased sorts as "oldest" first
                ];
            }

            $lastPurchase = \Carbon\Carbon::parse($lastPurchaseAt);
            $ageDays = (int) floor($lastPurchase->diffInDays(now()));

            return [
                'last_purchase_date' => $lastPurchase->toDateString(),
                'last_purchase_display' => $lastPurchase->format('d-M-Y'),
                'age_days' => $ageDays,
                'age_display' => $ageDays . ' Days',
                'age_sort' => $ageDays,
            ];
        };

        $productsList = collect();
        foreach ($products as $product) {
            $purchasePrice = (float) $product->purchase_price;
            $salePrice     = (float) $product->sale_price;
            $mrpPrice      = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                // Build per-location stock: prefer variant-level sum (positive), fall back to
                // parent-level (handles purchase-at-parent, sale-at-variant inconsistency).
                $parentLocStock    = [];
                $hasVariantStock   = false;   // true if ANY location has positive variant-level stock
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent     = (int) ($variantStock[$location->id]['parent'] ?? 0);
                    if ($locVariantSum > 0) {
                        $hasVariantStock = true;
                        $parentLocStock[$location->id] = $locVariantSum;
                    } else {
                        $parentLocStock[$location->id] = max(0, $locParent);
                    }
                }
                $parentTotal = array_sum($parentLocStock);

                if ($parentTotal <= 0) {
                    foreach ($locations as $location) {
                        $inventory = $product->inventories->firstWhere('location_id', $location->id);
                        $parentLocStock[$location->id] = $inventory ? (int) $inventory->quantity : 0;
                    }
                    $parentTotal = array_sum($parentLocStock);
                    $hasVariantStock = false;
                }

                if ($hasVariantStock) {
                    $parentPurchaseVal = 0.0;
                    $parentSaleVal     = 0.0;
                    $parentMrpVal      = 0.0;
                } else {
                    $effectiveTotal    = $product->pair_product ? ($parentTotal / $pairSize) : (float) $parentTotal;
                    $parentPurchaseVal = $effectiveTotal * $purchasePrice;
                    $parentSaleVal     = $effectiveTotal * $salePrice;
                    $parentMrpVal      = $effectiveTotal * $mrpPrice;
                }
                $parentPairCount   = 0;

                $variantRows = [];
                foreach ($product->variants as $v) {
                    $vLocStock = [];
                    foreach ($locations as $location) {
                        $vLocStock[$location->id] = max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                    }
                    $attrName   = $v->attributeValue->attribute->name ?? '';
                    $valName    = $v->attributeValue->value ?? '';
                    $vTotal     = array_sum($vLocStock); // always >= 0
                    $vPrice     = (float) ($v->purchase_price ?? $purchasePrice);
                    $vSale      = (float) ($v->sale_price ?? $salePrice);
                    $vMrp       = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);
                    $vEffectiveQty = $product->pair_product ? ($vTotal / $pairSize) : (float) $vTotal;
                    $vPurchVal  = $vEffectiveQty * $vPrice;
                    $vSaleVal   = $vEffectiveQty * $vSale;
                    $vMrpVal    = $vEffectiveQty * $vMrp;

                    $vPairCount = ($product->pair_product && $vTotal > 0) ? (int) floor($vTotal / $pairSize) : 0;
                    $parentPairCount += $vPairCount;

                    if ($hasVariantStock) {
                        $parentPurchaseVal += $vPurchVal;
                        $parentSaleVal     += $vSaleVal;
                        $parentMrpVal      += $vMrpVal;
                    }

                    $variantKey = $product->id . ':' . $v->id;

                    $variantRows[] = array_merge([
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'barcode'        => $product->barcode,
                        'category'       => $product->category->name ?? '-',
                        'category_id'    => $product->category_id,
                        'stock'          => $vLocStock,
                        'total'          => $vTotal,
                        'purchase_value' => $vPurchVal,
                        'sale_value'     => $vSaleVal,
                        'mrp_value'      => $vMrpVal,
                        'stock_value'    => $vPurchVal,
                        'status'         => $v->status,
                        'is_parent'      => false,
                        'variant_name'   => "{$attrName}: {$valName}",
                        'image_url'      => $product->primary_image_url,
                        'pair_product'   => (bool) $product->pair_product,
                        'pair_count'     => $vPairCount,
                        'pair_display'   => $product->pair_product ? $product->formatStockDisplay($vTotal) : null,
                    ], $buildAgeInfo($variantLastPurchase[$variantKey] ?? null));
                }

                $parentPairCount = ($product->pair_product && $parentTotal > 0) ? (int) floor($parentTotal / $pairSize) : 0;
                $parentLoosePcs  = $product->pair_product ? (int) ($parentTotal % $pairSize) : (int) $parentTotal;

                $productsList->push(array_merge([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'stock'          => $parentLocStock,
                    'total'          => $parentTotal,
                    'purchase_value' => $parentPurchaseVal,
                    'sale_value'     => $parentSaleVal,
                    'mrp_value'      => $parentMrpVal,
                    'stock_value'    => $parentPurchaseVal,
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                    'image_url'      => $product->primary_image_url,
                    'product_obj'    => $product,
                    'pair_product'   => (bool) $product->pair_product,
                    'pair_count'     => $parentPairCount,
                    'loose_pcs'      => $parentLoosePcs,
                    'pair_display'   => $product->pair_product ? $product->formatStockDisplay($parentTotal) : null,
                ], $buildAgeInfo($productLastPurchase[$product->id] ?? null)));

                foreach ($variantRows as $vRow) {
                    $productsList->push($vRow);
                }
            } else {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory            = $product->inventories->firstWhere('location_id', $location->id);
                    $stock[$location->id] = $inventory ? $inventory->quantity : 0;
                }
                $total       = array_sum($stock);
                $effectiveQty = $product->pair_product ? ($total / $pairSize) : (float) $total;
                $purchaseVal = $effectiveQty * $purchasePrice;
                $saleVal     = $effectiveQty * $salePrice;
                $mrpVal      = $effectiveQty * $mrpPrice;

                $pPairCount  = ($product->pair_product && $total > 0) ? (int) floor($total / $pairSize) : 0;
                $pLoosePcs   = $product->pair_product ? (int) ($total % $pairSize) : (int) $total;

                $productsList->push(array_merge([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'stock'          => $stock,
                    'total'          => $total,
                    'purchase_value' => $purchaseVal,
                    'sale_value'     => $saleVal,
                    'mrp_value'      => $mrpVal,
                    'stock_value'    => $purchaseVal,
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                    'image_url'      => $product->primary_image_url,
                    'product_obj'    => $product,
                    'pair_product'   => (bool) $product->pair_product,
                    'pair_count'     => $pPairCount,
                    'loose_pcs'      => $pLoosePcs,
                    'pair_display'   => $product->pair_product ? $product->formatStockDisplay($total) : null,
                ], $buildAgeInfo($productLastPurchase[$product->id] ?? null)));
            }
        }

        $activeProductCount = Product::where('status', 1)->count();

        $soldoutProductCount = $productsList
            ->where('is_parent', true)
            ->where('status', 1)
            ->filter(fn ($p) => $p['total'] <= 0)
            ->count();

        return view('reports.stock-inventory', [
            'products'           => $productsList,
            'locations'          => $locations,
            'categories'         => $categories,
            'activeProductCount' => $activeProductCount,
            'soldoutProductCount'=> $soldoutProductCount,
            'isRestricted'       => $isRestricted,
            'fromDate'           => $fromDate,
            'toDate'             => $toDate,
        ]);
    }

    public function purchases(Request $request)
    {
        $this->authorize('view purchase reports');

        $suppliers = Supplier::orderBy('name')->get();
        
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $isGst = $request->query('is_gst');

        $user = auth()->user();
        $query = Purchase::with(['supplier', 'items.product'])
            ->where('status', Purchase::STATUS_APPROVE)
            ->when($user->location_id && !$user->hasRole('super-admin'), function($q) use ($user) {
                $q->whereHas('items.allocations', function($sub) use ($user) {
                    $sub->where('location_id', $user->location_id);
                });
            });

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('is_gst', (bool)$isGst);
        }

        $invoices = $query->latest()->get();

        // Totals
        $totalPurchases = $invoices->sum('total_amount');
        $invoiceCount   = $invoices->count();
        $confirmedCount = $invoiceCount;

        // Purchase by Supplier (Donut Chart)
        $supplierData = [];
        foreach ($invoices->groupBy('supplier_id') as $supId => $grp) {
            $supplierName = $grp->first()->supplier->name ?? 'Unknown';
            $supplierData[$supplierName] = (float)$grp->sum('total_amount');
        }
        arsort($supplierData);

        // Purchases Over Time
        $purchasesTrend = [];
        $trendGroup = $invoices->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        })->sortKeys();
        foreach ($trendGroup as $month => $grp) {
            $purchasesTrend[$month] = (float)$grp->sum('total_amount');
        }

        // Top Purchased Products
        $invoiceIds = $invoices->pluck('id');
        $productPurchases = PurchaseItem::with('product.primaryImage')
            ->whereIn('purchase_id', $invoiceIds)
            ->selectRaw('product_id, SUM(quantity) as qty_purchased, SUM(total) as total_cost')
            ->groupBy('product_id')
            ->get()
            ->sortByDesc('qty_purchased')
            ->values();

        return view('reports.purchases', compact(
            'invoices',
            'suppliers',
            'totalPurchases',
            'invoiceCount',
            'confirmedCount',
            'supplierData',
            'purchasesTrend',
            'productPurchases',
            'startDate',
            'endDate',
            'supplierId',
            'isGst'
        ));
    }

    public function sales(Request $request)
    {
        $this->authorize('view sale reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
            $locationId = $request->query('location_id');
        }
        $customers = Customer::orderBy('name')->get();

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');
        $isGst = $request->query('is_gst');

        $query = Order::with(['customer', 'location', 'user'])
            ->where('order_type', 'sale')
            ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('is_gst', (bool)$isGst);
        }

        $orders = $query->latest()->get();

        // Summary metrics
        $totalSales   = (float)$orders->sum('final_amount');
        $orderCount   = $orders->count();
        $avgOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0.0;
        $paidCount     = $orders->where('payment_status', 2)->count();
        $pendingCount  = $orders->where('payment_status', 1)->count();

        // Sales Over Time
        $salesTrend = [];
        $trendGroup = $orders->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        })->sortKeys();
        foreach ($trendGroup as $month => $grp) {
            $salesTrend[$month] = (float)$grp->sum('final_amount');
        }

        // Sales by Payment Method
        $paymentMethodData = [];
        foreach ($orders->groupBy('payment_method') as $method => $grp) {
            $paymentMethodData[$method] = (float)$grp->sum('final_amount');
        }

        // Top Selling Products
        $orderIds = $orders->pluck('id');
        $productSales = OrderItem::with('product.primaryImage')
            ->whereIn('order_id', $orderIds)
            ->selectRaw('product_id, SUM(quantity) as qty_sold, SUM(total) as total_revenue')
            ->groupBy('product_id')
            ->get()
            ->sortByDesc('qty_sold')
            ->values();

        return view('reports.sales', compact(
            'orders',
            'locations',
            'customers',
            'totalSales',
            'orderCount',
            'avgOrderValue',
            'paidCount',
            'pendingCount',
            'salesTrend',
            'paymentMethodData',
            'productSales',
            'startDate',
            'endDate',
            'locationId',
            'paymentStatus',
            'paymentMethod',
            'isGst'
        ));
    }

    public function profitLoss(Request $request)
    {
        $this->authorize('view profit loss reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $salesQuery = Order::where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));
        if ($startDate) {
            $salesQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($locationId) {
            $salesQuery->where('location_id', $locationId);
        }

        $sales = $salesQuery->get();
        $totalRevenue = (float)$sales->sum('final_amount');

        // COGS query
        $saleIds = $sales->pluck('id');
        $orderItems = OrderItem::with(['product.primaryImage', 'variant'])
            ->whereIn('order_id', $saleIds)
            ->get();

        // Group order items by order_id to distribute order-level discounts proportionally
        $orderItemsGrouped = $orderItems->groupBy('order_id');

        $totalCogs = 0.0;
        $productProfitability = [];

        foreach ($orderItemsGrouped as $orderId => $items) {
            $orderSubtotal = (float)$items->sum('total');
            $order = $sales->firstWhere('id', $orderId);

            $orderDiscountAmount = 0.0;
            if ($order && $orderSubtotal > 0) {
                $discVal = (float)($order->order_discount_value ?? 0);
                $discType = $order->order_discount_type;
                if ($discVal > 0) {
                    if ($discType === 'percentage') {
                        $orderDiscountAmount = $orderSubtotal * ($discVal / 100);
                    } else {
                        // flat discount
                        $orderDiscountAmount = $discVal;
                    }
                }
                if ($orderDiscountAmount > $orderSubtotal) {
                    $orderDiscountAmount = $orderSubtotal;
                }
            }

            foreach ($items as $item) {
                $purchasePrice = $item->variant->purchase_price ?? $item->product->purchase_price ?? 0.0;
                $itemCost = $item->quantity * $purchasePrice;
                $totalCogs += $itemCost;

                // Proportionate net revenue after order-level discount
                $itemTotal = (float)$item->total;
                $effectiveRevenue = $itemTotal;
                if ($orderSubtotal > 0 && $orderDiscountAmount > 0) {
                    $itemShareRatio = $itemTotal / $orderSubtotal;
                    $itemOrderDiscount = $orderDiscountAmount * $itemShareRatio;
                    $effectiveRevenue = max(0.0, $itemTotal - $itemOrderDiscount);
                }

                $productId = $item->product_id;
                if (!isset($productProfitability[$productId])) {
                    $productProfitability[$productId] = [
                        'name'          => $item->product->name ?? 'Unknown',
                        'barcode'       => $item->product->barcode ?? '-',
                        'qty_sold'      => 0,
                        'total_revenue' => 0.0,
                        'total_cost'    => 0.0,
                        'image_url'     => $item->product->primary_image_url ?? null,
                    ];
                }
                $productProfitability[$productId]['qty_sold']      += $item->quantity;
                $productProfitability[$productId]['total_revenue'] += $effectiveRevenue;
                $productProfitability[$productId]['total_cost']    += (float)$itemCost;
            }
        }

        // Latest product first (by product id)
        krsort($productProfitability);

        // Query Expenses
        $expensesQuery = Expense::when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));
        if ($startDate) {
            $expensesQuery->whereDate('expense_date', '>=', $startDate);
        }
        if ($endDate) {
            $expensesQuery->whereDate('expense_date', '<=', $endDate);
        }
        if ($locationId) {
            $expensesQuery->where('location_id', $locationId);
        }
        $expenses = $expensesQuery->get();
        $totalExpenses = (float)$expenses->sum('amount');

        $netProfit = $totalRevenue - $totalCogs - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        // Group Monthly Revenue, COGS, and Expenses
        $salesGroup = $sales->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        })->sortKeys();

        $expensesGroup = $expenses->groupBy(function($item) {
            return $item->expense_date->format('Y-m');
        })->sortKeys();

        // Get all unique months from both sales and expenses
        $allMonths = collect(array_merge(
            $salesGroup->keys()->toArray(),
            $expensesGroup->keys()->toArray()
        ))->unique()->sort()->values();

        $monthlyRevenue = [];
        $monthlyCogs = [];
        $monthlyExpenses = [];

        foreach ($allMonths as $month) {
            // Revenue
            $grp = $salesGroup->get($month);
            $monthlyRevenue[$month] = $grp ? (float)$grp->sum('final_amount') : 0.0;

            // COGS
            $grpCogs = 0.0;
            if ($grp) {
                $grpSaleIds = $grp->pluck('id');
                $grpItems = OrderItem::with(['product', 'variant'])
                    ->whereIn('order_id', $grpSaleIds)
                    ->get();
                foreach ($grpItems as $item) {
                    $purchasePrice = $item->variant->purchase_price ?? $item->product->purchase_price ?? 0.0;
                    $grpCogs += $item->quantity * $purchasePrice;
                }
            }
            $monthlyCogs[$month] = (float)$grpCogs;

            // Expenses
            $grpExp = $expensesGroup->get($month);
            $monthlyExpenses[$month] = $grpExp ? (float)$grpExp->sum('amount') : 0.0;
        }

        return view('reports.profit-loss', compact(
            'locations',
            'totalRevenue',
            'totalCogs',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'productProfitability',
            'monthlyRevenue',
            'monthlyCogs',
            'monthlyExpenses',
            'startDate',
            'endDate',
            'locationId'
        ));
    }

    public function exportProducts(Request $request)
    {
        $this->authorize('view product reports');

        $categoryId = $request->query('category_id');
        $status = $request->query('status');
        $stockStatus = $request->query('stock');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        $query = Product::with(['category', 'primaryImage', 'inventories', 'variants.attributeValue.attribute']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $products = $query->orderBy('name')->get();

        $productsList = collect();
        foreach ($products as $product) {
            $imageBase64 = $this->getImageBase64($product);

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                $parentLocStock  = [];
                $hasVariantStock = false;
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent     = (int) ($variantStock[$location->id]['parent'] ?? 0);
                    if ($locVariantSum > 0) {
                        $hasVariantStock = true;
                        $parentLocStock[$location->id] = $locVariantSum;
                    } else {
                        $parentLocStock[$location->id] = max(0, $locParent);
                    }
                }
                $parentStock = array_sum($parentLocStock);

                if ($parentStock <= 0) {
                    foreach ($locations as $location) {
                        $inventory = $product->inventories->firstWhere('location_id', $location->id);
                        $parentLocStock[$location->id] = $inventory ? (int) $inventory->quantity : 0;
                    }
                    $parentStock = array_sum($parentLocStock);
                }

                $parentPairCount = ($product->pair_product && $parentStock > 0) ? (int) floor($parentStock / $pairSize) : 0;
                $parentLoosePcs  = $product->pair_product ? (int) ($parentStock % $pairSize) : (int) $parentStock;

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $parentStock,
                    'formatted_stock'=> $product->formatStockDisplay($parentStock),
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                    'image_base64'   => $imageBase64,
                    'pair_product'   => (bool) $product->pair_product,
                    'pair_count'     => $parentPairCount,
                    'loose_pcs'      => $parentLoosePcs,
                ]);

                // Variant rows
                foreach ($product->variants as $v) {
                    $vStock = 0;
                    if ($user->location_id && !$user->hasRole('super-admin')) {
                        $vStock = max(0, (int) ($variantStock[$user->location_id]['variants'][$v->id] ?? 0));
                    } else {
                        foreach ($locations as $location) {
                            $vStock += max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                        }
                    }

                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';

                    $vPairCount = ($product->pair_product && $vStock > 0) ? (int) floor($vStock / $pairSize) : 0;
                    $vLoosePcs  = $product->pair_product ? (int) ($vStock % $pairSize) : (int) $vStock;

                    $productsList->push([
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'barcode'        => $product->barcode,
                        'category'       => $product->category->name ?? '-',
                        'category_id'    => $product->category_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price'     => $v->sale_price,
                        'total_stock'    => $vStock,
                        'formatted_stock'=> $product->formatStockDisplay($vStock),
                        'status'         => $v->status,
                        'is_parent'      => false,
                        'variant_name'   => "{$attrName}: {$valName}",
                        'image_base64'   => $imageBase64,
                        'pair_product'   => (bool) $product->pair_product,
                        'pair_count'     => $vPairCount,
                        'loose_pcs'      => $vLoosePcs,
                    ]);
                }
            } else {
                $totalStock = $product->inventories
                    ->when($user->location_id && !$user->hasRole('super-admin'), fn($col) => $col->where('location_id', $user->location_id))
                    ->sum('quantity');

                $pPairCount = ($product->pair_product && $totalStock > 0) ? (int) floor($totalStock / $pairSize) : 0;
                $pLoosePcs  = $product->pair_product ? (int) ($totalStock % $pairSize) : (int) $totalStock;

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $totalStock,
                    'formatted_stock'=> $product->formatStockDisplay($totalStock),
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                    'image_base64'   => $imageBase64,
                    'pair_product'   => (bool) $product->pair_product,
                    'pair_count'     => $pPairCount,
                    'loose_pcs'      => $pLoosePcs,
                ]);
            }
        }

        // Apply filters on the mapped collection
        if ($status) {
            $productsList = $productsList->where('status', $status);
        }
        if ($stockStatus === 'in') {
            $productsList = $productsList->where('total_stock', '>', 0);
        } elseif ($stockStatus === 'out') {
            $productsList = $productsList->where('total_stock', '<=', 0);
        }

        if ($productsList->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Products Report',
                'pdfUrl' => route('admin.reports.products.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $categoryName = $categoryId ? (Category::find($categoryId)?->name ?? 'All') : 'All';

        $pdf = Pdf::loadView('reports.pdf.products', compact('productsList', 'categoryId', 'categoryName', 'status', 'stockStatus'))
            ->setPaper('a4', 'landscape');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Products report exported to PDF');

        return $pdf->stream('products_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportStockInventory(Request $request)
    {
        $this->authorize('view stock inventory reports');

        $categoryId = $request->query('category_id');
        $stockStatus = $request->query('stock');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        $query = Product::with(['category', 'primaryImage', 'inventories.location', 'variants.attributeValue.attribute']);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get();

        $productsList = collect();
        foreach ($products as $product) {
            $purchasePrice = (float) $product->purchase_price;
            $salePrice     = (float) $product->sale_price;
            $mrpPrice      = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

            $imageBase64 = $this->getImageBase64($product);

            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                $parentLocStock          = [];
                $parentFormattedLocStock = [];
                $hasVariantStock         = false;
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent     = (int) ($variantStock[$location->id]['parent'] ?? 0);
                    if ($locVariantSum > 0) {
                        $hasVariantStock = true;
                        $pQty = $locVariantSum;
                    } else {
                        $pQty = max(0, $locParent);
                    }
                    $parentLocStock[$location->id]          = $pQty;
                    $parentFormattedLocStock[$location->id] = $pQty > 0 ? $product->formatStockDisplay($pQty) : '0';
                }
                $parentTotal = array_sum($parentLocStock);

                if ($parentTotal <= 0) {
                    foreach ($locations as $location) {
                        $inventory = $product->inventories->firstWhere('location_id', $location->id);
                        $pQty = $inventory ? (int) $inventory->quantity : 0;
                        $parentLocStock[$location->id]          = $pQty;
                        $parentFormattedLocStock[$location->id] = $pQty > 0 ? $product->formatStockDisplay($pQty) : '0';
                    }
                    $parentTotal = array_sum($parentLocStock);
                    $hasVariantStock = false;
                }

                if ($hasVariantStock) {
                    $parentPurchaseVal = 0.0;
                    $parentSaleVal     = 0.0;
                    $parentMrpVal      = 0.0;
                } else {
                    $effectiveTotal    = $product->pair_product ? ($parentTotal / $pairSize) : (float) $parentTotal;
                    $parentPurchaseVal = $effectiveTotal * $purchasePrice;
                    $parentSaleVal     = $effectiveTotal * $salePrice;
                    $parentMrpVal      = $effectiveTotal * $mrpPrice;
                }
                $parentPairCount = 0;

                $variantRows = [];
                foreach ($product->variants as $v) {
                    $vLocStock          = [];
                    $vFormattedLocStock = [];
                    foreach ($locations as $location) {
                        $vQty = max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                        $vLocStock[$location->id]          = $vQty;
                        $vFormattedLocStock[$location->id] = $vQty > 0 ? $product->formatStockDisplay($vQty) : '0';
                    }
                    $attrName      = $v->attributeValue->attribute->name ?? '';
                    $valName       = $v->attributeValue->value ?? '';
                    $vTotal        = array_sum($vLocStock);
                    $vPrice        = (float) ($v->purchase_price ?? $purchasePrice);
                    $vSale         = (float) ($v->sale_price ?? $salePrice);
                    $vMrp          = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);
                    $vEffectiveQty = $product->pair_product ? ($vTotal / $pairSize) : (float) $vTotal;

                    $vPurchVal = $vEffectiveQty * $vPrice;
                    $vSaleVal  = $vEffectiveQty * $vSale;
                    $vMrpVal   = $vEffectiveQty * $vMrp;

                    $vPairCount = ($product->pair_product && $vTotal > 0) ? (int) floor($vTotal / $pairSize) : 0;
                    $vLoosePcs  = $product->pair_product ? (int) ($vTotal % $pairSize) : (int) $vTotal;

                    if ($hasVariantStock) {
                        $parentPurchaseVal += $vPurchVal;
                        $parentSaleVal     += $vSaleVal;
                        $parentMrpVal      += $vMrpVal;
                        $parentPairCount   += $vPairCount;
                    }

                    $variantRows[] = [
                        'id'                 => $product->id,
                        'name'               => $product->name,
                        'barcode'            => $product->barcode,
                        'category'           => $product->category->name ?? '-',
                        'category_id'        => $product->category_id,
                        'stock'              => $vLocStock,
                        'formatted_loc_stock'=> $vFormattedLocStock,
                        'total'              => $vTotal,
                        'formatted_stock'    => $product->formatStockDisplay($vTotal),
                        'purchase_value'     => $vPurchVal,
                        'sale_value'         => $vSaleVal,
                        'mrp_value'          => $vMrpVal,
                        'status'             => $v->status,
                        'is_parent'          => false,
                        'variant_name'       => "{$attrName}: {$valName}",
                        'image_base64'       => $imageBase64,
                        'pair_product'       => (bool) $product->pair_product,
                        'pair_count'         => $vPairCount,
                        'loose_pcs'          => $vLoosePcs,
                    ];
                }

                if (!$hasVariantStock) {
                    $parentPairCount = ($product->pair_product && $parentTotal > 0) ? (int) floor($parentTotal / $pairSize) : 0;
                }
                $parentLoosePcs = $product->pair_product ? (int) ($parentTotal % $pairSize) : (int) $parentTotal;

                $productsList->push([
                    'id'                 => $product->id,
                    'name'               => $product->name,
                    'barcode'            => $product->barcode,
                    'category'           => $product->category->name ?? '-',
                    'category_id'        => $product->category_id,
                    'stock'              => $parentLocStock,
                    'formatted_loc_stock'=> $parentFormattedLocStock,
                    'total'              => $parentTotal,
                    'formatted_stock'    => $product->formatStockDisplay($parentTotal),
                    'purchase_value'     => $parentPurchaseVal,
                    'sale_value'         => $parentSaleVal,
                    'mrp_value'          => $parentMrpVal,
                    'status'             => $product->status,
                    'is_parent'          => true,
                    'variant_name'       => null,
                    'image_base64'       => $imageBase64,
                    'pair_product'       => (bool) $product->pair_product,
                    'pair_count'         => $parentPairCount,
                    'loose_pcs'          => $parentLoosePcs,
                ]);

                foreach ($variantRows as $vRow) {
                    $productsList->push($vRow);
                }
            } else {
                // Normal product
                $stock = [];
                $formattedLocStock = [];
                foreach ($locations as $location) {
                    $inventory            = $product->inventories->firstWhere('location_id', $location->id);
                    $sQty                 = $inventory ? $inventory->quantity : 0;
                    $stock[$location->id] = $sQty;
                    $formattedLocStock[$location->id] = $sQty > 0 ? $product->formatStockDisplay($sQty) : '0';
                }
                $total        = array_sum($stock);
                $effectiveQty = $product->pair_product ? ($total / $pairSize) : (float) $total;
                $purchaseVal  = $effectiveQty * $purchasePrice;
                $saleVal      = $effectiveQty * $salePrice;
                $mrpVal       = $effectiveQty * $mrpPrice;

                $pPairCount = ($product->pair_product && $total > 0) ? (int) floor($total / $pairSize) : 0;
                $pLoosePcs  = $product->pair_product ? (int) ($total % $pairSize) : (int) $total;

                $productsList->push([
                    'id'                 => $product->id,
                    'name'               => $product->name,
                    'barcode'            => $product->barcode,
                    'category'           => $product->category->name ?? '-',
                    'category_id'        => $product->category_id,
                    'stock'              => $stock,
                    'formatted_loc_stock'=> $formattedLocStock,
                    'total'              => $total,
                    'formatted_stock'    => $product->formatStockDisplay($total),
                    'purchase_value'     => $purchaseVal,
                    'sale_value'         => $saleVal,
                    'mrp_value'          => $mrpVal,
                    'status'             => $product->status,
                    'is_parent'          => true,
                    'variant_name'       => null,
                    'image_base64'       => $imageBase64,
                    'pair_product'       => (bool) $product->pair_product,
                    'pair_count'         => $pPairCount,
                    'loose_pcs'          => $pLoosePcs,
                ]);
            }
        }

        // Apply filters on the mapped collection
        if ($stockStatus === 'in') {
            $productsList = $productsList->where('total', '>', 0);
        } elseif ($stockStatus === 'low') {
            $productsList = $productsList->where('total', '>', 0)->where('total', '<=', 5);
        } elseif ($stockStatus === 'out') {
            $productsList = $productsList->where('total', '<=', 0);
        }

        if ($productsList->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Stock Inventory Report',
                'pdfUrl' => route('admin.reports.stock-inventory.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $categoryName = $categoryId ? (Category::find($categoryId)?->name ?? 'All') : 'All';

        $pdf = Pdf::loadView('reports.pdf.stock-inventory', compact('productsList', 'locations', 'categoryId', 'categoryName', 'stockStatus'))
            ->setPaper('a4', 'landscape');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Stock inventory report exported to PDF');

        return $pdf->stream('stock_inventory_report_' . now()->format('Ymd_His') . '.pdf');
    }

    private function getImageBase64(?Product $product): string
    {
        if (!$product || !$product->primaryImage || empty($product->primaryImage->image_path)) {
            return '';
        }

        $rawPath = ltrim($product->primaryImage->image_path, '/\\');
        $cleanPath = preg_replace('#^uploads[\\/]#i', '', $rawPath);
        
        $imgPath = public_path('uploads/' . $cleanPath);
        if (!file_exists($imgPath)) {
            $imgPath = public_path($rawPath);
        }

        if (!file_exists($imgPath) || !is_file($imgPath)) {
            return '';
        }

        $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));

        // Convert WebP to PNG for DomPDF compatibility
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $im = @imagecreatefromwebp($imgPath);
            if ($im) {
                ob_start();
                imagepng($im);
                $imData = ob_get_clean();
                imagedestroy($im);
                if ($imData) {
                    return 'data:image/png;base64,' . base64_encode($imData);
                }
            }
        }

        $mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');
        $fileContent = @file_get_contents($imgPath);
        if ($fileContent) {
            return 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        }

        return '';
    }

    public function exportPurchases(Request $request)
    {
        $this->authorize('view purchase reports');

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $isGst = $request->query('is_gst');

        $user = auth()->user();
        $query = Purchase::with(['supplier', 'items.product'])
            ->where('status', Purchase::STATUS_APPROVE)
            ->when($user->location_id && !$user->hasRole('super-admin'), function($q) use ($user) {
                $q->whereHas('items.allocations', function($sub) use ($user) {
                    $sub->where('location_id', $user->location_id);
                });
            });

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('is_gst', (bool)$isGst);
        }

        $invoices = $query->latest()->get();
        $invoiceIds = $invoices->pluck('id');

        $productPurchases = PurchaseItem::with('product')
            ->whereIn('purchase_id', $invoiceIds)
            ->selectRaw('product_id, SUM(quantity) as qty_purchased, SUM(total) as total_cost')
            ->groupBy('product_id')
            ->get()
            ->sortByDesc('qty_purchased')
            ->values();

        if ($invoices->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Purchase Report',
                'pdfUrl' => route('admin.reports.purchases.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('reports.pdf.purchases', compact('invoices', 'productPurchases', 'startDate', 'endDate'))
            ->setPaper('a4', 'landscape');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Purchases report exported to PDF');

        return $pdf->stream('purchases_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportSales(Request $request)
    {
        $this->authorize('view sale reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locationId = $user->location_id;
        } else {
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');
        $isGst = $request->query('is_gst');

        $query = Order::with(['customer', 'location', 'user'])
            ->where('order_type', 'sale')
            ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('is_gst', (bool)$isGst);
        }

        $orders = $query->latest()->get();
        $orderIds = $orders->pluck('id');

        $productSales = OrderItem::with('product')
            ->whereIn('order_id', $orderIds)
            ->selectRaw('product_id, SUM(quantity) as qty_sold, SUM(total) as total_revenue')
            ->groupBy('product_id')
            ->get()
            ->sortByDesc('qty_sold')
            ->values();

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Sales Report',
                'pdfUrl' => route('admin.reports.sales.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('reports.pdf.sales', compact('orders', 'productSales', 'startDate', 'endDate'))
            ->setPaper('a4', 'landscape');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Sales report exported to PDF');

        return $pdf->stream('sales_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportProfitLoss(Request $request)
    {
        $this->authorize('view profit loss reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locationId = $user->location_id;
        } else {
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $salesQuery = Order::where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));
        if ($startDate) {
            $salesQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($locationId) {
            $salesQuery->where('location_id', $locationId);
        }

        $sales = $salesQuery->get();
        $totalRevenue = (float)$sales->sum('final_amount');

        $saleIds = $sales->pluck('id');
        $orderItems = OrderItem::with(['product', 'variant'])
            ->whereIn('order_id', $saleIds)
            ->get();

        $orderItemsGrouped = $orderItems->groupBy('order_id');

        $totalCogs = 0.0;
        $productProfitability = [];

        foreach ($orderItemsGrouped as $orderId => $items) {
            $orderSubtotal = (float)$items->sum('total');
            $order = $sales->firstWhere('id', $orderId);

            $orderDiscountAmount = 0.0;
            if ($order && $orderSubtotal > 0) {
                $discVal = (float)($order->order_discount_value ?? 0);
                $discType = $order->order_discount_type;
                if ($discVal > 0) {
                    if ($discType === 'percentage') {
                        $orderDiscountAmount = $orderSubtotal * ($discVal / 100);
                    } else {
                        $orderDiscountAmount = $discVal;
                    }
                }
                if ($orderDiscountAmount > $orderSubtotal) {
                    $orderDiscountAmount = $orderSubtotal;
                }
            }

            foreach ($items as $item) {
                $purchasePrice = $item->variant->purchase_price ?? $item->product->purchase_price ?? 0.0;
                $itemCost = $item->quantity * $purchasePrice;
                $totalCogs += $itemCost;

                $itemTotal = (float)$item->total;
                $effectiveRevenue = $itemTotal;
                if ($orderSubtotal > 0 && $orderDiscountAmount > 0) {
                    $itemShareRatio = $itemTotal / $orderSubtotal;
                    $itemOrderDiscount = $orderDiscountAmount * $itemShareRatio;
                    $effectiveRevenue = max(0.0, $itemTotal - $itemOrderDiscount);
                }

                $productId = $item->product_id;
                if (!isset($productProfitability[$productId])) {
                    $productProfitability[$productId] = [
                        'name'          => $item->product->name ?? 'Unknown',
                        'barcode'       => $item->product->barcode ?? '-',
                        'qty_sold'      => 0,
                        'total_revenue' => 0.0,
                        'total_cost'    => 0.0,
                    ];
                }
                $productProfitability[$productId]['qty_sold']      += $item->quantity;
                $productProfitability[$productId]['total_revenue'] += $effectiveRevenue;
                $productProfitability[$productId]['total_cost']    += (float)$itemCost;
            }
        }

        krsort($productProfitability);

        $expensesQuery = Expense::when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));
        if ($startDate) {
            $expensesQuery->whereDate('expense_date', '>=', $startDate);
        }
        if ($endDate) {
            $expensesQuery->whereDate('expense_date', '<=', $endDate);
        }
        if ($locationId) {
            $expensesQuery->where('location_id', $locationId);
        }
        $totalExpenses = (float) $expensesQuery->sum('amount');

        $netProfit = $totalRevenue - $totalCogs - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        if ($sales->isEmpty() && $totalExpenses <= 0) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Profit & Loss Report',
                'pdfUrl' => route('admin.reports.profit-loss.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('reports.pdf.profit-loss', compact(
            'totalRevenue',
            'totalCogs',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'productProfitability',
            'startDate',
            'endDate'
        ))->setPaper('a4', 'portrait');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Profit & Loss report exported to PDF');

        return $pdf->stream('profit_loss_report_' . now()->format('Ymd_His') . '.pdf');
    }

    // ───────────────────────────────────────────────────────
    //  PAYMENT REPORT
    // ───────────────────────────────────────────────────────
    public function payments(Request $request)
    {
        $this->authorize('view payment reports');

        $startDate     = $request->query('start_date');
        $endDate       = $request->query('end_date');
        $locationId    = $request->query('location_id');
        $source        = $request->query('source');
        $paymentMethod = $request->query('payment_method');
        $paymentStatus = $request->query('payment_status');

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        // Location scoping
        if ($user->location_id && !$isSuperAdmin) {
            $locations  = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        $applyCommonFilters = function ($q) use ($user, $isSuperAdmin, $locationId, $startDate, $endDate, $source, $paymentMethod) {
            if ($user->location_id && !$isSuperAdmin) {
                $q->where('location_id', $user->location_id);
            } elseif ($locationId) {
                $q->where('location_id', $locationId);
            }

            if ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            }
            if ($source) {
                $q->where('source', $source);
            }
            if ($paymentMethod) {
                if ($paymentMethod === 'online') {
                    $q->whereIn('payment_method', ['online', 'razorpay']);
                } else {
                    $q->where('payment_method', $paymentMethod);
                }
            }

            return $q;
        };

        $query = Order::with(['customer', 'payment'])
            ->where('order_type', 'sale')
            ->whereIn('status', [
                Order::STATUS_APPROVE,
                Order::STATUS_SHIPPED,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED,
            ]);
        $applyCommonFilters($query);

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        $orders = $query->latest()->get();

        // ── Summary Stats ──────────────────────────────────
        $totalAmount = (float) $orders->sum('final_amount');
        $totalCount  = $orders->count();
        $avgAmount   = $totalCount > 0 ? $totalAmount / $totalCount : 0.0;
        $pendingOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING);
        $pendingAmount = (float) $pendingOrders->sum('final_amount');
        $pendingCount  = $pendingOrders->count();

        // ── Refunded Orders (cancelled sales with an approved refund) ──
        $refundQuery = Order::with(['customer', 'payment', 'cancellationRequest'])
            ->where('order_type', 'sale')
            ->where('status', Order::STATUS_DECLINE)
            ->whereHas('cancellationRequest', function ($q) {
                $q->where('status', \App\Models\OrderCancellationRequest::STATUS_APPROVED)
                  ->where('refund_amount', '>', 0);
            });
        $applyCommonFilters($refundQuery);

        $refundedOrders = $refundQuery->latest()->get();
        $refundAmount   = (float) $refundedOrders->sum(fn ($order) => (float) $order->cancellationRequest->refund_amount);
        $refundCount    = $refundedOrders->count();

        $normalizePaymentMethod = function (?string $method): string {
            return match ($method) {
                'razorpay' => 'online',
                'cod'      => 'cod',
                'cash'     => 'cash',
                'online'   => 'online',
                default    => (string) $method,
            };
        };

        $paymentMethodLabel = function (?string $method) use ($normalizePaymentMethod): string {
            return match ($normalizePaymentMethod($method)) {
                'cod'    => 'COD',
                'online' => 'Online',
                'cash'   => 'Cash',
                default  => ucwords(str_replace('_', ' ', (string) $method)),
            };
        };

        // ── Payments Over Time (monthly) ────────────────────
        $paymentTrend = [];
        $trendGroup = $orders
            ->groupBy(fn ($order) => $order->created_at->format('Y-m'))
            ->sortKeys();
        foreach ($trendGroup as $month => $grp) {
            $paymentTrend[$month] = (float) $grp->sum('final_amount');
        }

        // ── By Payment Method (donut) ─────────────────────
        $paymentMethodData = [];
        foreach ($orders->groupBy(fn ($order) => $normalizePaymentMethod($order->payment_method)) as $method => $grp) {
            $paymentMethodData[$paymentMethodLabel($method)] = (float) $grp->sum('final_amount');
        }

        // ── By Source (donut) ─────────────────────────────
        $sourceData = [];
        foreach ($orders->groupBy(fn ($order) => $order->source ?? 'POS') as $src => $grp) {
            $sourceData[strtoupper($src)] = (float) $grp->sum('final_amount');
        }

        $availableSources        = ['POS', 'ONLINE'];
        $availablePaymentMethods = ['cash', 'online', 'cod'];

        // Merge refunded orders into the table listing (not into the sales totals/charts above)
        $orders = $orders->merge($refundedOrders)->sortByDesc('created_at')->values();

        return view('reports.payments', compact(
            'orders',
            'locations',
            'totalAmount',
            'totalCount',
            'avgAmount',
            'pendingAmount',
            'pendingCount',
            'refundAmount',
            'refundCount',
            'paymentTrend',
            'paymentMethodData',
            'sourceData',
            'availableSources',
            'availablePaymentMethods',
            'startDate',
            'endDate',
            'source',
            'paymentMethod',
            'paymentStatus',
            'locationId',
            'isSuperAdmin'
        ));
    }

    public function exportPayments(Request $request)
    {
        $this->authorize('view payment reports');

        $startDate     = $request->query('start_date');
        $endDate       = $request->query('end_date');
        $locationId    = $request->query('location_id');
        $source        = $request->query('source');
        $paymentMethod = $request->query('payment_method');
        $paymentStatus = $request->query('payment_status');

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        if ($user->location_id && !$isSuperAdmin) {
            $locationId = $user->location_id;
        }

        $applyCommonFilters = function ($q) use ($user, $isSuperAdmin, $locationId, $startDate, $endDate, $source, $paymentMethod) {
            if ($user->location_id && !$isSuperAdmin) {
                $q->where('location_id', $user->location_id);
            } elseif ($locationId) {
                $q->where('location_id', $locationId);
            }

            if ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            }
            if ($source) {
                $q->where('source', $source);
            }
            if ($paymentMethod) {
                if ($paymentMethod === 'online') {
                    $q->whereIn('payment_method', ['online', 'razorpay']);
                } else {
                    $q->where('payment_method', $paymentMethod);
                }
            }

            return $q;
        };

        $query = Order::with(['customer', 'payment'])
            ->where('order_type', 'sale')
            ->whereIn('status', [
                Order::STATUS_APPROVE,
                Order::STATUS_SHIPPED,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED,
            ]);
        $applyCommonFilters($query);

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        $orders = $query->latest()->get();

        $totalAmount = (float) $orders->sum('final_amount');
        $totalCount  = $orders->count();
        $pendingOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING);
        $pendingAmount = (float) $pendingOrders->sum('final_amount');
        $pendingCount  = $pendingOrders->count();

        $refundQuery = Order::with(['customer', 'payment', 'cancellationRequest'])
            ->where('order_type', 'sale')
            ->where('status', Order::STATUS_DECLINE)
            ->whereHas('cancellationRequest', function ($q) {
                $q->where('status', \App\Models\OrderCancellationRequest::STATUS_APPROVED)
                  ->where('refund_amount', '>', 0);
            });
        $applyCommonFilters($refundQuery);

        $refundedOrders = $refundQuery->latest()->get();
        $refundAmount   = (float) $refundedOrders->sum(fn ($order) => (float) $order->cancellationRequest->refund_amount);
        $refundCount    = $refundedOrders->count();

        $orders = $orders->merge($refundedOrders)->sortByDesc('created_at')->values();

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Payment Report',
                'pdfUrl' => route('admin.reports.payments.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('reports.pdf.payments', compact(
            'orders',
            'totalAmount',
            'totalCount',
            'pendingAmount',
            'pendingCount',
            'refundAmount',
            'refundCount',
            'startDate',
            'endDate'
        ))->setPaper('a4', 'landscape');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Payment report exported to PDF');

        return $pdf->stream('payment_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function dailyReport(Request $request)
    {
        $this->authorize('view daily reports');

        [$date, $locationId, $locations, $isSuperAdmin] = $this->resolveDailyReportFilters($request);

        return view('reports.daily-report', array_merge(
            ['locations' => $locations, 'locationId' => $locationId, 'isSuperAdmin' => $isSuperAdmin, 'date' => $date],
            $this->buildDailyReportData($date, $locationId)
        ));
    }

    public function dailyReportData(Request $request)
    {
        $this->authorize('view daily reports');

        [$date, $locationId] = $this->resolveDailyReportFilters($request);

        return response()->json(array_merge(['status' => 'success', 'date' => $date], $this->buildDailyReportData($date, $locationId)));
    }

    public function exportDailyReport(Request $request)
    {
        $this->authorize('view daily reports');

        [$date, $locationId, $locations, $isSuperAdmin] = $this->resolveDailyReportFilters($request);
        $data = $this->buildDailyReportData($date, $locationId);

        if (
            $data['salesRows']->isEmpty()
            && $data['purchaseRows']->isEmpty()
            && $data['expenseRows']->isEmpty()
            && $data['purchaseBillRows']->isEmpty()
        ) {
            return redirect()->back()->with('error', 'No data found for the selected date. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Daily Report',
                'pdfUrl' => route('admin.reports.daily-report.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $selectedLocation = $locationId ? Location::find($locationId) : null;

        $pdf = Pdf::loadView('reports.pdf.daily-report', array_merge($data, [
            'date'             => $date,
            'selectedLocation' => $selectedLocation,
        ]))->setPaper('a4', 'landscape');

        return $pdf->stream('daily_report_' . $date . '.pdf');
    }

    private function resolveDailyReportFilters(Request $request): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $validator = Validator::make($request->all(), [
            'date'        => ['nullable', 'date_format:Y-m-d'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);
        $date = (!$validator->fails() && $request->query('date'))
            ? $request->query('date')
            : now()->toDateString();

        if ($user->location_id && !$isSuperAdmin) {
            $locations  = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations  = Location::where('status', 1)->orderBy('name')->get();
            $locationId = ($request->query('location_id') && !$validator->fails()) ? $request->query('location_id') : null;
        }

        return [$date, $locationId, $locations, $isSuperAdmin];
    }

    private function buildDailyReportData(string $date, $locationId): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        if ($user->location_id && !$isSuperAdmin) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        // Locations included in this report run: either the single filtered one, or every visible one
        $reportLocations = $locationId
            ? $locations->where('id', $locationId)->values()
            : $locations;
        $locationIds = $reportLocations->pluck('id');

        $orderStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];

        $orderStatusLabels = [1 => 'Pending', 2 => 'Approve', 3 => 'Shipped', 4 => 'Out for delivery', 5 => 'Delivered', 6 => 'Cancelled'];
        $orderStatusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-info', 4 => 'bg-label-warning', 5 => 'bg-label-success', 6 => 'bg-label-danger'];
        $orderPaymentLabels = [1 => 'Pending', 2 => 'Paid'];
        $orderPaymentColors = [1 => 'bg-label-warning', 2 => 'bg-label-info'];
        $purchaseStatusLabels = [1 => 'Pending', 2 => 'Approve', 3 => 'Decline'];
        $purchaseStatusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];
        $purchasePaymentLabels = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
        $purchasePaymentColors = [1 => 'bg-label-warning', 2 => 'bg-label-info', 3 => 'bg-label-primary'];
        $transferStatusLabels = [1 => 'Pending', 2 => 'Accepted', 3 => 'Rejected'];
        $transferStatusColors = [1 => 'bg-label-secondary', 2 => 'bg-label-success', 3 => 'bg-label-danger'];

        $badge = fn (string $label, string $color) => '<span class="badge ' . $color . '">' . $label . '</span>';

        // ── Per-location aggregates (one query each, no N+1) ──────────────

        $salesByLocation = Order::where('order_type', 'sale')
            ->whereIn('status', $orderStatuses)
            ->whereDate('created_at', $date)
            ->whereIn('location_id', $locationIds)
            ->selectRaw('location_id, COUNT(*) as cnt, SUM(final_amount) as total')
            ->groupBy('location_id')
            ->get()
            ->keyBy('location_id');

        $expensesByLocation = Expense::whereDate('expense_date', $date)
            ->whereIn('location_id', $locationIds)
            ->selectRaw('location_id, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('location_id')
            ->get()
            ->keyBy('location_id');

        // Purchases have no direct location_id — every item's allocation carries the branch it was received at.
        $purchasesByLocation = DB::table('purchase_allocations')
            ->join('purchase_items', 'purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereDate('purchases.created_at', $date)
            ->whereIn('purchase_allocations.location_id', $locationIds)
            ->groupBy('purchase_allocations.location_id')
            ->selectRaw('purchase_allocations.location_id as location_id, COUNT(DISTINCT purchases.id) as cnt, SUM(purchase_items.total) as total')
            ->get()
            ->keyBy('location_id');

        // A branch's Purchase Bill activity is counted only when it is the SOURCE (sender) of the transfer.
        $transferFrom = PurchaseBill::join('purchase_bill_items', 'purchase_bill_items.purchase_bill_id', '=', 'purchase_bills.id')
            ->whereDate('purchase_bills.created_at', $date)
            ->whereIn('purchase_bills.from_location_id', $locationIds)
            ->groupBy('purchase_bills.from_location_id')
            ->selectRaw('purchase_bills.from_location_id as location_id, COUNT(DISTINCT purchase_bills.id) as cnt, SUM(purchase_bill_items.quantity) as qty')
            ->get();

        $transfersByLocation = [];
        foreach ($transferFrom as $row) {
            $transfersByLocation[$row->location_id] = ['cnt' => (int) $row->cnt, 'qty' => (int) $row->qty];
        }

        // ── Per-branch breakdown table ────────────────────────────────────
        $branchRows = $reportLocations->map(function ($location) use ($salesByLocation, $expensesByLocation, $purchasesByLocation, $transfersByLocation) {
            $sale     = $salesByLocation->get($location->id);
            $expense  = $expensesByLocation->get($location->id);
            $purchase = $purchasesByLocation->get($location->id);
            $transfer = $transfersByLocation[$location->id] ?? null;

            return [
                'location_name'    => $location->name,
                'sales_amount'     => (float) ($sale->total ?? 0),
                'sales_count'      => (int) ($sale->cnt ?? 0),
                'purchase_amount'  => (float) ($purchase->total ?? 0),
                'purchase_count'   => (int) ($purchase->cnt ?? 0),
                'expense_amount'   => (float) ($expense->total ?? 0),
                'expense_count'    => (int) ($expense->cnt ?? 0),
                'transfer_count'   => (int) ($transfer['cnt'] ?? 0),
                'transfer_qty'     => (int) ($transfer['qty'] ?? 0),
            ];
        })->values();

        // ── Top summary cards (avoid double counting a transfer that touches two visible branches) ──
        $totalSales    = (float) $salesByLocation->sum('total');
        $totalSalesCount = (int) $salesByLocation->sum('cnt');
        $totalPurchases = (float) $purchasesByLocation->sum('total');
        $totalPurchasesCount = (int) $purchasesByLocation->sum('cnt');
        $totalExpenses = (float) $expensesByLocation->sum('total');
        $totalExpensesCount = (int) $expensesByLocation->sum('cnt');

        $transferOverallQuery = PurchaseBill::whereDate('created_at', $date)
            ->when($locationId, fn ($q) => $q->where('from_location_id', $locationId));
        $totalTransfersCount = (clone $transferOverallQuery)->count();
        $totalTransfersQty = PurchaseBillItem::whereHas('transfer', function ($q) use ($date, $locationId) {
            $q->whereDate('created_at', $date);
            if ($locationId) {
                $q->where('from_location_id', $locationId);
            }
        })->sum('quantity');

        // ── Per-module tables for the day (mirrors each module's own list columns, no actions) ──

        $salesRows = Order::with(['customer', 'location'])
            ->where('order_type', 'sale')
            ->whereIn('status', $orderStatuses)
            ->whereDate('created_at', $date)
            ->whereIn('location_id', $locationIds)
            ->latest()
            ->get()
            ->values()
            ->map(function ($order, $index) use ($orderStatusLabels, $orderStatusColors, $orderPaymentLabels, $orderPaymentColors, $badge) {
                return [
                    'index'          => $index + 1,
                    'sale_no'        => $order->order_no,
                    'customer'       => $order->customer->name ?? 'Walk-in',
                    'location'       => $order->location->name ?? '-',
                    'source'         => $order->source ?? 'POS',
                    'amount'         => (float) $order->final_amount,
                    'status'         => $badge($orderStatusLabels[$order->status] ?? '-', $orderStatusColors[$order->status] ?? 'bg-label-secondary'),
                    'payment_status' => $badge($orderPaymentLabels[$order->payment_status] ?? '-', $orderPaymentColors[$order->payment_status] ?? 'bg-label-secondary'),
                    'method'         => $order->payment_method === 'cod' ? 'COD' : ucwords(str_replace('_', ' ', (string) $order->payment_method)),
                ];
            });

        // One purchase's items are always allocated to a single branch, so pluck the first matching
        // (purchase_id => location_id) pair per purchase in a single batch query, avoiding N+1 below.
        $purchaseLocationMap = DB::table('purchase_items')
            ->join('purchase_allocations', 'purchase_allocations.purchase_item_id', '=', 'purchase_items.id')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereDate('purchases.created_at', $date)
            ->whereIn('purchase_allocations.location_id', $locationIds)
            ->select('purchase_items.purchase_id', 'purchase_allocations.location_id')
            ->distinct()
            ->get()
            ->keyBy('purchase_id');

        $purchaseRows = Purchase::with(['supplier'])
            ->whereIn('id', $purchaseLocationMap->keys())
            ->latest()
            ->get()
            ->values()
            ->map(function ($purchase, $index) use ($purchaseStatusLabels, $purchaseStatusColors, $purchasePaymentLabels, $purchasePaymentColors, $badge) {
                return [
                    'index'          => $index + 1,
                    'purchase_no'    => $purchase->invoice_no,
                    'supplier'       => $purchase->supplier->name ?? '-',
                    'total_amount'   => (float) $purchase->total_amount,
                    'status'         => $badge($purchaseStatusLabels[$purchase->status] ?? '-', $purchaseStatusColors[$purchase->status] ?? 'bg-label-secondary'),
                    'payment_status' => $badge($purchasePaymentLabels[$purchase->payment_status] ?? '-', $purchasePaymentColors[$purchase->payment_status] ?? 'bg-label-secondary'),
                ];
            });

        $expenseRows = Expense::with(['location'])
            ->whereDate('expense_date', $date)
            ->whereIn('location_id', $locationIds)
            ->latest()
            ->get()
            ->values()
            ->map(function ($expense, $index) {
                return [
                    'index'          => $index + 1,
                    'title'          => $expense->title,
                    'category'       => $expense->category,
                    'amount'         => (float) $expense->amount,
                    'payment_method' => $expense->payment_method,
                    'location'       => $expense->location->name ?? '-',
                    'expense_date'   => $expense->expense_date->format('d M Y'),
                    'created_by'     => $expense->createdBy->name ?? '-',
                ];
            });

        $purchaseBillRows = PurchaseBill::with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
            ->whereDate('created_at', $date)
            ->whereIn('from_location_id', $locationIds)
            ->withCount('items')
            ->latest()
            ->get()
            ->values()
            ->map(function ($transfer, $index) use ($transferStatusLabels, $transferStatusColors, $badge) {
                $amount = $transfer->items->sum(function ($item) {
                    $price = $item->variant->purchase_price ?? $item->product->purchase_price ?? 0;
                    return $price * $item->quantity;
                });

                return [
                    'index'       => $index + 1,
                    'bill_no'     => $transfer->transfer_no,
                    'source'      => $transfer->fromLocation->name ?? '-',
                    'destination' => $transfer->toLocation->name ?? '-',
                    'items_count' => $transfer->items_count,
                    'amount'      => (float) $amount,
                    'status'      => $badge($transferStatusLabels[$transfer->status] ?? '-', $transferStatusColors[$transfer->status] ?? 'bg-label-secondary'),
                    'created_by'  => $transfer->createdBy->name ?? '-',
                ];
            });

        return [
            'branchRows'          => $branchRows,
            'salesRows'           => $salesRows,
            'purchaseRows'        => $purchaseRows,
            'expenseRows'         => $expenseRows,
            'purchaseBillRows'    => $purchaseBillRows,
            'totalSales'          => $totalSales,
            'totalSalesCount'     => $totalSalesCount,
            'totalPurchases'      => $totalPurchases,
            'totalPurchasesCount' => $totalPurchasesCount,
            'totalExpenses'       => $totalExpenses,
            'totalExpensesCount'  => $totalExpensesCount,
            'totalTransfersCount' => $totalTransfersCount,
            'totalTransfersQty'   => $totalTransfersQty,
        ];
    }
}
