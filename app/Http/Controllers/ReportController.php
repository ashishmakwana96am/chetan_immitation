<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
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
        $subCategories = SubCategory::where('status', 1)->orderBy('name')->get();

        $categoryChartData = SubCategory::withCount('products')
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->take(10)
            ->pluck('products_count', 'name')
            ->toArray();

        if (empty($categoryChartData)) {
            $categoryChartData = Product::select('category_id', DB::raw('COUNT(*) as total'))
                ->groupBy('category_id')
                ->take(10)
                ->get()
                ->mapWithKeys(function($p) {
                    return [$p->category->name ?? 'Default' => (int)$p->total];
                })
                ->toArray();
        }

        $top10Products = Product::select('products.id', 'products.name', DB::raw('COALESCE(SUM(inventories.quantity), 0) as stock'))
            ->leftJoin('inventories', 'products.id', '=', 'inventories.product_id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('stock')
            ->take(10)
            ->get();

        $top10ChartData = [];
        foreach ($top10Products as $p) {
            $top10ChartData[] = [
                'name'  => (string)$p->name,
                'stock' => (int)max(1, $p->stock),
            ];
        }

        $totalProducts = Product::count();
        $activeProductCount = Product::where('status', 1)->count();
        $soldoutProductCount = Product::where('status', 1)
            ->whereDoesntHave('inventories', fn($q) => $q->where('quantity', '>', 0))
            ->count();

        return view('reports.products', [
            'categories'          => $categories,
            'subCategories'       => $subCategories,
            'totalProducts'       => $totalProducts,
            'activeProductCount'  => $activeProductCount,
            'soldoutProductCount' => $soldoutProductCount,
            'categoryChartData'   => $categoryChartData,
            'top10ChartData'      => $top10ChartData,
        ]);
    }

    public function productsData(Request $request)
    {
        $this->authorize('view product reports');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId = $isRestricted ? $user->location_id : null;

        $query = Product::with(['category', 'subCategory', 'primaryImage', 'variants.attributeValue.attribute', 'inventories'])
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->sub_category_id, fn($q) => $q->where('sub_category_id', $request->sub_category_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->input('search.value'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            });

        $recordsTotal = Product::count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) {
            $length = 25;
        }

        $products = $query->orderBy('name')
            ->skip($start)
            ->take($length)
            ->get();

        Product::preloadVariantStock($products);

        $data = [];
        $index = $start + 1;

        foreach ($products as $product) {
            $margin = (float)$product->sale_price - (float)$product->purchase_price;
            $marginPct = $product->purchase_price > 0 ? round(($margin / $product->purchase_price) * 100, 1) : 0;

            $variantsData = [];
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock($locationId);
                $stockByLoc = $locationId ? [$locationId => $variantStock] : $variantStock;

                $parentStock = 0;
                foreach ($stockByLoc as $locData) {
                    if (!$locData) continue;
                    $vSum = array_sum($locData['variants'] ?? []);
                    $parentStock += $vSum > 0 ? $vSum : max(0, (int) ($locData['parent'] ?? 0));
                }
                if ($parentStock <= 0) {
                    $parentStock = (int) $product->inventories
                        ->when($locationId, fn($col) => $col->where('location_id', $locationId))
                        ->sum('quantity');
                }

                foreach ($product->variants as $vIndex => $v) {
                    $vStock = 0;
                    foreach ($stockByLoc as $locData) {
                        if (!$locData) continue;
                        $vStock += max(0, (int) ($locData['variants'][$v->id] ?? 0));
                    }

                    $vMargin = (float)$v->sale_price - (float)$v->purchase_price;
                    $vMarginPct = $v->purchase_price > 0 ? round(($vMargin / $v->purchase_price) * 100, 1) : 0;
                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';

                    $variantsData[] = [
                        'index' => $vIndex + 1,
                        'name' => "{$attrName}: {$valName}",
                        'purchase_price' => format_price($v->purchase_price),
                        'sale_price' => format_price($v->sale_price),
                        'margin_badge' => '<span class="badge ' . ($vMargin >= 0 ? 'bg-label-success' : 'bg-label-danger') . '">' . format_price($vMargin) . ' (' . $vMarginPct . '%)</span>',
                        'stock_badge' => '<span class="badge ' . ($vStock > 0 ? 'bg-label-success' : 'bg-label-danger') . '">' . ($vStock > 0 ? $vStock : 'SOLD OUT') . '</span>',
                        'status_badge' => status_badge($v->status)
                    ];
                }
            } else {
                $parentStock = (int) $product->inventories
                    ->when($locationId, fn($col) => $col->where('location_id', $locationId))
                    ->sum('quantity');
            }

            $hasVariants = count($variantsData) > 0;
            $nameHtml = '<div class="d-flex align-items-center">';
            if ($hasVariants) {
                $nameHtml .= '<button type="button" class="btn btn-icon btn-sm variant-toggle me-2" data-product-id="' . $product->id . '" aria-expanded="false"><i class="ti ti-chevron-right"></i></button>';
            } else {
                $nameHtml .= '<span class="me-2" style="width: 24px;"></span>';
            }
            $nameHtml .= '<img src="' . $product->primary_image_url . '" alt="' . e($product->name) . '" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">';
            $nameHtml .= '<a href="' . route('admin.products.show', $product->id) . '" class="fw-semibold mb-0">' . e($product->name) . '</a></div>';

            $data[] = [
                'index' => $index++,
                'id' => $product->id,
                'name' => $nameHtml,
                'barcode' => '<code>' . e($product->barcode) . '</code>',
                'sub_category' => ($product->subCategory->name ?? null) ? '<span class="badge bg-label-info">' . e($product->subCategory->name) . '</span>' : '<span class="text-muted small">-</span>',
                'purchase_price' => format_price($product->purchase_price),
                'sale_price' => format_price($product->sale_price),
                'margin_badge' => '<span class="badge ' . ($margin >= 0 ? 'bg-label-success' : 'bg-label-danger') . '">' . format_price($margin) . ' (' . $marginPct . '%)</span>',
                'stock_badge' => '<span class="badge ' . ($parentStock > 0 ? 'bg-label-success' : 'bg-label-danger') . '">' . ($parentStock > 0 ? $product->formatStockDisplay($parentStock) : 'SOLD OUT') . '</span>',
                'status_badge' => status_badge($product->status),
                'variants' => $variantsData,
                'has_variants' => $hasVariants
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
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

        $activeProductCount = Product::where('status', 1)->count();
        $soldoutProductCount = Product::where('status', 1)
            ->whereDoesntHave('inventories', function ($q) use ($locationId) {
                $q->where('quantity', '>', 0);
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
            })
            ->count();

        $locationChartData = [];
        foreach ($locations as $location) {
            $locationChartData[] = [
                'name'  => $location->name,
                'stock' => (int) Inventory::where('location_id', $location->id)->sum('quantity'),
            ];
        }

        $top10Products = Product::select('products.id', 'products.name', DB::raw('COALESCE(SUM(inventories.quantity), 0) as stock'))
            ->leftJoin('inventories', 'products.id', '=', 'inventories.product_id')
            ->when($locationId, fn($q) => $q->where('inventories.location_id', $locationId))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('stock')
            ->take(10)
            ->get();

        $top10Breakdown = Inventory::whereIn('product_id', $top10Products->pluck('id'))
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->get()
            ->groupBy('product_id');

        $stackedChartData = [];
        foreach ($top10Products as $p) {
            $row = ['name' => $p->name];
            $productInv = $top10Breakdown->get($p->id, collect());
            foreach ($locations as $location) {
                $row[$location->id] = (int) $productInv->where('location_id', $location->id)->sum('quantity');
            }
            $stackedChartData[] = $row;
        }

        $lowStockCount = Inventory::when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) > 0 AND SUM(quantity) <= 5')
            ->get()
            ->count();

        $overallTotals = $this->computeStockTotals(Product::query(), $locations, $locationId);

        return view('reports.stock-inventory', [
            'locations'          => $locations,
            'categories'         => $categories,
            'activeProductCount' => $activeProductCount,
            'soldoutProductCount'=> $soldoutProductCount,
            'lowStockCount'      => $lowStockCount,
            'locationChartData'  => $locationChartData,
            'stackedChartData'   => $stackedChartData,
            'totalStockDisplay'  => $overallTotals['qty_total'],
            'totalPurchaseValueDisplay' => format_price($overallTotals['purchase_total']),
            'totalMrpValueDisplay'      => format_price($overallTotals['mrp_total']),
            'isRestricted'       => $isRestricted,
            'fromDate'           => $fromDate,
            'toDate'             => $toDate,
        ]);
    }

    private function stockInventoryFilteredQuery(Request $request, ?int $locationId)
    {
        [$stockExprSql, $stockExprBindings] = $this->stockTotalSubquery($locationId);
        [$lastPurchaseExprSql, $lastPurchaseExprBindings] = $this->lastPurchaseSubquery();

        $categoryId = $request->input('category_id');
        $stockFilter = $request->input('stock');
        $minAge = $request->input('min_age');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $search = $request->input('search.value');
        $hasDateFilter = $fromDate || $toDate;

        $query = Product::query()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            });

        if ($stockFilter === 'in') {
            $query->whereRaw("{$stockExprSql} > 0", $stockExprBindings);
        } elseif ($stockFilter === 'low') {
            $query->whereRaw("{$stockExprSql} > 0 AND {$stockExprSql} <= 5", array_merge($stockExprBindings, $stockExprBindings));
        } elseif ($stockFilter === 'out') {
            $query->whereRaw("{$stockExprSql} <= 0", $stockExprBindings);
        }

        if ($hasDateFilter) {
            $sql = "exists (select 1 from purchase_items join purchases on purchases.id = purchase_items.purchase_id
                     where purchase_items.product_id = products.id and purchases.status = ?";
            $bindings = [Purchase::STATUS_APPROVE];
            if ($fromDate) {
                $sql .= ' and date(purchases.created_at) >= ?';
                $bindings[] = $fromDate;
            }
            if ($toDate) {
                $sql .= ' and date(purchases.created_at) <= ?';
                $bindings[] = $toDate;
            }
            $sql .= ')';
            $query->whereRaw($sql, $bindings);
            
            $query->whereRaw("{$stockExprSql} > 0", $stockExprBindings);
        }

        if ($minAge !== null && $minAge !== '') {
            $cutoff = now()->subDays((int) $minAge)->toDateTimeString();
            $query->whereRaw(
                "({$lastPurchaseExprSql} IS NULL OR {$lastPurchaseExprSql} <= ?)",
                array_merge($lastPurchaseExprBindings, $lastPurchaseExprBindings, [$cutoff])
            );
        }

        return $query;
    }

    private function stockTotalSubquery(?int $locationId): array
    {
        $sql = '(select coalesce(sum(quantity), 0) from inventories where inventories.product_id = products.id';
        $bindings = [];
        if ($locationId) {
            $sql .= ' and location_id = ?';
            $bindings[] = $locationId;
        }
        $sql .= ')';

        return [$sql, $bindings];
    }

    private function lastPurchaseSubquery(): array
    {
        $sql = '(select max(purchases.created_at) from purchase_items
                 join purchases on purchases.id = purchase_items.purchase_id
                 where purchase_items.product_id = products.id and purchases.status = ?)';

        return [$sql, [Purchase::STATUS_APPROVE]];
    }

    private function stockInventoryBuildAgeInfo(?string $lastPurchaseAt): array
    {
        if (!$lastPurchaseAt) {
            return [
                'last_purchase_date' => null,
                'last_purchase_display' => '-',
                'age_days' => null,
                'age_display' => 'No Purchase History',
                'age_sort' => PHP_INT_MAX,
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
    }

    public function stockInventoryData(Request $request)
    {
        $this->authorize('view stock inventory reports');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId = $isRestricted ? $user->location_id : null;

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $recordsTotal = Product::count();

        $query = $this->stockInventoryFilteredQuery($request, $locationId);
        $recordsFiltered = (clone $query)->count();

        [$lastPurchaseExprSql, $lastPurchaseExprBindings] = $this->lastPurchaseSubquery();
        $sortBy = $request->input('sort_by');
        $hasDateFilter = $request->input('from_date') || $request->input('to_date');

        if ($sortBy === 'age_desc') {
            $query->orderByRaw("({$lastPurchaseExprSql}) IS NULL DESC, ({$lastPurchaseExprSql}) ASC",
                array_merge($lastPurchaseExprBindings, $lastPurchaseExprBindings));
        } elseif ($sortBy === 'age_asc') {
            $query->orderByRaw("({$lastPurchaseExprSql}) IS NULL ASC, ({$lastPurchaseExprSql}) DESC",
                array_merge($lastPurchaseExprBindings, $lastPurchaseExprBindings));
        } else {
            $direction = $hasDateFilter ? 'ASC' : 'DESC';
            $query->orderByRaw("({$lastPurchaseExprSql}) IS NULL ASC, ({$lastPurchaseExprSql}) {$direction}",
                array_merge($lastPurchaseExprBindings, $lastPurchaseExprBindings));
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) {
            $length = 25;
        }

        $products = $query->with(['category', 'primaryImage', 'inventories', 'variants.attributeValue.attribute'])
            ->skip($start)
            ->take($length)
            ->get();

        Product::preloadVariantStock($products);

        $productIds = $products->pluck('id');
        $lastPurchaseRows = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereIn('purchase_items.product_id', $productIds)
            ->where('purchases.status', Purchase::STATUS_APPROVE)
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

        $data = [];
        $index = $start + 1;

        foreach ($products as $product) {
            $purchasePrice = (float) $product->purchase_price;
            $salePrice     = (float) $product->sale_price;
            $mrpPrice      = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn ($s) => (float) $s)->filter(fn ($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

            $variantsData = [];

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
                    $parentMrpVal      = 0.0;
                } else {
                    $effectiveTotal    = $product->pair_product ? ($parentTotal / $pairSize) : (float) $parentTotal;
                    $parentPurchaseVal = $effectiveTotal * $purchasePrice;
                    $parentMrpVal      = $effectiveTotal * $mrpPrice;
                }

                foreach ($product->variants as $vIndex => $v) {
                    $vLocStock = [];
                    foreach ($locations as $location) {
                        $vLocStock[$location->id] = max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                    }
                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName  = $v->attributeValue->value ?? '';
                    $vTotal   = array_sum($vLocStock);
                    $vPrice   = (float) ($v->purchase_price ?? $purchasePrice);
                    $vMrp     = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);
                    $vEffectiveQty = $product->pair_product ? ($vTotal / $pairSize) : (float) $vTotal;
                    $vPurchVal = $vEffectiveQty * $vPrice;
                    $vMrpVal   = $vEffectiveQty * $vMrp;

                    if ($hasVariantStock) {
                        $parentPurchaseVal += $vPurchVal;
                        $parentMrpVal      += $vMrpVal;
                    }

                    $variantKey = $product->id . ':' . $v->id;
                    $ageInfo = $this->stockInventoryBuildAgeInfo($variantLastPurchase[$variantKey] ?? null);

                    $locBadges = '';
                    foreach ($locations as $location) {
                        $qty = $vLocStock[$location->id] ?? 0;
                        $locBadges .= '<td class="text-center"><span class="badge ' . ($qty > 5 ? 'bg-label-success' : ($qty > 0 ? 'bg-label-warning' : 'bg-label-secondary')) . '">' . $product->formatStockDisplay($qty) . '</span></td>';
                    }

                    $variantsData[] = [
                        'index' => $vIndex + 1,
                        'name' => "{$attrName}: {$valName}",
                        'last_purchase_display' => $ageInfo['last_purchase_display'],
                        'loc_badges' => $locBadges,
                        'total_badge' => '<span class="badge ' . ($vTotal > 5 ? 'bg-label-success' : ($vTotal > 0 ? 'bg-label-warning' : 'bg-label-danger')) . ' fw-bold">' . $product->formatStockDisplay($vTotal) . '</span>',
                        'purchase_value' => format_price($vPurchVal),
                        'mrp_value' => format_price($vMrpVal),
                        'age_badge' => $this->stockInventoryAgeBadge($ageInfo),
                    ];
                }

                $totalStock = $parentTotal;
                $purchaseValue = $parentPurchaseVal;
                $mrpValue = $parentMrpVal;
            } else {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory = $product->inventories->firstWhere('location_id', $location->id);
                    $stock[$location->id] = $inventory ? $inventory->quantity : 0;
                }
                $totalStock = array_sum($stock);
                $effectiveQty = $product->pair_product ? ($totalStock / $pairSize) : (float) $totalStock;
                $purchaseValue = $effectiveQty * $purchasePrice;
                $mrpValue = $effectiveQty * $mrpPrice;
                $parentLocStock = $stock;
            }

            $ageInfo = $this->stockInventoryBuildAgeInfo($productLastPurchase[$product->id] ?? null);
            $hasVariants = count($variantsData) > 0;

            $nameHtml = '<div class="d-flex align-items-center">';
            $nameHtml .= $hasVariants
                ? '<button type="button" class="btn btn-icon btn-sm variant-toggle me-2" data-product-id="' . $product->id . '" aria-expanded="false"><i class="ti ti-chevron-right"></i></button>'
                : '<span class="me-2" style="width: 24px;"></span>';
            $nameHtml .= '<img src="' . $product->primary_image_url . '" alt="' . e($product->name) . '" class="rounded me-2 product-thumbnail" style="width: 32px; height: 32px; object-fit: cover;">';
            $nameHtml .= '<a href="' . route('admin.products.show', $product->id) . '" class="fw-semibold">' . e($product->name) . '</a></div>';

            $locBadges = '';
            foreach ($locations as $location) {
                $qty = $parentLocStock[$location->id] ?? 0;
                $locBadges .= '<td class="text-center"><span class="badge ' . ($qty > 5 ? 'bg-label-success' : ($qty > 0 ? 'bg-label-warning' : 'bg-label-secondary')) . '">' . $product->formatStockDisplay($qty) . '</span></td>';
            }

            $data[] = [
                'index' => $index++,
                'id' => $product->id,
                'name' => $nameHtml,
                'last_purchase_display' => $ageInfo['last_purchase_display'],
                'barcode' => '<code>' . e($product->barcode) . '</code>',
                'category' => '<span class="badge bg-label-primary">' . e($product->category->name ?? '-') . '</span>',
                'loc_badges' => $locBadges,
                'total_badge' => '<span class="badge ' . ($totalStock > 5 ? 'bg-label-success' : ($totalStock > 0 ? 'bg-label-warning' : 'bg-label-danger')) . ' fw-bold">' . $product->formatStockDisplay($totalStock) . '</span>',
                'purchase_value' => format_price($purchaseValue),
                'mrp_value' => format_price($mrpValue),
                'age_badge' => $this->stockInventoryAgeBadge($ageInfo),
                'variants' => $variantsData,
                'has_variants' => $hasVariants,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function stockInventoryAgeBadge(array $ageInfo): string
    {
        if (is_null($ageInfo['age_days'])) {
            return '<span class="badge bg-label-secondary">' . $ageInfo['age_display'] . '</span>';
        }
        if ($ageInfo['age_days'] >= 180) {
            return '<span class="badge bg-label-danger">' . $ageInfo['age_display'] . '</span>';
        }
        if ($ageInfo['age_days'] >= 90) {
            return '<span class="badge bg-label-warning">' . $ageInfo['age_display'] . '</span>';
        }
        return '<span class="badge bg-label-success">' . $ageInfo['age_display'] . '</span>';
    }

    public function stockInventoryTotals(Request $request)
    {
        $this->authorize('view stock inventory reports');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId = $isRestricted ? $user->location_id : null;

        $locations = $isRestricted
            ? Location::where('id', $user->location_id)->get()
            : Location::where('status', 1)->orderBy('name')->get();

        $totals = $this->computeStockTotals($this->stockInventoryFilteredQuery($request, $locationId), $locations, $locationId);

        return response()->json([
            'location_totals' => $totals['location_totals'],
            'qty_total' => $totals['qty_total'],
            'purchase_total' => format_price($totals['purchase_total']),
            'mrp_total' => format_price($totals['mrp_total']),
        ]);
    }

    /**
     * Aggregates pair/pcs + purchase/MRP value totals (overall and per-location) for a
     * product query, using a plain inventories-table sum per product (not the ledger-aware
     * getVariantStock()) — an approximation for variable products with parent/variant stock
     * desync, accepted so this can run over the full (often 1000+) catalog on every filter
     * change without recomputing each product's purchase/sale/transfer ledger.
     */
    private function computeStockTotals($productQuery, $locations, ?int $locationId): array
    {
        $products = $productQuery
            ->select('id', 'pair_product', 'custom_sizes', 'purchase_price', 'sale_price', 'mrp')
            ->get();

        $invByProduct = Inventory::whereIn('product_id', $products->pluck('id'))
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->get()
            ->groupBy('product_id');

        $locationPairTotals = [];
        $locationLooseTotals = [];
        foreach ($locations as $location) {
            $locationPairTotals[$location->id] = 0;
            $locationLooseTotals[$location->id] = 0;
        }

        $totalPairUnits = 0;
        $totalLoosePcs = 0;
        $totalPurchaseValue = 0.0;
        $totalMrpValue = 0.0;

        foreach ($products as $product) {
            $invRows = $invByProduct->get($product->id, collect());
            $totalQty = 0;

            foreach ($locations as $location) {
                $qty = (int) $invRows->where('location_id', $location->id)->sum('quantity');
                $totalQty += $qty;

                if ($product->pair_product && $qty > 0) {
                    $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn ($s) => (float) $s)->filter(fn ($s) => $s > 0);
                    $pairSize = $sizes->count() > 0 ? (float) $sizes->max() : 1.0;
                    if ($pairSize <= 0) $pairSize = 1.0;
                    $locationPairTotals[$location->id] += (int) floor($qty / $pairSize);
                    $locationLooseTotals[$location->id] += (int) ($qty % $pairSize);
                } else {
                    $locationLooseTotals[$location->id] += $qty;
                }
            }

            if ($totalQty <= 0) {
                continue;
            }

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn ($s) => (float) $s)->filter(fn ($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0) $pairSize = 1.0;

            if ($product->pair_product) {
                $totalPairUnits += (int) floor($totalQty / $pairSize);
                $totalLoosePcs  += (int) ($totalQty % $pairSize);
            } else {
                $totalLoosePcs += $totalQty;
            }

            $mrpPrice = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);
            $effectiveQty = $product->pair_product ? ($totalQty / $pairSize) : (float) $totalQty;
            $totalPurchaseValue += $effectiveQty * (float) $product->purchase_price;
            $totalMrpValue      += $effectiveQty * $mrpPrice;
        }

        $formatPairsPcs = function ($pairs, $pcs) {
            $parts = [];
            if ($pairs > 0) {
                $parts[] = number_format($pairs) . ' Pair' . ($pairs > 1 ? 's' : '');
            }
            if ($pcs > 0 || count($parts) === 0) {
                $parts[] = number_format($pcs) . ' Pcs';
            }
            return implode('<br>', $parts);
        };

        $locationTotals = [];
        foreach ($locations as $location) {
            $locationTotals[$location->id] = $formatPairsPcs($locationPairTotals[$location->id], $locationLooseTotals[$location->id]);
        }

        return [
            'location_totals' => $locationTotals,
            'qty_total' => $formatPairsPcs($totalPairUnits, $totalLoosePcs),
            'purchase_total' => $totalPurchaseValue,
            'mrp_total' => $totalMrpValue,
        ];
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
        $query = Purchase::with(['supplier:id,name'])
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
        $totalPurchases     = $invoices->sum('total_amount');
        $invoiceCount       = $invoices->count();
        $confirmedCount     = $invoiceCount;
        $totalPendingAmount = (float) $invoices->sum(fn($i) => max(0, (float) $i->total_amount - (float) $i->paid_amount));

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
            'totalPendingAmount',
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
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
            $locationId = $request->query('location_id');
        }
        $customers = Customer::when($isRestricted, fn($q) => $q->where('location_id', $user->location_id))
            ->orderBy('name')->get();

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
        $paidCount     = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->count();
        $partialCount  = $orders->where('payment_status', Order::PAYMENT_STATUS_PARTIAL)->count();
        $pendingCount  = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING)->count();

        // Calculate pending amount across all non-declined orders matching filter
        $pendingQuery = Order::where('order_type', 'sale')
            ->where('status', '!=', Order::STATUS_DECLINE)
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('location_id', $user->location_id));

        if ($startDate) {
            $pendingQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $pendingQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($locationId) {
            $pendingQuery->where('location_id', $locationId);
        }
        if ($paymentStatus) {
            $pendingQuery->where('payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $pendingQuery->where('payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $pendingQuery->where('is_gst', (bool)$isGst);
        }

        $allPendingOrders = $pendingQuery->get();
        $totalPendingAmount = (float)$allPendingOrders->sum(function($o) {
            if ((int)$o->payment_status === Order::PAYMENT_STATUS_PAID) return 0;
            return max(0, (float)$o->final_amount - ((float)$o->paid_cash_amount + (float)$o->paid_online_amount));
        });

        // Sales Over Time
        $salesTrend = [];
        $trendGroup = $orders->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        })->sortKeys();
        foreach ($trendGroup as $month => $grp) {
            $salesTrend[$month] = (float)$grp->sum('final_amount');
        }

        // Sales by Payment Method — attribute actual money collected to Cash/Online,
        // ignoring Pending orders (nothing collected yet) and never showing a
        // separate "Pending"/"Cash + Online" bucket.
        $cashTotal = 0.0;
        $onlineTotal = 0.0;
        foreach ($orders as $order) {
            if (!in_array((int) $order->payment_status, [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL], true)) {
                continue;
            }
            $cashAmt = (float) $order->paid_cash_amount;
            $onlineAmt = (float) $order->paid_online_amount;
            if ($cashAmt <= 0 && $onlineAmt <= 0 && (int) $order->payment_status === Order::PAYMENT_STATUS_PAID) {
                $pm = strtolower($order->payment_method ?? '');
                if (in_array($pm, ['online', 'razorpay'])) {
                    $onlineAmt = (float) $order->final_amount;
                } else {
                    $cashAmt = (float) $order->final_amount;
                }
            }
            $cashTotal += $cashAmt;
            $onlineTotal += $onlineAmt;
        }
        $paymentMethodData = [];
        if ($cashTotal > 0) {
            $paymentMethodData['Cash'] = $cashTotal;
        }
        if ($onlineTotal > 0) {
            $paymentMethodData['Online'] = $onlineTotal;
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
            'partialCount',
            'pendingCount',
            'totalPendingAmount',
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

        $salesQuery = Order::where('order_type', 'sale')
            ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
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
        $totalRevenue = (float)$sales->sum(function($o) {
            return (float)$o->paid_cash_amount + (float)$o->paid_online_amount;
        });

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
            $monthlyRevenue[$month] = $grp ? (float)$grp->sum(fn($o) => (float)$o->paid_cash_amount + (float)$o->paid_online_amount) : 0.0;

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

        $salesQuery = Order::where('order_type', 'sale')
            ->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
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
        $totalRevenue = (float)$sales->sum(function($o) {
            return (float)$o->paid_cash_amount + (float)$o->paid_online_amount;
        });

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

        $totalAmount = (float) $orders->sum('final_amount');
        $totalCount  = $orders->count();
        $avgAmount   = $totalCount > 0 ? $totalAmount / $totalCount : 0.0;
        
        $pendingOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING);
        $pendingFullAmount = (float) $pendingOrders->sum('final_amount');

        $partialOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PARTIAL);
        $partialDueAmount = (float) $partialOrders->sum(function ($order) {
            $paid = (float) ($order->paid_cash_amount ?? 0) + (float) ($order->paid_online_amount ?? 0);
            return max(0, (float) $order->final_amount - $paid);
        });

        $pendingAmount = $pendingFullAmount + $partialDueAmount;
        $pendingCount  = $pendingOrders->count() + $partialOrders->count();

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
                'razorpay'    => 'online',
                'cod'         => 'cod',
                'cash'        => 'cash',
                'online'      => 'online',
                'online_cash' => 'online_cash',
                null, ''      => 'pending',
                default       => (string) $method,
            };
        };

        $paymentMethodLabel = function (?string $method) use ($normalizePaymentMethod): string {
            return match ($normalizePaymentMethod($method)) {
                'cod'         => 'COD',
                'online'      => 'Online',
                'cash'        => 'Cash',
                'online_cash' => 'Cash + Online',
                'pending'     => 'Pending',
                default       => ucwords(str_replace('_', ' ', (string) $method)),
            };
        };

        $paymentTrend = [];
        $trendGroup = $orders
            ->groupBy(fn ($order) => $order->created_at->format('Y-m'))
            ->sortKeys();
        foreach ($trendGroup as $month => $grp) {
            $paymentTrend[$month] = (float) $grp->sum('final_amount');
        }

        $cashTotal = 0.0;
        $onlineTotal = 0.0;
        foreach ($orders as $order) {
            $pStatus = (int) $order->payment_status;
            if (!in_array($pStatus, [\App\Models\Order::PAYMENT_STATUS_PAID, \App\Models\Order::PAYMENT_STATUS_PARTIAL], true)) {
                continue;
            }
            $cashAmt = (float) $order->paid_cash_amount;
            $onlineAmt = (float) $order->paid_online_amount;
            if ($cashAmt <= 0 && $onlineAmt <= 0 && $pStatus === \App\Models\Order::PAYMENT_STATUS_PAID) {
                if ($normalizePaymentMethod($order->payment_method) === 'online') {
                    $onlineAmt = (float) $order->final_amount;
                } else {
                    $cashAmt = (float) $order->final_amount;
                }
            }
            $cashTotal += $cashAmt;
            $onlineTotal += $onlineAmt;
        }
        $paymentMethodData = [];
        if ($cashTotal > 0) {
            $paymentMethodData['Cash'] = $cashTotal;
        }
        if ($onlineTotal > 0) {
            $paymentMethodData['Online'] = $onlineTotal;
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

        ActivityLogger::log('Reports', 'export', null, null, null, 'Daily report exported to PDF for ' . $date);

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
        $orderPaymentLabels = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
        $orderPaymentColors = [1 => 'bg-label-warning', 2 => 'bg-label-info', 3 => 'bg-label-primary'];
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

        $purchasesByLocation = DB::table('purchase_allocations')
            ->join('purchase_items', 'purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereDate('purchases.created_at', $date)
            ->whereIn('purchase_allocations.location_id', $locationIds)
            ->groupBy('purchase_allocations.location_id')
            ->selectRaw('purchase_allocations.location_id as location_id, COUNT(DISTINCT purchases.id) as cnt, SUM(purchase_items.total) as total')
            ->get()
            ->keyBy('location_id');

        $transfersByLocation = [];
        foreach ($locationIds as $locId) {
            $locTransfers = PurchaseBill::where('status', PurchaseBill::STATUS_ACCEPTED)
                ->whereDate('created_at', $date)
                ->where(function ($q) use ($locId) {
                    $q->where('from_location_id', $locId)
                      ->orWhere('to_location_id', $locId);
                })->get();

            $cnt = $locTransfers->count();
            $billIds = $locTransfers->pluck('id');
            $qty = PurchaseBillItem::whereIn('purchase_bill_id', $billIds)->sum('quantity');

            $transfersByLocation[$locId] = ['cnt' => (int) $cnt, 'qty' => (int) $qty];
        }

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

        $totalSales    = (float) $salesByLocation->sum('total');
        $totalSalesCount = (int) $salesByLocation->sum('cnt');

        $pendingSalesOrders = Order::where('order_type', 'sale')
            ->where('status', '!=', Order::STATUS_DECLINE)
            ->whereDate('created_at', $date)
            ->whereIn('location_id', $locationIds)
            ->get();
        $totalPendingSales = (float) $pendingSalesOrders->sum(function($o) {
            if ((int)$o->payment_status === Order::PAYMENT_STATUS_PAID) return 0;
            return max(0, (float)$o->final_amount - ((float)$o->paid_cash_amount + (float)$o->paid_online_amount));
        });

        $totalPurchases = (float) $purchasesByLocation->sum('total');
        $totalPurchasesCount = (int) $purchasesByLocation->sum('cnt');

        $approvedPurchases = Purchase::where('status', Purchase::STATUS_APPROVE)
            ->whereDate('created_at', $date)
            ->when($locationId, function($q) use ($locationId) {
                $q->whereHas('items.allocations', function($sub) use ($locationId) {
                    $sub->where('location_id', $locationId);
                });
            })
            ->get();
        $totalPendingPurchases = (float) $approvedPurchases->sum(fn($i) => max(0, (float)$i->total_amount - (float)$i->paid_amount));
        $totalExpenses = (float) $expensesByLocation->sum('total');
        $totalExpensesCount = (int) $expensesByLocation->sum('cnt');

        $transferOverallQuery = PurchaseBill::where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('created_at', $date);

        if ($locationId) {
            $transferOverallQuery->where(function ($q) use ($locationId) {
                $q->where('from_location_id', $locationId)
                  ->orWhere('to_location_id', $locationId);
            });
        } else {
            $transferOverallQuery->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                  ->orWhereIn('to_location_id', $locationIds);
            });
        }

        $overallTransfers = $transferOverallQuery->get();
        $totalTransfersCount = $overallTransfers->count();
        $totalTransfersQty = PurchaseBillItem::whereIn('purchase_bill_id', $overallTransfers->pluck('id'))->sum('quantity');

        $salesRows = Order::with(['customer', 'location'])
            ->where('order_type', 'sale')
            ->whereIn('status', $orderStatuses)
            ->whereDate('created_at', $date)
            ->whereIn('location_id', $locationIds)
            ->latest()
            ->latest('id')
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
            ->latest('id')
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
            ->latest('id')
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

        $purchaseBillQuery = PurchaseBill::with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('created_at', $date);

        if ($locationId) {
            $purchaseBillQuery->where(function ($q) use ($locationId) {
                $q->where('from_location_id', $locationId)
                  ->orWhere('to_location_id', $locationId);
            });
        } else {
            $purchaseBillQuery->where(function ($q) use ($locationIds) {
                $q->whereIn('from_location_id', $locationIds)
                  ->orWhereIn('to_location_id', $locationIds);
            });
        }

        $purchaseBillRows = $purchaseBillQuery->withCount('items')
            ->latest()
            ->latest('id')
            ->get()
            ->values()
            ->map(function ($transfer, $index) use ($transferStatusLabels, $transferStatusColors, $badge) {
                $amount = 0.0;

                foreach ($transfer->items as $item) {
                    $price = $item->variant->purchase_price ?? $item->product->purchase_price ?? 0;
                    $amount += $price * $item->quantity;
                }

                return [
                    'index'          => $index + 1,
                    'bill_no'        => $transfer->transfer_no,
                    'source'         => $transfer->fromLocation->name ?? '-',
                    'destination'    => $transfer->toLocation->name ?? '-',
                    'total_quantity' => $transfer->items->sum('quantity'),
                    'amount'         => (float) $amount,
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
            'totalPendingSales'   => $totalPendingSales,
            'totalSalesCount'     => $totalSalesCount,
            'totalPurchases'      => $totalPurchases,
            'totalPendingPurchases' => $totalPendingPurchases,
            'totalPurchasesCount' => $totalPurchasesCount,
            'totalExpenses'       => $totalExpenses,
            'totalExpensesCount'  => $totalExpensesCount,
            'totalTransfersCount' => $totalTransfersCount,
            'totalTransfersQty'   => $totalTransfersQty,
        ];
    }

    // ============================================================
    // CUSTOMER REPORT
    // ============================================================

    private function buildCustomerReportData(Request $request): array
    {
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $type       = $request->query('type');
        $source     = $request->query('source');
        $customerId = $request->query('customer_id');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');

        $query = CustomerBalanceTransaction::query()
            ->whereHas('customer', function ($q) use ($isRestricted, $user) {
                $q->where('is_credit_customer', true);
                if ($isRestricted) {
                    $q->where('location_id', $user->location_id);
                }
            })
            ->with(['customer', 'createdBy']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if (in_array($type, [CustomerBalanceTransaction::TYPE_CREDIT, CustomerBalanceTransaction::TYPE_DEBIT], true)) {
            $query->where('type', $type);
        }
        if (in_array($source, [CustomerBalanceTransaction::SOURCE_CASH, CustomerBalanceTransaction::SOURCE_BANK], true)) {
            $query->where('source', $source);
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $transactions = $query->orderByDesc('id')->get();

        $creditCustomers = Customer::where('is_credit_customer', true)
            ->when($isRestricted, fn ($q) => $q->where('location_id', $user->location_id))
            ->orderBy('name')->get();

        $saleOrderNos = $transactions
            ->filter(fn ($t) => $t->type === CustomerBalanceTransaction::TYPE_DEBIT)
            ->map(function ($t) {
                return preg_match('/Sale #([^\s(]+)/', $t->notes ?? '', $m) ? $m[1] : null;
            })
            ->filter()
            ->unique()
            ->values();

        $ordersByNo = Order::with('items.product', 'items.variant.attributeValue')
            ->whereIn('order_no', $saleOrderNos)
            ->get()
            ->keyBy('order_no');

        $transactions->each(function ($t) use ($ordersByNo) {
            $t->linked_order = null;
            if ($t->type === CustomerBalanceTransaction::TYPE_DEBIT && preg_match('/Sale #([^\s(]+)/', $t->notes ?? '', $m)) {
                $t->linked_order = $ordersByNo->get($m[1]);
            }
        });

        $totalCredit = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalDebit  = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_DEBIT)->sum('amount');

        $cashCredit  = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_CREDIT)->where('source', CustomerBalanceTransaction::SOURCE_CASH)->sum('amount');
        $cashDebit   = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_DEBIT)->where('source', CustomerBalanceTransaction::SOURCE_CASH)->sum('amount');
        $cashBalance = $cashCredit - $cashDebit;

        $bankCredit  = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_CREDIT)->where('source', CustomerBalanceTransaction::SOURCE_BANK)->sum('amount');
        $bankDebit   = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_DEBIT)->where('source', CustomerBalanceTransaction::SOURCE_BANK)->sum('amount');
        $bankBalance = $bankCredit - $bankDebit;

        return [
            'transactions'       => $transactions,
            'totalTransactions'  => $transactions->count(),
            'totalCredit'        => $totalCredit,
            'totalDebit'         => $totalDebit,
            'cashBalance'        => $cashBalance,
            'bankBalance'        => $bankBalance,
            'type'               => $type,
            'source'             => $source,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'customerId'         => $customerId,
            'creditCustomers'    => $creditCustomers,
            // Net balance for the currently filtered result set (Credit - Debit),
            // not the customer's absolute wallet balance, so it moves with the filters.
            'totalWalletBalance' => $totalCredit - $totalDebit,
        ];
    }

    public function customerReport(Request $request)
    {
        $this->authorize('view customer report');

        return view('reports.customer-report', $this->buildCustomerReportData($request));
    }

    public function exportCustomerReport(Request $request)
    {
        $this->authorize('view customer report');

        $data = $this->buildCustomerReportData($request);

        if ($data['transactions']->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        if ($request->boolean('auto_print') && !$request->boolean('stream')) {
            return view('sales.pdf-print-wrapper', [
                'title'  => 'Customer Credit Report',
                'pdfUrl' => route('admin.reports.customer-report.export', array_merge($request->all(), ['stream' => 1])),
            ]);
        }

        $pdf = Pdf::loadView('reports.pdf.customer-report', $data)->setPaper('a4', 'landscape');

        ActivityLogger::log('Reports', 'export', null, null, null, 'Customer credit report exported to PDF');

        return $pdf->stream('customer_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function customerReportDetail(Request $request)
    {
        $this->authorize('view customer report');

        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $customer = Customer::findOrFail($request->customer_id);

        $viewer = auth()->user();
        if ($viewer->location_id && !$viewer->hasRole('super-admin') && $customer->location_id != $viewer->location_id) {
            abort(403);
        }

        $transactionType = $request->query('type');

        $transactionsQuery = $customer->balanceTransactions()
            ->with('createdBy')
            ->orderByDesc('id');

        if (in_array($transactionType, [CustomerBalanceTransaction::TYPE_CREDIT, CustomerBalanceTransaction::TYPE_DEBIT], true)) {
            $transactionsQuery->where('type', $transactionType);
        }

        $transactions = $transactionsQuery->get();

        $totalCredit = $customer->balanceTransactions()->where('type', CustomerBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalDebit  = $customer->balanceTransactions()->where('type', CustomerBalanceTransaction::TYPE_DEBIT)->sum('amount');

        $saleOrderNos = $transactions
            ->filter(fn ($t) => $t->type === CustomerBalanceTransaction::TYPE_DEBIT)
            ->map(function ($t) {
                return preg_match('/Sale #([^\s(]+)/', $t->notes ?? '', $m) ? $m[1] : null;
            })
            ->filter()
            ->unique()
            ->values();

        $ordersByNo = Order::with('items.product', 'items.variant.attributeValue')
            ->whereIn('order_no', $saleOrderNos)
            ->get()
            ->keyBy('order_no');

        $transactions->each(function ($t) use ($ordersByNo) {
            $t->linked_order = null;
            if ($t->type === CustomerBalanceTransaction::TYPE_DEBIT && preg_match('/Sale #([^\s(]+)/', $t->notes ?? '', $m)) {
                $t->linked_order = $ordersByNo->get($m[1]);
            }
        });

        $sales = Order::with('location')
            ->where('customer_id', $customer->id)
            ->where('order_type', 'sale')
            ->orderByDesc('created_at')
            ->get();

        return view('reports.customer-report-detail', compact('customer', 'transactions', 'totalCredit', 'totalDebit', 'sales', 'transactionType'));
    }

    public function customerReportSaleProducts(Request $request)
    {
        $this->authorize('view customer report');

        $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::with([
            'items.product.variants.attributeValue.attribute',
            'items.product.primaryImage',
            'items.variant.attributeValue.attribute',
            'customer',
            'customerAddress',
            'location',
            'user',
            'coupon',
            'cancellationRequest',
        ])->findOrFail($request->order_id);

        return view('reports.partials.sale-details', compact('order'));
    }
}
