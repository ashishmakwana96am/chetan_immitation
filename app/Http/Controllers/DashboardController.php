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

        $customerOutstandingBalance = (float) Customer::where('is_credit_customer', true)->sum('balance');
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
            $salePrice     = (float) $p->sale_price;
            $mrpPrice      = (float) (($p->mrp ?? 0) > 0 ? $p->mrp : $salePrice);

            $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

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
                    $vMrp   = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);

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
            'total_units'          => $totalStockUnits,
            'total_pairs'          => $totalStockPairs,
            'total_loose_pcs'      => $totalStockLoosePcs,
            'stock_display'        => $stockDisplay,
            'stock_parts'          => $stockParts,
            'total_purchase_value' => $totalStockPurchaseValue,
            'total_mrp_value'      => $totalStockMrpValue,
        ];

        $monthlySales   = $this->getMonthlySales();
        $recentSales    = Order::with(['customer', 'location'])->where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->latest()->take(5)->get();
        $lowStock       = $products->filter(function ($p) {
            $threshold = $p->category->low_stock_threshold ?? Category::DEFAULT_LOW_STOCK_THRESHOLD;
            return $p->totalAvailableStock() <= $threshold;
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
            'stats', 'salesStats', 'stockStats', 'customerOutstandingBalance',
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

        $salesStats = [
            'today'      => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->whereDate('created_at', today())->sum('final_amount'),
            'this_month' => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('final_amount'),
            'total'      => (float) Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', $approvedStatuses)->sum('final_amount'),
            'pending'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_PENDING)->count(),
            'approve'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->whereIn('status', [2, 3, 4, 5])->count(),
            'decline'    => Order::where('order_type', 'sale')->where('location_id', $locationId)->where('status', Order::STATUS_DECLINE)->count(),
        ];

        $customerOutstandingBalance = (float) Customer::where('is_credit_customer', true)->where('location_id', $locationId)->sum('balance');

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
            $salePrice     = (float) $p->sale_price;
            $mrpPrice      = (float) (($p->mrp ?? 0) > 0 ? $p->mrp : $salePrice);

            $sizes = collect($p->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($p->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

            $pTotalPcs = 0;

            if ($p->type === 'variable') {
                $variantStock = $p->getVariantStock();
                $locData = $variantStock[$locationId] ?? ['variants' => []];
                foreach ($p->variants as $v) {
                    $vQty   = (int) ($locData['variants'][$v->id] ?? 0);
                    $vPrice = (float) ($v->purchase_price ?? $purchasePrice);
                    $vMrp   = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);

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
            'total_products'       => $stockRows->count(),
            'total_units'          => $totalStockUnits,
            'total_pairs'          => $totalStockPairs,
            'total_loose_pcs'      => $totalStockLoosePcs,
            'stock_display'        => $stockDisplay,
            'stock_parts'          => $stockParts,
            'total_purchase_value' => $totalStockPurchaseValue,
            'total_mrp_value'      => $totalStockMrpValue,
            'out_of_stock'         => $stockRows->where('quantity', 0)->count(),
            'low_stock'            => $lowStockInventories->where('quantity', '>', 0)->count(),
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
            'location', 'salesStats', 'stockStats', 'customerOutstandingBalance',
            'monthlySales', 'recentSales',
            'lowStock', 'topProducts',
            'recentInquiries', 'todayInquiriesCount'
        ));
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

        $rows = $query->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, SUM(final_amount) as amount, COUNT(*) as count')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn($row) => $row->y . '-' . $row->m);

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $row  = $rows->get($date->year . '-' . $date->month);

            $months[] = [
                'month'  => $date->format('M Y'),
                'amount' => (float) ($row->amount ?? 0),
                'count'  => (int) ($row->count ?? 0),
            ];
        }
        return $months;
    }
}
