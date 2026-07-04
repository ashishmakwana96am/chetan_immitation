<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Services\ReportExportService;
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
        $products = Product::with(['category', 'primaryImage', 'inventories', 'variants.attributeValue.attribute'])
            ->orderBy('name')
            ->get();

        $productsList = collect();
        foreach ($products as $product) {
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();
                
                // Parent row
                $parentStock = 0;
                if ($user->location_id && !$user->hasRole('super-admin')) {
                    $parentStock = $variantStock[$user->location_id]['parent'] ?? 0;
                } else {
                    foreach ($variantStock as $locData) {
                        $parentStock += $locData['parent'];
                    }
                }

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $parentStock,
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                ]);

                // Variant rows
                foreach ($product->variants as $v) {
                    $vStock = 0;
                    if ($user->location_id && !$user->hasRole('super-admin')) {
                        $vStock = $variantStock[$user->location_id]['variants'][$v->id] ?? 0;
                    } else {
                        foreach ($variantStock as $locData) {
                            $vStock += ($locData['variants'][$v->id] ?? 0);
                        }
                    }

                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';

                    $productsList->push([
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'sku'            => $product->sku,
                        'category'       => $product->category->name ?? '-',
                        'category_id'    => $product->category_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price'     => $v->sale_price,
                        'total_stock'    => $vStock,
                        'status'         => $v->status,
                        'is_parent'      => false,
                        'variant_name'   => "{$attrName}: {$valName}",
                    ]);
                }
            } else {
                $totalStock = $product->inventories
                    ->when($user->location_id && !$user->hasRole('super-admin'), fn($col) => $col->where('location_id', $user->location_id))
                    ->sum('quantity');

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $totalStock,
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                ]);
            }
        }

        $totalProducts = Product::count();
        $activeProductCount = Product::where('status', 1)->count();
        $soldoutProductCount = Product::where('status', 1)
            ->whereDoesntHave('inventories', function ($q) {
                $q->where('quantity', '>', 0);
            })
            ->count();

        return view('reports.products', ['products' => $productsList, 'categories' => $categories,'totalProducts' => $totalProducts, 'activeProductCount' => $activeProductCount, 'soldoutProductCount' => $soldoutProductCount]);
    }

    public function stockInventory()
    {
        $this->authorize('view stock inventory reports');

        $user      = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId   = $isRestricted ? $user->location_id : null;

        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }
        $categories = Category::where('status', 1)->orderBy('name')->get();

        $products = Product::with(['category', 'inventories.location', 'variants.attributeValue.attribute'])
            ->orderBy('name')
            ->get();

        $productsList = collect();
        foreach ($products as $product) {
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                $parentLocStock = [];
                foreach ($locations as $location) {
                    $inv = $product->inventories->firstWhere('location_id', $location->id);
                    $parentLocStock[$location->id] = $inv ? $inv->quantity : 0;
                }
                $productsList->push([
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'category'    => $product->category->name ?? '-',
                    'category_id' => $product->category_id,
                    'stock'       => $parentLocStock,
                    'total'       => array_sum($parentLocStock),  // matches Dashboard
                    'status'      => $product->status,
                    'is_parent'   => true,
                    'variant_name'=> null,
                ]);

                foreach ($product->variants as $v) {
                    $vLocStock = [];
                    foreach ($locations as $location) {
                        $vLocStock[$location->id] = $variantStock[$location->id]['variants'][$v->id] ?? 0;
                    }
                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';
                    
                    $productsList->push([
                        'id'          => $product->id,
                        'name'        => $product->name,
                        'sku'         => $product->sku,
                        'category'    => $product->category->name ?? '-',
                        'category_id' => $product->category_id,
                        'stock'       => $vLocStock,
                        'total'       => array_sum($vLocStock),
                        'status'      => $v->status,
                        'is_parent'   => false,
                        'variant_name'=> "{$attrName}: {$valName}",
                    ]);
                }
            } else {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory            = $product->inventories->firstWhere('location_id', $location->id);
                    $stock[$location->id] = $inventory ? $inventory->quantity : 0;
                }
                $productsList->push([
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'category'    => $product->category->name ?? '-',
                    'category_id' => $product->category_id,
                    'stock'       => $stock,
                    'total'       => array_sum($stock),
                    'status'      => $product->status,
                    'is_parent'   => true,
                    'variant_name'=> null,
                ]);
            }
        }

        $activeProductCount = Product::where('status', 1)->count();

        if ($locationId) {
            $soldoutProductCount = Product::where('status', 1)
                ->whereDoesntHave('inventories', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId)->where('quantity', '>', 0);
                })
                ->whereHas('inventories', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                })
                ->count();
        } else {
            $soldoutProductCount = Product::where('status', 1)
                ->whereDoesntHave('inventories', function ($q) {
                    $q->where('quantity', '>', 0);
                })
                ->count();
        }

        return view('reports.stock-inventory', [
            'products'           => $productsList,
            'locations'          => $locations,
            'categories'         => $categories,
            'activeProductCount' => $activeProductCount,
            'soldoutProductCount'=> $soldoutProductCount,
            'isRestricted'       => $isRestricted,
        ]);
    }

    public function purchases(Request $request)
    {
        $this->authorize('view purchase reports');

        $suppliers = Supplier::orderBy('name')->get();
        
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $status     = $request->query('status');

        $user = auth()->user();
        $query = PurchaseInvoice::with(['supplier', 'items.product'])
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
        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->get();

        // Totals
        $totalPurchases = $invoices->sum('total_amount');
        $invoiceCount   = $invoices->count();
        $confirmedCount = $invoices->where('status', 2)->count();
        $draftCount     = $invoices->where('status', 1)->count();

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
        $productPurchases = PurchaseItem::with('product')
            ->whereIn('purchase_invoice_id', $invoiceIds)
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
            'draftCount',
            'supplierData',
            'purchasesTrend',
            'productPurchases',
            'startDate',
            'endDate',
            'supplierId',
            'status'
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
        $productSales = OrderItem::with('product')
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
            'paymentMethod'
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
        $orderItems = OrderItem::with('product')
            ->whereIn('order_id', $saleIds)
            ->get();

        $totalCogs = 0.0;
        $productProfitability = [];

        foreach ($orderItems as $item) {
            $purchasePrice = $item->product->purchase_price ?? 0.0;
            $itemCost = $item->quantity * $purchasePrice;
            $totalCogs += $itemCost;

            $productId = $item->product_id;
            if (!isset($productProfitability[$productId])) {
                $productProfitability[$productId] = [
                    'name'          => $item->product->name ?? 'Unknown',
                    'sku'           => $item->product->sku ?? '-',
                    'qty_sold'      => 0,
                    'total_revenue' => 0.0,
                    'total_cost'    => 0.0,
                ];
            }
            $productProfitability[$productId]['qty_sold']      += $item->quantity;
            $productProfitability[$productId]['total_revenue'] += (float)$item->total;
            $productProfitability[$productId]['total_cost']    += (float)$itemCost;
        }

        // Sort by quantity sold descending so highest sells are first
        uasort($productProfitability, function($a, $b) {
            return $b['qty_sold'] <=> $a['qty_sold'];
        });

        $netProfit = $totalRevenue - $totalCogs;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        // Group Monthly Revenue vs COGS
        $monthlyRevenue = [];
        $monthlyCogs = [];

        $salesGroup = $sales->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        })->sortKeys();

        foreach ($salesGroup as $month => $grp) {
            $monthlyRevenue[$month] = (float)$grp->sum('final_amount');
            
            $grpSaleIds = $grp->pluck('id');
            $grpItems = OrderItem::with('product')
                ->whereIn('order_id', $grpSaleIds)
                ->get();
            
            $grpCogs = 0.0;
            foreach ($grpItems as $item) {
                $grpCogs += $item->quantity * ($item->product->purchase_price ?? 0.0);
            }
            $monthlyCogs[$month] = (float)$grpCogs;
        }

        return view('reports.profit-loss', compact(
            'locations',
            'totalRevenue',
            'totalCogs',
            'netProfit',
            'profitMargin',
            'productProfitability',
            'monthlyRevenue',
            'monthlyCogs',
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
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();
                
                // Parent row
                $parentStock = 0;
                if ($user->location_id && !$user->hasRole('super-admin')) {
                    $parentStock = $variantStock[$user->location_id]['parent'] ?? 0;
                } else {
                    foreach ($variantStock as $locData) {
                        $parentStock += $locData['parent'];
                    }
                }

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $parentStock,
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
                ]);

                // Variant rows
                foreach ($product->variants as $v) {
                    $vStock = 0;
                    if ($user->location_id && !$user->hasRole('super-admin')) {
                        $vStock = $variantStock[$user->location_id]['variants'][$v->id] ?? 0;
                    } else {
                        foreach ($variantStock as $locData) {
                            $vStock += ($locData['variants'][$v->id] ?? 0);
                        }
                    }

                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';

                    $productsList->push([
                        'id'             => $product->id,
                        'name'           => $product->name,
                        'sku'            => $product->sku,
                        'category'       => $product->category->name ?? '-',
                        'category_id'    => $product->category_id,
                        'purchase_price' => $v->purchase_price,
                        'sale_price'     => $v->sale_price,
                        'total_stock'    => $vStock,
                        'status'         => $v->status,
                        'is_parent'      => false,
                        'variant_name'   => "{$attrName}: {$valName}",
                    ]);
                }
            } else {
                $totalStock = $product->inventories
                    ->when($user->location_id && !$user->hasRole('super-admin'), fn($col) => $col->where('location_id', $user->location_id))
                    ->sum('quantity');

                $productsList->push([
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $totalStock,
                    'status'         => $product->status,
                    'is_parent'      => true,
                    'variant_name'   => null,
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

        $spreadsheet = $this->exportService->exportProducts($productsList);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'products_report_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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

        $query = Product::with(['category', 'inventories.location', 'variants.attributeValue.attribute']);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get();

        $productsList = collect();
        foreach ($products as $product) {
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();
                
                // Parent Stock per location
                $parentLocStock = [];
                foreach ($locations as $location) {
                    $parentLocStock[$location->id] = $variantStock[$location->id]['parent'] ?? 0;
                }
                $productsList->push([
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'category'    => $product->category->name ?? '-',
                    'category_id' => $product->category_id,
                    'stock'       => $parentLocStock,
                    'total'       => array_sum($parentLocStock),
                    'status'      => $product->status,
                    'is_parent'   => true,
                    'variant_name'=> null,
                ]);

                // Variants stock per location
                foreach ($product->variants as $v) {
                    $vLocStock = [];
                    foreach ($locations as $location) {
                        $vLocStock[$location->id] = $variantStock[$location->id]['variants'][$v->id] ?? 0;
                    }
                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';
                    
                    $productsList->push([
                        'id'          => $product->id,
                        'name'        => $product->name,
                        'sku'         => $product->sku,
                        'category'    => $product->category->name ?? '-',
                        'category_id' => $product->category_id,
                        'stock'       => $vLocStock,
                        'total'       => array_sum($vLocStock),
                        'status'      => $v->status,
                        'is_parent'   => false,
                        'variant_name'=> "{$attrName}: {$valName}",
                    ]);
                }
            } else {
                // Normal product
                $stock = [];
                foreach ($locations as $location) {
                    $inventory            = $product->inventories->firstWhere('location_id', $location->id);
                    $stock[$location->id] = $inventory ? $inventory->quantity : 0;
                }
                $productsList->push([
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'category'    => $product->category->name ?? '-',
                    'category_id' => $product->category_id,
                    'stock'       => $stock,
                    'total'       => array_sum($stock),
                    'status'      => $product->status,
                    'is_parent'   => true,
                    'variant_name'=> null,
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

        $spreadsheet = $this->exportService->exportStockInventory($productsList, $locations);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'stock_inventory_report_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportPurchases(Request $request)
    {
        $this->authorize('view purchase reports');

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $status     = $request->query('status');

        $user = auth()->user();
        $query = PurchaseInvoice::with(['supplier', 'items.product'])
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
        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->get();
        $invoiceIds = $invoices->pluck('id');

        $productPurchases = PurchaseItem::with('product')
            ->whereIn('purchase_invoice_id', $invoiceIds)
            ->selectRaw('product_id, SUM(quantity) as qty_purchased, SUM(total) as total_cost')
            ->groupBy('product_id')
            ->get()
            ->sortByDesc('qty_purchased')
            ->values();

        $spreadsheet = $this->exportService->exportPurchases($invoices, $productPurchases);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'purchases_report_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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

        $orders = $query->latest()->get();
        $orderIds = $orders->pluck('id');

        $productSales = OrderItem::with('product')
            ->whereIn('order_id', $orderIds)
            ->selectRaw('product_id, SUM(quantity) as qty_sold, SUM(total) as total_revenue')
            ->groupBy('product_id')
            ->get()
            ->sortByDesc('qty_sold')
            ->values();

        $spreadsheet = $this->exportService->exportSales($orders, $productSales);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'sales_report_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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
        $orderItems = OrderItem::with('product')
            ->whereIn('order_id', $saleIds)
            ->get();

        $totalCogs = 0.0;
        $productProfitability = [];

        foreach ($orderItems as $item) {
            $purchasePrice = $item->product->purchase_price ?? 0.0;
            $itemCost = $item->quantity * $purchasePrice;
            $totalCogs += $itemCost;

            $productId = $item->product_id;
            if (!isset($productProfitability[$productId])) {
                $productProfitability[$productId] = [
                    'name'          => $item->product->name ?? 'Unknown',
                    'sku'           => $item->product->sku ?? '-',
                    'qty_sold'      => 0,
                    'total_revenue' => 0.0,
                    'total_cost'    => 0.0,
                ];
            }
            $productProfitability[$productId]['qty_sold']      += $item->quantity;
            $productProfitability[$productId]['total_revenue'] += (float)$item->total;
            $productProfitability[$productId]['total_cost']    += (float)$itemCost;
        }

        uasort($productProfitability, function($a, $b) {
            return $b['qty_sold'] <=> $a['qty_sold'];
        });

        $netProfit = $totalRevenue - $totalCogs;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        $spreadsheet = $this->exportService->exportProfitLoss($totalRevenue, $totalCogs, $netProfit, $profitMargin, $productProfitability);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'profit_loss_report_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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

        $query = Order::with(['customer', 'payment'])
            ->where('order_type', 'sale')
            ->whereIn('status', [
                Order::STATUS_APPROVE,
                Order::STATUS_SHIPPED,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED,
            ]);

        if ($user->location_id && !$isSuperAdmin) {
            $query->where('location_id', $user->location_id);
        } elseif ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($source) {
            $query->where('source', $source);
        }
        if ($paymentMethod) {
            if ($paymentMethod === 'online') {
                $query->whereIn('payment_method', ['online', 'razorpay']);
            } else {
                $query->where('payment_method', $paymentMethod);
            }
        }
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

        return view('reports.payments', compact(
            'orders',
            'locations',
            'totalAmount',
            'totalCount',
            'avgAmount',
            'pendingAmount',
            'pendingCount',
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
}
