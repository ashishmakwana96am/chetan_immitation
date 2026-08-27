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
            $todayPaid = (float) (clone $approvedQuery)->whereDate('created_at', today())->get()->sum(function ($order) {
                if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
                    return (float) $order->final_amount;
                }
                $cashOnline = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                $walletUsed = 0.0;
                if ($order->customer_id && $order->customer && $order->customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $order->customer_id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                }
                return min((float) $order->final_amount, $cashOnline + $walletUsed);
            });
            $todayPending = max(0.0, $todaySales - $todayPaid);

            $monthSales = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount');
            $monthPaid = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->get()->sum(function ($order) {
                if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
                    return (float) $order->final_amount;
                }
                $cashOnline = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                $walletUsed = 0.0;
                if ($order->customer_id && $order->customer && $order->customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $order->customer_id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                }
                return min((float) $order->final_amount, $cashOnline + $walletUsed);
            });
            $monthPending = max(0.0, $monthSales - $monthPaid);

            $totalSales = (float) (clone $approvedQuery)->sum('final_amount');
            $totalReceived = (float) (clone $approvedQuery)->get()->sum(function ($order) {
                if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
                    return (float) $order->final_amount;
                }
                $cashOnline = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                $walletUsed = 0.0;
                if ($order->customer_id && $order->customer && $order->customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $order->customer_id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                }
                return min((float) $order->final_amount, $cashOnline + $walletUsed);
            });
            $totalPending = max(0.0, $totalSales - $totalReceived);

            $todayCash = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum('paid_cash_amount');
            $todayOnline = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum('paid_online_amount');

            $salesStats = [
                'today' => $todaySales,
                'today_pending_payment' => $todayPending,
                'today_cash' => $todayCash,
                'today_online' => $todayOnline,
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
            $totalStockPurchaseValue = (float) Purchase::where('status', Purchase::STATUS_APPROVE)->sum('total_amount');
            $totalStockMrpValue = 0.0;

            foreach ($products as $p) {
                $salePrice = (float) $p->sale_price;
                $mrpPrice = (float) (($p->mrp ?? 0) > 0 ? $p->mrp : $salePrice);

                $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
                $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
                if ($pairSize <= 0)
                    $pairSize = 1.0;

                $pTotalPcs = (int) $p->inventories->sum('quantity');
                $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;

                $totalStockUnits += $pTotalPcs;
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
            $todayPaid = (float) (clone $approvedQuery)->whereDate('created_at', today())->get()->sum(function ($order) {
                if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
                    return (float) $order->final_amount;
                }
                $cashOnline = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                $walletUsed = 0.0;
                if ($order->customer_id && $order->customer && $order->customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $order->customer_id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                }
                return min((float) $order->final_amount, $cashOnline + $walletUsed);
            });
            $todayPending = max(0.0, $todaySales - $todayPaid);

            $monthSales = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount');
            $monthPaid = (float) (clone $approvedQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->get()->sum(function ($order) {
                if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
                    return (float) $order->final_amount;
                }
                $cashOnline = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                $walletUsed = 0.0;
                if ($order->customer_id && $order->customer && $order->customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $order->customer_id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                }
                return min((float) $order->final_amount, $cashOnline + $walletUsed);
            });
            $monthPending = max(0.0, $monthSales - $monthPaid);

            $totalSales = (float) (clone $approvedQuery)->sum('final_amount');
            $totalReceived = (float) (clone $approvedQuery)->get()->sum(function ($order) {
                if ($order->payment_status == Order::PAYMENT_STATUS_PAID) {
                    return (float) $order->final_amount;
                }
                $cashOnline = (float) $order->paid_cash_amount + (float) $order->paid_online_amount;
                $walletUsed = 0.0;
                if ($order->customer_id && $order->customer && $order->customer->is_credit_customer) {
                    $walletUsed = (float) \App\Models\CustomerBalanceTransaction::where('customer_id', $order->customer_id)
                        ->where('notes', 'LIKE', '%Sale #' . $order->order_number . '%')
                        ->sum('amount');
                }
                return min((float) $order->final_amount, $cashOnline + $walletUsed);
            });
            $totalPending = max(0.0, $totalSales - $totalReceived);

            $todayCash = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum('paid_cash_amount');
            $todayOnline = (float) (clone $approvedQuery)->whereDate('created_at', today())->sum('paid_online_amount');

            $salesStats = [
                'today' => $todaySales,
                'today_pending_payment' => $todayPending,
                'today_cash' => $todayCash,
                'today_online' => $todayOnline,
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
            
            $currentLocation   = Location::find($locationId);
            $isDefaultLocation = $currentLocation && (bool) $currentLocation->is_default;

            $totalStockPurchaseValue = 0.0;
            $totalStockMrpValue      = 0.0;

            if ($isDefaultLocation) {
                $totalStockPurchaseValue = (float) Purchase::where('status', Purchase::STATUS_APPROVE)->sum('total_amount');
                $defaultProducts = Product::whereHas('inventories', fn($q) => $q->where('quantity', '>', 0))->with(['inventories'])->get();

                foreach ($defaultProducts as $p) {
                    $salePrice = (float) $p->sale_price;
                    $mrpPrice  = (float) (($p->mrp ?? 0) > 0 ? $p->mrp : $salePrice);

                    $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
                    $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
                    if ($pairSize <= 0) $pairSize = 1.0;

                    $pTotalPcs = (int) $p->inventories->sum('quantity');
                    $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;
                    $totalStockMrpValue += ($effectiveQty * $mrpPrice);
                }
            } else {
                $incomingBills = \App\Models\PurchaseBill::with(['items.product', 'items.variant'])
                    ->where('to_location_id', $locationId)
                    ->where('status', \App\Models\PurchaseBill::STATUS_ACCEPTED)
                    ->get();

                $incomingPurchaseValue = 0.0;
                $incomingMrpValue      = 0.0;
                foreach ($incomingBills as $bill) {
                    [$billAmount, $billMrp] = $this->purchaseBillTotals($bill);
                    $incomingPurchaseValue += $billAmount;
                    $incomingMrpValue      += $billMrp;
                }

                $outgoingBills = \App\Models\PurchaseBill::with(['items.product', 'items.variant'])
                    ->where('from_location_id', $locationId)
                    ->where('status', \App\Models\PurchaseBill::STATUS_ACCEPTED)
                    ->get();

                $outgoingPurchaseValue = 0.0;
                $outgoingMrpValue      = 0.0;
                foreach ($outgoingBills as $bill) {
                    [$billAmount, $billMrp] = $this->purchaseBillTotals($bill);
                    $outgoingPurchaseValue += $billAmount;
                    $outgoingMrpValue      += $billMrp;
                }

                // 3. Sold Items Purchase Cost & MRP (Sales made by this branch)
                $soldOrderItems = \App\Models\OrderItem::with(['product', 'variant'])
                    ->whereHas('order', function ($q) use ($locationId) {
                        $q->where('location_id', $locationId)
                          ->where('order_type', 'sale')
                          ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
                          ->whereIn('payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL]);
                    })
                    ->get();

                $soldPurchaseValue = 0.0;
                $soldMrpValue      = 0.0;

                foreach ($soldOrderItems as $sItem) {
                    $p = $sItem->product;
                    if (!$p) continue;

                    $purchasePrice = (float) (($sItem->variant->purchase_price ?? 0) > 0 
                        ? $sItem->variant->purchase_price 
                        : ($p->purchase_price ?? 0));

                    $multiplier = $this->stockMultiplierFor($p, $sItem->pair_type, $sItem->custom_size_value);
                    $quantity   = (int) $sItem->quantity;

                    $soldPurchaseValue += $purchasePrice * $quantity;
                    $soldMrpValue      += $this->mrpForOrderItem($sItem, $multiplier) * $quantity;
                }

                // Formula: Incoming - Outgoing - Sold Items
                $totalStockPurchaseValue = max(0.0, $incomingPurchaseValue - $outgoingPurchaseValue - $soldPurchaseValue);
                $totalStockMrpValue      = max(0.0, $incomingMrpValue - $outgoingMrpValue - $soldMrpValue);
            }

            $totalStockPairs = 0;
            $totalStockLoosePcs = 0;
            $totalStockUnits = 0;

            foreach ($allProducts as $p) {
                $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
                $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
                if ($pairSize <= 0)
                    $pairSize = 1.0;

                $inventory = $p->inventories->firstWhere('location_id', $locationId);
                $pTotalPcs = (int) ($inventory ? $inventory->quantity : 0);

                $totalStockUnits += $pTotalPcs;

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

    private function purchaseBillTotals(\App\Models\PurchaseBill $transfer): array
    {
        $totalAmount = 0.0;
        $totalMrp = 0.0;

        foreach ($transfer->items as $item) {
            $multiplier = $this->stockMultiplierFor($item->product, $item->pair_type, $item->custom_size_value);
            $quantity = (int) $item->quantity;

            $totalAmount += $this->purchasePriceForPurchaseBillItem($item) * $quantity;
            $totalMrp += $this->mrpForPurchaseBillItem($item, $multiplier) * $quantity;
        }

        return [$totalAmount, $totalMrp];
    }

    private function stockMultiplierFor(Product $product, ?string $pairType, $customSizeValue = null): float
    {
        if ($customSizeValue !== null && $customSizeValue !== '' && (float)$customSizeValue > 0) {
            return (float) $customSizeValue;
        }

        if (!$product->pair_product) {
            return 1.0;
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

    private function purchasePriceForPurchaseBillItem(\App\Models\PurchaseBillItem $item): float
    {
        $product = $item->product;
        $basePrice = (float) (($item->purchase_price > 0) ? $item->purchase_price : ($item->variant->purchase_price ?? $product?->purchase_price ?? 0));

        if (!$product || !$product->pair_product) {
            return $basePrice;
        }

        $selectedSize = (float) $item->custom_size_value;
        if ($selectedSize <= 0) {
            return $basePrice;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        $maxSize = collect($sizes)
            ->pluck('size')
            ->map(fn ($size) => (float) $size)
            ->filter(fn ($size) => $size > 0)
            ->max();

        if (!$maxSize || $maxSize <= 0) {
            return $basePrice;
        }

        return (float) ($basePrice * ($selectedSize / (float) $maxSize));
    }

    private function mrpForPurchaseBillItem(\App\Models\PurchaseBillItem $item, float $multiplier): float
    {
        $product = $item->product;
        if (!$product) {
            return 0.0;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        if (!empty($sizes)) {
            $value = (float) $item->custom_size_value;
            $matched = null;

            if ($value > 0) {
                $matched = collect($sizes)->first(fn ($row) => abs((float) ($row['size'] ?? 0) - $value) < 0.001);
            }

            if (!$matched) {
                $matched = collect($sizes)->sortBy(fn ($row) => (float) ($row['size'] ?? 0))->last();
            }

            if ($matched && isset($matched['mrp']) && is_numeric($matched['mrp'])) {
                return (float) $matched['mrp'];
            }
        }

        return (float) ($product->mrp ?? 0);
    }

    private function purchasePriceForOrderItem(\App\Models\OrderItem $item): float
    {
        $product = $item->product;
        $basePrice = (float) (($item->variant->purchase_price ?? 0) > 0 ? $item->variant->purchase_price : ($product?->purchase_price ?? 0));

        if (!$product || !$product->pair_product) {
            return $basePrice;
        }

        $selectedSize = (float) $item->custom_size_value;
        if ($selectedSize <= 0) {
            return $basePrice;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        $maxSize = collect($sizes)
            ->pluck('size')
            ->map(fn ($size) => (float) $size)
            ->filter(fn ($size) => $size > 0)
            ->max();

        if (!$maxSize || $maxSize <= 0) {
            return $basePrice;
        }

        return (float) ($basePrice * ($selectedSize / (float) $maxSize));
    }

    private function mrpForOrderItem(\App\Models\OrderItem $item, float $multiplier): float
    {
        $product = $item->product;
        if (!$product) {
            return 0.0;
        }

        $sizes = ($item->variant && !empty($item->variant->custom_sizes))
            ? $item->variant->custom_sizes
            : ($product->custom_sizes ?? []);

        if (!empty($sizes)) {
            $value = (float) $item->custom_size_value;
            $matched = null;

            if ($value > 0) {
                $matched = collect($sizes)->first(fn ($row) => abs((float) ($row['size'] ?? 0) - $value) < 0.001);
            }

            if (!$matched) {
                $matched = collect($sizes)->sortBy(fn ($row) => (float) ($row['size'] ?? 0))->last();
            }

            if ($matched && isset($matched['mrp']) && is_numeric($matched['mrp'])) {
                return (float) $matched['mrp'];
            }
        }

        return (float) (($item->mrp > 0) ? $item->mrp : ($product->mrp ?? 0));
    }
}
