<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactInquiry;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return $this->superAdminDashboard();
        }

        if ($user->location_id) {
            return $this->locationDashboard($user->location_id);
        }

        return $this->superAdminDashboard();
    }

    private function superAdminDashboard()
    {
        $stats = [
            'products'   => Product::count(),
            'customers'  => Customer::count(),
            'suppliers'  => Supplier::count(),
            'users'      => User::whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'))->count(),
        ];

        $salesStats = [
            'today'      => (float) Order::where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->whereDate('created_at', today())->sum('final_amount'),
            'this_month' => (float) Order::where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount'),
            'total'      => (float) Order::where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->sum('final_amount'),
            'pending'    => Order::where('order_type', 'sale')->where('status', Order::STATUS_PENDING)->count(),
        ];

        $purchaseStats = [
            'confirmed' => (float) Purchase::where('status', Purchase::STATUS_APPROVE)->sum('total_amount'),
            'draft'     => Purchase::where('status', Purchase::STATUS_PENDING)->count(),
        ];

        $monthlySales   = $this->getMonthlySales();
        $recentSales    = Order::with(['customer', 'location'])->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->latest()->take(5)->get();
        $lowStock       = Product::with(['inventories', 'category', 'primaryImage'])->get()->filter(function ($p) {
            $threshold = $p->category->low_stock_threshold ?? Category::DEFAULT_LOW_STOCK_THRESHOLD;
            return $p->inventories->sum('quantity') <= $threshold;
        })->take(10)->values();
        $topProducts    = OrderItem::with('product.primaryImage')->whereHas('order', fn($q) => $q->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED]))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();
        $salesByLocation = Location::withSum(['orders as total_sales' => fn($q) => $q->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])], 'final_amount')
            ->withCount(['orders as total_orders' => fn($q) => $q->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])])
            ->get()
            ->filter(fn($l) => $l->total_sales > 0)
            ->sortByDesc('total_sales')
            ->values();

        $recentInquiries = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::latest()->take(5)->get()
            : collect();
        $todayInquiriesCount = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::whereDate('created_at', today())->count()
            : 0;

        return view('dashboard.super-admin', compact(
            'stats', 'salesStats', 'purchaseStats',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts', 'salesByLocation',
            'recentInquiries', 'todayInquiriesCount'
        ));
    }

    private function locationDashboard(?int $locationId)
    {
        $location = Location::find($locationId);

        $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];

        $salesStats = [
            'today'      => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->whereDate('created_at', today())->sum('final_amount'),
            'this_month' => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount'),
            'total'      => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->sum('final_amount'),
            'pending'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_PENDING)->count(),
            'approve'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', [2, 3, 4, 5])->count(),
            'decline'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_DECLINE)->count(),
        ];

        $lowStockInventories = Inventory::with(['product.category', 'product.primaryImage'])
            ->whereHas('product')
            ->where('location_id', $locationId)
            ->get()
            ->filter(function ($inv) {
                $threshold = $inv->product->category->low_stock_threshold ?? Category::DEFAULT_LOW_STOCK_THRESHOLD;
                return $inv->quantity <= $threshold;
            });

        $stockStats = [
            'total_products' => Inventory::where('location_id', $locationId)->count(),
            'total_units'    => (int) Inventory::where('location_id', $locationId)->sum('quantity'),
            'out_of_stock'   => Inventory::where('location_id', $locationId)->where('quantity', 0)->count(),
            'low_stock'      => $lowStockInventories->where('quantity', '>', 0)->count(),
        ];

        $monthlySales    = $this->getMonthlySales($locationId);
        $recentSales     = Order::with(['customer'])->where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->latest()->take(5)->get();
        $lowStock        = $lowStockInventories->sortBy('quantity')->take(10)->values();
        $topProducts     = OrderItem::with('product.primaryImage')->whereHas('order', fn($q) => $q->where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();

        $recentInquiries = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::latest()->take(5)->get()
            : collect();
        $todayInquiriesCount = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::whereDate('created_at', today())->count()
            : 0;

        return view('dashboard.location', compact(
            'location', 'salesStats', 'stockStats',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts',
            'recentInquiries', 'todayInquiriesCount'
        ));
    }

    private function getMonthlySales(?int $locationId = null): array
    {
        $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $query = Order::where('order_type', 'sale')
                ->whereIn('status', $approvedStatuses)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);

            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            $months[] = [
                'month'  => $date->format('M Y'),
                'amount' => (float) $query->sum('final_amount'),
                'count'  => $query->count(),
            ];
        }
        return $months;
    }
}
