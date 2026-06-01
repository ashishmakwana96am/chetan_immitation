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

class ReportController extends Controller
{
    public function products()
    {
        $this->authorize('view reports');

        $categories = Category::where('status', 'active')->orderBy('name')->get();

        $user = auth()->user();
        $products = Product::with(['category', 'primaryImage', 'inventories'])
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($user) {
                $totalStock = $product->inventories
                    ->when($user->location_id && $user->type !== 'super-admin', fn($col) => $col->where('location_id', $user->location_id))
                    ->sum('quantity');
                return [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'category'       => $product->category->name ?? '-',
                    'category_id'    => $product->category_id,
                    'purchase_price' => $product->purchase_price,
                    'sale_price'     => $product->sale_price,
                    'total_stock'    => $totalStock,
                    'status'         => $product->status,
                ];
            });

        return view('reports.products', compact('products', 'categories'));
    }

    public function stockInventory()
    {
        $this->authorize('view reports');

        $user = auth()->user();
        if ($user->location_id && $user->type !== 'super-admin') {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 'active')->orderBy('name')->get();
        }
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        $products = Product::with(['category', 'inventories.location'])
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($locations) {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory            = $product->inventories->firstWhere('location_id', $location->id);
                    $stock[$location->id] = $inventory ? $inventory->quantity : 0;
                }
                return [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'category'    => $product->category->name ?? '-',
                    'category_id' => $product->category_id,
                    'stock'       => $stock,
                    'total'       => array_sum($stock),
                    'status'      => $product->status,
                ];
            });

        return view('reports.stock-inventory', compact('products', 'locations', 'categories'));
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
            ->when($user->location_id && $user->type !== 'super-admin', function($q) use ($user) {
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
        $confirmedCount = $invoices->where('status', 'confirmed')->count();
        $draftCount     = $invoices->where('status', 'draft')->count();

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
        if ($user->location_id && $user->type !== 'super-admin') {
            $locations = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 'active')->orderBy('name')->get();
            $locationId = $request->query('location_id');
        }
        $customers = Customer::orderBy('name')->get();

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');

        $query = Order::with(['customer', 'location', 'user'])
            ->where('order_type', 'sale')
            ->where('status', '!=', 'cancelled')
            ->when($user->location_id && $user->type !== 'super-admin', fn($q) => $q->where('location_id', $user->location_id));

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
        $paidCount     = $orders->where('payment_status', 'paid')->count();
        $pendingCount  = $orders->where('payment_status', 'pending')->count();

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
        if ($user->location_id && $user->type !== 'super-admin') {
            $locations = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 'active')->orderBy('name')->get();
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $salesQuery = Order::where('order_type', 'sale')->where('status', '!=', 'cancelled')
            ->when($user->location_id && $user->type !== 'super-admin', fn($q) => $q->where('location_id', $user->location_id));
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
}
