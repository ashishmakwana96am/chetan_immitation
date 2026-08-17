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
            'products' => Product::count(),
            'customers' => Customer::count(),
            'suppliers' => Supplier::count(),
            'users' => User::whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'))->count(),
        ];

        $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];

        $todayOrders = Order::where('order_type', 'sale')->whereIn('status', $approvedStatuses)->whereDate('created_at', today())->with(['payments'])->get();
        $thisMonthOrders = Order::where('order_type', 'sale')->whereIn('status', $approvedStatuses)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->with(['payments'])->get();

        $calcPending = function ($o) {
            if ((int) $o->payment_status === Order::PAYMENT_STATUS_PAID) {
                return 0.0;
            }
            $paid = (float) $o->paid_cash_amount + (float) $o->paid_online_amount;
            if ($paid <= 0) {
                $paid = (float) $o->payments->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
            }
            return max(0.0, (float) $o->final_amount - $paid);
        };

        $calcReceived = function ($o) use ($calcPending) {
            return max(0.0, (float) $o->final_amount - $calcPending($o));
        };

        $allApprovedOrders = Order::where('order_type', 'sale')->whereIn('status', $approvedStatuses)->with(['payments'])->get();

        $salesStats = [
            'today' => (float) $todayOrders->sum('final_amount'),
            'today_pending_payment' => (float) $todayOrders->sum($calcPending),
            'this_month' => (float) $thisMonthOrders->sum('final_amount'),
            'this_month_pending_payment' => (float) $thisMonthOrders->sum($calcPending),
            'total' => (float) $allApprovedOrders->sum('final_amount'),
            'total_received' => (float) $allApprovedOrders->sum($calcReceived),
            'total_pending_payment' => (float) $allApprovedOrders->sum($calcPending),
            'pending' => Order::where('order_type', 'sale')->where('status', Order::STATUS_PENDING)->count(),
        ];

        $customerOutstandingBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true))->sum('balance');
        $cashCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true))->sum('cash_balance');
        $bankCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true))->sum('bank_balance');

        $totalCashBalance = (float) \App\Models\LocationBalance::whereHas('location', fn($q) => $q->where('status', 1))->sum('cash_balance');
        $totalBankBalance = (float) \App\Models\LocationBalance::whereHas('location', fn($q) => $q->where('status', 1))->sum('bank_balance');

        $products = Product::with(['inventories', 'variants', 'category', 'primaryImage'])->get();
        Product::preloadVariantStock($products);
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

            $pTotalPcs = (int) $p->totalAvailableStock();

            if ($p->type === 'variable') {
                $variantStock = $p->getVariantStock();
                $vSum = 0;
                foreach ($p->variants as $v) {
                    $vQty = 0;
                    foreach ($variantStock as $locData) {
                        $vQty += max(0, (int) ($locData['variants'][$v->id] ?? 0));
                    }
                    $vPrice = (float) ($v->purchase_price ?? $purchasePrice);
                    $vMrp = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);

                    $vEffectiveQty = $p->pair_product ? ($vQty / $pairSize) : (float) $vQty;

                    $vSum += $vQty;
                    $totalStockUnits += $vQty;
                    $totalStockPurchaseValue += ($vEffectiveQty * $vPrice);
                    $totalStockMrpValue += ($vEffectiveQty * $vMrp);
                }

                if ($vSum === 0 && $pTotalPcs > 0) {
                    $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;
                    $totalStockUnits += $pTotalPcs;
                    $totalStockPurchaseValue += ($effectiveQty * $purchasePrice);
                    $totalStockMrpValue += ($effectiveQty * $mrpPrice);
                }
            } else {
                $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;

                $totalStockUnits += $pTotalPcs;
                $totalStockPurchaseValue += ($effectiveQty * $purchasePrice);
                $totalStockMrpValue += ($effectiveQty * $mrpPrice);
            }

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
        $lowStock = $products->filter(function ($p) {
            $threshold = $p->category->low_stock_threshold ?? Category::DEFAULT_LOW_STOCK_THRESHOLD;
            return $p->totalAvailableStock() <= $threshold;
        })->take(10)->values();
        $topProducts = OrderItem::with('product.primaryImage')->whereHas('order', fn($q) => $q->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED]))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();
        $salesByLocation = Location::where('status', 1)->get()->map(function ($loc) {
            $locOrders = Order::where('order_type', 'sale')->where('location_id', $loc->id)->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->get();
            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'total_sales' => (float) $locOrders->sum('final_amount'),
                'order_count' => $locOrders->count(),
            ];
        });

        $recentInquiries = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::latest()->take(5)->get()
            : collect();
        $todayInquiriesCount = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::whereDate('created_at', today())->count()
            : 0;

        return view('dashboard.super-admin', compact(
            'stats', 'salesStats', 'stockStats', 'customerOutstandingBalance',
            'cashCreditBalance', 'bankCreditBalance',
            'totalCashBalance', 'totalBankBalance',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts', 'salesByLocation',
            'recentInquiries', 'todayInquiriesCount'
        ));
    }

    private function locationDashboard(?int $locationId)
    {
        $location = Location::find($locationId);

        $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];

        $todayOrders = Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->whereDate('created_at', today())->with(['payments'])->get();
        $thisMonthOrders = Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->with(['payments'])->get();

        $calcPending = function ($o) {
            if ((int) $o->payment_status === Order::PAYMENT_STATUS_PAID) {
                return 0.0;
            }
            $paid = (float) $o->paid_cash_amount + (float) $o->paid_online_amount;
            if ($paid <= 0) {
                $paid = (float) $o->payments->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
            }
            return max(0.0, (float) $o->final_amount - $paid);
        };

        $calcReceived = function ($o) use ($calcPending) {
            return max(0.0, (float) $o->final_amount - $calcPending($o));
        };

        $allApprovedOrders = Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->with(['payments'])->get();

        $salesStats = [
            'today' => (float) $todayOrders->sum('final_amount'),
            'today_pending_payment' => (float) $todayOrders->sum($calcPending),
            'this_month' => (float) $thisMonthOrders->sum('final_amount'),
            'this_month_pending_payment' => (float) $thisMonthOrders->sum($calcPending),
            'total' => (float) $allApprovedOrders->sum('final_amount'),
            'total_received' => (float) $allApprovedOrders->sum($calcReceived),
            'total_pending_payment' => (float) $allApprovedOrders->sum($calcPending),
            'pending' => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_PENDING)->count(),
            'approve' => Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', [2, 3, 4, 5])->count(),
            'decline' => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_DECLINE)->count(),
        ];

        $customerOutstandingBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true)->where('location_id', $locationId))->sum('balance');
        $cashCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true)->where('location_id', $locationId))->sum('cash_balance');
        $bankCreditBalance = (float) CustomerBalance::whereHas('customer', fn($q) => $q->where('is_credit_customer', true)->where('location_id', $locationId))->sum('bank_balance');

        $allProducts = Product::with(['category', 'primaryImage', 'inventories', 'variants'])->get();
        Product::preloadVariantStock($allProducts);

        $invByProduct = Inventory::where('location_id', $locationId)->get()->keyBy('product_id');
        $stockRows = collect();
        foreach ($allProducts as $product) {
            if ($product->type === 'variable') {
                $stockData = $product->getVariantStock($locationId);
                if (!$stockData) {
                    continue;
                }
                $qty = array_sum($stockData['variants']);
                if ($qty === 0) {
                    $qty = (int) ($stockData['parent'] ?? 0);
                }
            } else {
                $inv = $invByProduct->get($product->id);
                if (!$inv) {
                    continue;
                }
                $qty = $inv->quantity;
            }
            $stockRows->push((object) ['product' => $product, 'quantity' => $qty]);
        }

        $lowStockInventories = $stockRows->filter(function ($row) {
            $threshold = $row->product->category->low_stock_threshold ?? Category::DEFAULT_LOW_STOCK_THRESHOLD;
            return $row->quantity <= $threshold;
        });

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

            $pTotalPcs = 0;

            if ($p->type === 'variable') {
                $variantStock = $p->getVariantStock();
                $locData = $variantStock[$locationId] ?? ['variants' => []];
                foreach ($p->variants as $v) {
                    $vQty = (int) ($locData['variants'][$v->id] ?? 0);
                    $vPrice = (float) ($v->purchase_price ?? $purchasePrice);
                    $vMrp = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);

                    $vEffectiveQty = $p->pair_product ? ($vQty / $pairSize) : (float) $vQty;

                    $pTotalPcs += $vQty;
                    $totalStockUnits += $vQty;
                    $totalStockPurchaseValue += ($vEffectiveQty * $vPrice);
                    $totalStockMrpValue += ($vEffectiveQty * $vMrp);
                }

                if ($pTotalPcs === 0) {
                    $inventory = $p->inventories->firstWhere('location_id', $locationId);
                    $invQty = (int) ($inventory ? $inventory->quantity : 0);
                    if ($invQty > 0) {
                        $pTotalPcs = $invQty;
                        $effectiveQty = $p->pair_product ? ($invQty / $pairSize) : (float) $invQty;
                        $totalStockUnits += $invQty;
                        $totalStockPurchaseValue += ($effectiveQty * $purchasePrice);
                        $totalStockMrpValue += ($effectiveQty * $mrpPrice);
                    }
                }
            } else {
                $inventory = $p->inventories->firstWhere('location_id', $locationId);
                $pTotalPcs = (int) ($inventory ? $inventory->quantity : 0);
                $effectiveQty = $p->pair_product ? ($pTotalPcs / $pairSize) : (float) $pTotalPcs;

                $totalStockUnits += $pTotalPcs;
                $totalStockPurchaseValue += ($effectiveQty * $purchasePrice);
                $totalStockMrpValue += ($effectiveQty * $mrpPrice);
            }

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
            'total_products' => $stockRows->count(),
            'total_units' => $totalStockUnits,
            'total_pairs' => $totalStockPairs,
            'total_loose_pcs' => $totalStockLoosePcs,
            'stock_display' => $stockDisplay,
            'stock_parts' => $stockParts,
            'total_purchase_value' => $totalStockPurchaseValue,
            'total_mrp_value' => $totalStockMrpValue,
            'out_of_stock' => $stockRows->where('quantity', 0)->count(),
            'low_stock' => $lowStockInventories->where('quantity', '>', 0)->count(),
        ];

        $monthlySales = $this->getMonthlySales($locationId);
        $recentSales = Order::with(['customer'])->where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->latest()->take(5)->get();
        $lowStock = $lowStockInventories->sortBy('quantity')->take(10)->values();
        $topProducts = OrderItem::with('product.primaryImage')->whereHas('order', fn($q) => $q->where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses))->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total) as total_revenue')->groupBy('product_id')->orderByDesc('total_qty')->take(5)->get();

        $recentInquiries = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::latest()->take(5)->get()
            : collect();
        $todayInquiriesCount = auth()->user()->can('view contact inquiries')
            ? ContactInquiry::whereDate('created_at', today())->count()
            : 0;

        return view('dashboard.location', compact(
            'location', 'salesStats', 'stockStats', 'customerOutstandingBalance',
            'cashCreditBalance', 'bankCreditBalance',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts',
            'recentInquiries', 'todayInquiriesCount'
        ));
    }

    private function getMonthlySales(?int $locationId = null): array
    {
        $approvedStatuses = [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED];
        $rangeStart = now()->subMonths(5)->startOfMonth();

        $calcPending = function ($o) {
            if ((int) $o->payment_status === Order::PAYMENT_STATUS_PAID) {
                return 0.0;
            }
            $paid = (float) $o->paid_cash_amount + (float) $o->paid_online_amount;
            if ($paid <= 0) {
                $paid = (float) $o->payments()->where('status', \App\Models\OrderPayment::STATUS_CAPTURED)->sum('amount');
            }
            return max(0.0, (float) $o->final_amount - $paid);
        };

        $query = Order::where('order_type', 'sale')
            ->whereIn('status', $approvedStatuses)
            ->where('created_at', '>=', $rangeStart);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $allOrders = $query->get();
        $grouped = $allOrders->groupBy(fn($o) => $o->created_at->format('Y-n'));

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->year . '-' . $date->month;
            $monthOrders = $grouped->get($key, collect());

            $amount = (float) $monthOrders->sum('final_amount');
            $pending = (float) $monthOrders->sum($calcPending);
            $received = max(0.0, $amount - $pending);

            $months[] = [
                'month'    => $date->format('M Y'),
                'amount'   => $amount,
                'received' => $received,
                'pending'  => $pending,
                'count'    => (int) $monthOrders->count(),
            ];
        }
        return $months;
    }
}
