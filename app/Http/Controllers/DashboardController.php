<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactInquiry;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public static function clearDashboardCaches(): void
    {
        Cache::forget('dashboard_super_admin_data');
        try {
            $locations = Location::pluck('id');
            foreach ($locations as $locId) {
                Cache::forget("dashboard_location_data_{$locId}");
            }
        } catch (\Throwable $e) {
            // Ignore if locations table not yet ready
        }
    }
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
        $cachedData = Cache::remember('dashboard_super_admin_data', 1800, function () {
            $stats = [
                'products' => Product::count(),
                'customers' => Customer::count(),
                'suppliers' => Supplier::count(),
                'users' => User::whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'))->count(),
            ];

            $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];

            $approvedQuery = Order::where('order_type', 'sale')->whereIn('status', $approvedStatuses);

            $todaySales = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum('final_amount');
            $todayPaid = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum(\DB::raw('COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)'));
            $todayPending = max(0.0, $todaySales - $todayPaid);

            $monthSales = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount');
            $monthPaid = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum(\DB::raw('COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)'));
            $monthPending = max(0.0, $monthSales - $monthPaid);

            $totalSales = (float) (clone $approvedQuery)->sum('final_amount');
            $totalReceived = (float) (clone $approvedQuery)->sum(\DB::raw('COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)'));
            $totalPending = max(0.0, $totalSales - $totalReceived);

            $salesStats = [
                'today' => $todaySales,
                'today_pending_payment' => $todayPending,
                'this_month' => $monthSales,
                'this_month_pending_payment' => $monthPending,
                'total' => $totalSales,
                'total_received' => $totalReceived,
                'total_pending_payment' => $totalPending,
                'pending' => Order::where('order_type', 'sale')->where('status', Order::STATUS_PENDING)->count(),
            ];

            $customerOutstandingBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true))->sum('balance');
            $cashCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true))->sum('cash_balance');
            $bankCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true))->sum('bank_balance');

            $totalCashBalance = (float) \App\Models\LocationBalance::whereHas('location', fn($q) => $q->where('status', 1))->sum('cash_balance');
            $totalBankBalance = (float) \App\Models\LocationBalance::whereHas('location', fn($q) => $q->where('status', 1))->sum('bank_balance');

            $products = Product::whereHas('inventories', fn($q) => $q->where('quantity', '>', 0))->with(['inventories', 'category'])->get();
            $totalStockUnits = 0;
            $totalStockPairs = 0;
            $totalStockLoosePcs = 0;
            $totalStockPurchaseValue = 0.0;
            $totalStockMrpValue = 0.0;

            foreach ($products as $p) {
                $purchasePrice = (float) $p->purchase_price;
                $salePrice = (float) $p->sale_price;
                $mrpPrice = (float) (($p->mrp ?? 0) > 0 ? $p->mrp : $salePrice);

                $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
                $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
                if ($pairSize <= 0)
                    $pairSize = 1.0;

                $pTotalPcs = (int) $p->inventories->sum('quantity');
                $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;

                $totalStockUnits += $pTotalPcs;
                $totalStockPurchaseValue += ($effectiveQty * $purchasePrice);
                $totalStockMrpValue += ($effectiveQty * $mrpPrice);

                if ($p->pair_product && $pTotalPcs > 0) {
                    $totalStockPairs += (int) floor($pTotalPcs / $pairSize);
                    $totalStockLoosePcs += (int) ($pTotalPcs % $pairSize);
                } elseif (!$p->pair_product) {
                    $totalStockLoosePcs += $pTotalPcs;
                }
            }

            $stockParts = [];
            if ($totalStockPairs > 0) {
                $stockParts[] = number_format($totalStockPairs) . ' Pair' . ($totalStockPairs > 1 ? 's' : '');
            }
            if ($totalStockLoosePcs > 0 || count($stockParts) === 0) {
                $stockParts[] = number_format($totalStockLoosePcs) . ' Pcs';
            }
            $stockDisplay = implode('<br>', $stockParts);

            $stockStats = [
                'total_units' => $totalStockUnits,
                'total_pairs' => $totalStockPairs,
                'total_loose_pcs' => $totalStockLoosePcs,
                'stock_display' => $stockDisplay,
                'stock_parts' => $stockParts,
                'total_purchase_value' => $totalStockPurchaseValue,
                'total_mrp_value' => $totalStockMrpValue,
            ];

            $monthlySales = $this->getMonthlySales();
            $recentSales = Order::with(['customer', 'location'])->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->latest()->take(5)->get();
            $lowStock = Inventory::where('quantity', '<=', 10)->where('quantity', '>', 0)->with(['product.category', 'product.primaryImage'])->orderBy('quantity')->take(10)->get()->pluck('product')->filter()->unique('id')->values();
            $topProducts = OrderItem::with('product.primaryImage')->whereHas('order', fn($q) => $q->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED]))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();
            $locationSalesData = Order::where('order_type', 'sale')
                ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
                ->selectRaw('location_id, SUM(final_amount) as total_sales, COUNT(*) as order_count')
                ->groupBy('location_id')
                ->get()
                ->keyBy('location_id');

            $salesByLocation = Location::where('status', 1)->get()->map(function ($loc) use ($locationSalesData) {
                $data = $locationSalesData->get($loc->id);
                return [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'total_sales' => (float) ($data->total_sales ?? 0),
                    'order_count' => (int) ($data->order_count ?? 0),
                ];
            });

            Product::clearPreloadedVariantStock();

            return compact(
                'stats', 'salesStats', 'stockStats', 'customerOutstandingBalance',
                'cashCreditBalance', 'bankCreditBalance',
                'totalCashBalance', 'totalBankBalance',
                'monthlySales', 'recentSales',
                'lowStock', 'topProducts', 'salesByLocation'
            );
        });

        $recentInquiries = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::latest()->take(5)->get()
            : collect();
        $todayInquiriesCount = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::whereDate('created_at', today())->count()
            : 0;

        return view('dashboard.super-admin', array_merge($cachedData, compact('recentInquiries', 'todayInquiriesCount')));
    }

    private function locationDashboard(?int $locationId)
    {
        $location = Location::find($locationId);

        $cachedData = Cache::remember("dashboard_location_data_{$locationId}", 1800, function () use ($location, $locationId) {
            $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];

            $approvedQuery = Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses);

            $todaySales = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum('final_amount');
            $todayPaid = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum(\DB::raw('COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)'));
            $todayPending = max(0.0, $todaySales - $todayPaid);

            $monthSales = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount');
            $monthPaid = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum(\DB::raw('COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)'));
            $monthPending = max(0.0, $monthSales - $monthPaid);

            $totalSales = (float) (clone $approvedQuery)->sum('final_amount');
            $totalReceived = (float) (clone $approvedQuery)->sum(\DB::raw('COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)'));
            $totalPending = max(0.0, $totalSales - $totalReceived);

            $salesStats = [
                'today' => $todaySales,
                'today_pending_payment' => $todayPending,
                'this_month' => $monthSales,
                'this_month_pending_payment' => $monthPending,
                'total' => $totalSales,
                'total_received' => $totalReceived,
                'total_pending_payment' => $totalPending,
                'pending' => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_PENDING)->count(),
                'approve' => Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', [2, 3, 4, 5])->count(),
                'decline' => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_DECLINE)->count(),
            ];

            $customerOutstandingBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true)->where('location_id', $locationId))->sum('balance');
            $cashCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true)->where('location_id', $locationId))->sum('cash_balance');
            $bankCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true)->where('location_id', $locationId))->sum('bank_balance');

            $allProducts = Product::whereHas('inventories', fn($q) => $q->where('location_id', $locationId)->where('quantity', '>', 0))->with(['category', 'primaryImage', 'inventories'])->get();
            $lowStockInventories = Inventory::where('location_id', $locationId)->where('quantity', '<=', 10)->where('quantity', '>', 0)->with(['product.category', 'product.primaryImage'])->orderBy('quantity')->take(10)->get();

            $totalStockPurchaseValue = 0.0;
            $totalStockMrpValue = 0.0;
            $totalStockPairs = 0;
            $totalStockLoosePcs = 0;
            $totalStockUnits = 0;

            foreach ($allProducts as $p) {
                $purchasePrice = (float) $p->purchase_price;
                $salePrice = (float) $p->sale_price;
                $mrpPrice = (float) (($p->mrp ?? 0) > 0 ? $p->mrp : $salePrice);

                $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
                $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
                if ($pairSize <= 0)
                    $pairSize = 1.0;

                $inventory = $p->inventories->firstWhere('location_id', $locationId);
                $pTotalPcs = (int) ($inventory ? $inventory->quantity : 0);
                $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;

                $totalStockUnits += $pTotalPcs;
                $totalStockPurchaseValue += ($effectiveQty * $purchasePrice);
                $totalStockMrpValue += ($effectiveQty * $mrpPrice);

                if ($p->pair_product && $pTotalPcs > 0) {
                    $totalStockPairs += (int) floor($pTotalPcs / $pairSize);
                    $totalStockLoosePcs += (int) ($pTotalPcs % $pairSize);
                } elseif (!$p->pair_product) {
                    $totalStockLoosePcs += $pTotalPcs;
                }
            }

            $stockParts = [];
            if ($totalStockPairs > 0) {
                $stockParts[] = number_format($totalStockPairs) . ' Pair' . ($totalStockPairs > 1 ? 's' : '');
            }
            if ($totalStockLoosePcs > 0 || count($stockParts) === 0) {
                $stockParts[] = number_format($totalStockLoosePcs) . ' Pcs';
            }
            $stockDisplay = implode('<br>', $stockParts);

            $stockStats = [
                'total_products' => $allProducts->count(),
                'total_units' => $totalStockUnits,
                'total_pairs' => $totalStockPairs,
                'total_loose_pcs' => $totalStockLoosePcs,
                'stock_display' => $stockDisplay,
                'stock_parts' => $stockParts,
                'total_purchase_value' => $totalStockPurchaseValue,
                'total_mrp_value' => $totalStockMrpValue,
                'out_of_stock' => Inventory::where('location_id', $locationId)->where('quantity', 0)->count(),
                'low_stock' => $lowStockInventories->where('quantity', '>', 0)->count(),
            ];

            $monthlySales = $this->getMonthlySales($locationId);
            $recentSales = Order::with(['customer'])->where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->latest()->take(5)->get();
            $lowStock = $lowStockInventories->pluck('product')->filter()->unique('id')->values();
            $topProducts = OrderItem::with('product.primaryImage')->whereHas('order', fn($q) => $q->where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();

            Product::clearPreloadedVariantStock();

            return compact(
                'salesStats', 'stockStats', 'customerOutstandingBalance',
                'cashCreditBalance', 'bankCreditBalance',
                'monthlySales', 'recentSales',
                'lowStock', 'topProducts'
            );
        });

        $recentInquiries = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::latest()->take(5)->get()
            : collect();
        $todayInquiriesCount = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::whereDate('created_at', today())->count()
            : 0;

        return view('dashboard.location', array_merge($cachedData, compact('location', 'recentInquiries', 'todayInquiriesCount')));
    }

    private function getMonthlySales(?int $locationId = null): array
    {
        $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];
        $rangeStart = now()->subMonths(5)->startOfMonth();

        $query = Order::where('order_type', 'sale')
            ->whereIn('status', $approvedStatuses)
            ->where('created_at', '>=', $rangeStart);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $monthlyData = $query->selectRaw('
            YEAR(created_at) as yr,
            MONTH(created_at) as mnth,
            COUNT(*) as total_count,
            SUM(final_amount) as total_amount,
            SUM(COALESCE(paid_cash_amount,0) + COALESCE(paid_online_amount,0)) as total_paid
        ')
        ->groupBy('yr', 'mnth')
        ->get()
        ->keyBy(fn($row) => $row->yr . '-' . $row->mnth);

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->year . '-' . $date->month;
            $row = $monthlyData->get($key);

            $amount = (float) ($row->total_amount ?? 0);
            $received = (float) ($row->total_paid ?? 0);
            $pending = max(0.0, $amount - $received);

            $months[] = [
                'month'    => $date->format('M Y'),
                'amount'   => $amount,
                'received' => $received,
                'pending'  => $pending,
                'count'    => (int) ($row->total_count ?? 0),
            ];
        }
        return $months;
    }
}
