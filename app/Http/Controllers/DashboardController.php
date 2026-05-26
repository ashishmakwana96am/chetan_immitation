<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return $this->superAdminDashboard();
        }

        return $this->locationDashboard($user->location_id);
    }

    private function superAdminDashboard()
    {
        $stats = [
            'products'   => Product::count(),
            'customers'  => Customer::count(),
            'suppliers'  => Supplier::count(),
            'users'      => User::where('type', '!=', 'super-admin')->count(),
        ];

        $salesStats = [
            'today'      => (float) Order::where('order_type', 'sale')->whereDate('created_at', today())->sum('final_amount'),
            'this_month' => (float) Order::where('order_type', 'sale')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount'),
            'total'      => (float) Order::where('order_type', 'sale')->sum('final_amount'),
            'pending'    => Order::where('order_type', 'sale')->where('status', 'pending')->count(),
        ];

        $purchaseStats = [
            'confirmed' => (float) PurchaseInvoice::where('status', 'confirmed')->sum('total_amount'),
            'draft'     => PurchaseInvoice::where('status', 'draft')->count(),
        ];

        $monthlySales   = $this->getMonthlySales();
        $recentSales    = Order::with(['customer', 'location'])->where('order_type', 'sale')->latest()->take(6)->get();
        $lowStock       = Product::with(['inventories', 'category'])->get()->filter(fn($p) => $p->inventories->sum('quantity') <= 5)->take(5)->values();
        $topProducts    = OrderItem::with('product')->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();
        $salesByLocation = Location::withSum(['orders as total_sales' => fn($q) => $q->where('order_type', 'sale')], 'final_amount')->withCount(['orders as total_orders' => fn($q) => $q->where('order_type', 'sale')])->get();

        return view('dashboard.super-admin', compact(
            'stats', 'salesStats', 'purchaseStats',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts', 'salesByLocation'
        ));
    }

    private function locationDashboard(?int $locationId)
    {
        $location = Location::find($locationId);

        $salesStats = [
            'today'      => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereDate('created_at', today())->sum('final_amount'),
            'this_month' => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount'),
            'total'      => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->sum('final_amount'),
            'pending'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', 'pending')->count(),
            'completed'  => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', 'completed')->count(),
            'cancelled'  => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', 'cancelled')->count(),
        ];

        $stockStats = [
            'total_products' => Inventory::where('location_id', $locationId)->count(),
            'total_units'    => (int) Inventory::where('location_id', $locationId)->sum('quantity'),
            'out_of_stock'   => Inventory::where('location_id', $locationId)->where('quantity', 0)->count(),
            'low_stock'      => Inventory::where('location_id', $locationId)->where('quantity', '>', 0)->where('quantity', '<=', 5)->count(),
        ];

        $monthlySales    = $this->getMonthlySales($locationId);
        $recentSales     = Order::with(['customer'])->where('order_type', 'sale')->where('location_id', $locationId)->latest()->take(6)->get();
        $lowStock        = Inventory::with(['product.category'])->where('location_id', $locationId)->where('quantity', '<=', 5)->orderBy('quantity')->take(5)->get();
        $topProducts     = OrderItem::with('product')->whereHas('order', fn($q) => $q->where('location_id', $locationId))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();

        return view('dashboard.location', compact(
            'location', 'salesStats', 'stockStats',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts'
        ));
    }

    private function getMonthlySales(?int $locationId = null): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $query = Order::where('order_type', 'sale')
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
