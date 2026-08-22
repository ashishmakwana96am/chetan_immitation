<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Purchase;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseItem;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\ReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
                ->mapWithKeys(function ($p) {
                    return [$p->category->name ?? 'Default' => (int) $p->total];
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
                'name' => (string) $p->name,
                'stock' => (int) max(1, $p->stock),
            ];
        }

        $totalProducts = Product::count();
        $activeProductCount = Product::where('status', 1)->count();
        $soldoutProductCount = Product::where('status', 1)
            ->whereDoesntHave('inventories', fn($q) => $q->where('quantity', '>', 0))
            ->count();

        return view('reports.products', [
            'categories' => $categories,
            'subCategories' => $subCategories,
            'totalProducts' => $totalProducts,
            'activeProductCount' => $activeProductCount,
            'soldoutProductCount' => $soldoutProductCount,
            'categoryChartData' => $categoryChartData,
            'top10ChartData' => $top10ChartData,
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
                    $sub
                        ->where('name', 'like', "%{$search}%")
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

        $products = $query
            ->orderBy('name')
            ->skip($start)
            ->take($length)
            ->get();

        Product::preloadVariantStock($products);

        $data = [];
        $index = $start + 1;

        foreach ($products as $product) {
            $margin = (float) $product->sale_price - (float) $product->purchase_price;
            $marginPct = $product->purchase_price > 0 ? round(($margin / $product->purchase_price) * 100, 1) : 0;

            $variantsData = [];
            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock($locationId);
                $stockByLoc = $locationId ? [$locationId => $variantStock] : $variantStock;

                $parentStock = 0;
                foreach ($stockByLoc as $locData) {
                    if (!$locData)
                        continue;
                    $vSum = array_sum($locData['variants'] ?? []);
                    $parentStock += $vSum > 0 ? $vSum : max(0, (int) ($locData['parent'] ?? 0));
                }
                if ($parentStock <= 0) {
                    $parentStock = (int) $product
                        ->inventories
                        ->when($locationId, fn($col) => $col->where('location_id', $locationId))
                        ->sum('quantity');
                }

                foreach ($product->variants as $vIndex => $v) {
                    $vStock = 0;
                    foreach ($stockByLoc as $locData) {
                        if (!$locData)
                            continue;
                        $vStock += max(0, (int) ($locData['variants'][$v->id] ?? 0));
                    }

                    $vMargin = (float) $v->sale_price - (float) $v->purchase_price;
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
                $parentStock = (int) $product
                    ->inventories
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

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        $locationId = $isRestricted ? $user->location_id : null;

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

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
                'name' => $location->name,
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
            'locations' => $locations,
            'categories' => $categories,
            'activeProductCount' => $activeProductCount,
            'soldoutProductCount' => $soldoutProductCount,
            'lowStockCount' => $lowStockCount,
            'locationChartData' => $locationChartData,
            'stackedChartData' => $stackedChartData,
            'totalStockDisplay' => $overallTotals['qty_total'],
            'totalPurchaseValueDisplay' => format_price($overallTotals['purchase_total']),
            'totalMrpValueDisplay' => format_price($overallTotals['mrp_total']),
            'isRestricted' => $isRestricted,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
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
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub
                        ->where('name', 'like', "%{$search}%")
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
            $sql = 'exists (select 1 from purchase_items join purchases on purchases.id = purchase_items.purchase_id
                     where purchase_items.product_id = products.id and purchases.status = ?';
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

        [$stockExprSql, $stockExprBindings] = $this->stockTotalSubquery($locationId);
        [$lastPurchaseExprSql, $lastPurchaseExprBindings] = $this->lastPurchaseSubquery();
        $sortBy = $request->input('sort_by');
        $hasDateFilter = $request->input('from_date') || $request->input('to_date');

        $orderColumnIndex = $request->input('order.0.column');
        $orderDir = strtoupper($request->input('order.0.dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $columnName = $request->input("columns.{$orderColumnIndex}.name");

        if ($columnName === 'name') {
            $query->orderBy('name', $orderDir);
        } elseif ($columnName === 'barcode') {
            $query->orderBy('barcode', $orderDir);
        } elseif ($columnName === 'total_qty') {
            $query->orderByRaw("{$stockExprSql} {$orderDir}", $stockExprBindings);
        } elseif ($columnName === 'purchase_value') {
            $query->orderByRaw("({$stockExprSql} * products.purchase_price) {$orderDir}", $stockExprBindings);
        } elseif ($columnName === 'mrp_value') {
            $query->orderByRaw("({$stockExprSql} * COALESCE(NULLIF(products.mrp, 0), products.sale_price)) {$orderDir}", $stockExprBindings);
        } elseif ($columnName === 'last_purchase') {
            $query->orderByRaw("({$lastPurchaseExprSql}) IS NULL ASC, ({$lastPurchaseExprSql}) {$orderDir}",
                array_merge($lastPurchaseExprBindings, $lastPurchaseExprBindings));
        } elseif ($columnName === 'age') {
            $ageDir = $orderDir === 'ASC' ? 'DESC' : 'ASC';
            $query->orderByRaw("({$lastPurchaseExprSql}) IS NULL ASC, ({$lastPurchaseExprSql}) {$ageDir}",
                array_merge($lastPurchaseExprBindings, $lastPurchaseExprBindings));
        } else if ($sortBy === 'age_desc') {
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

        $products = $query
            ->with(['category', 'primaryImage', 'inventories', 'variants.attributeValue.attribute'])
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
            $salePrice = (float) $product->sale_price;
            $mrpPrice = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0)
                $pairSize = 1.0;

            $variantsData = [];

            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                $parentLocStock = [];
                $hasVariantStock = false;
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent = (int) ($variantStock[$location->id]['parent'] ?? 0);
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
                    $parentMrpVal = 0.0;
                } else {
                    $effectiveTotal = $product->pair_product ? ($parentTotal / $pairSize) : (float) $parentTotal;
                    $parentPurchaseVal = $effectiveTotal * $purchasePrice;
                    $parentMrpVal = $effectiveTotal * $mrpPrice;
                }

                foreach ($product->variants as $vIndex => $v) {
                    $vLocStock = [];
                    foreach ($locations as $location) {
                        $vLocStock[$location->id] = max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                    }
                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';
                    $vTotal = array_sum($vLocStock);
                    $vPrice = (float) ($v->purchase_price ?? $purchasePrice);
                    $vMrp = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $mrpPrice);
                    $vEffectiveQty = $product->pair_product ? ($vTotal / $pairSize) : (float) $vTotal;
                    $vPurchVal = $vEffectiveQty * $vPrice;
                    $vMrpVal = $vEffectiveQty * $vMrp;

                    if ($hasVariantStock) {
                        $parentPurchaseVal += $vPurchVal;
                        $parentMrpVal += $vMrpVal;
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
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
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
                    $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
                    $pairSize = $sizes->count() > 0 ? (float) $sizes->max() : 1.0;
                    if ($pairSize <= 0)
                        $pairSize = 1.0;
                    $locationPairTotals[$location->id] += (int) floor($qty / $pairSize);
                    $locationLooseTotals[$location->id] += (int) ($qty % $pairSize);
                } else {
                    $locationLooseTotals[$location->id] += $qty;
                }
            }

            if ($totalQty <= 0) {
                continue;
            }

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0)
                $pairSize = 1.0;

            if ($product->pair_product) {
                $totalPairUnits += (int) floor($totalQty / $pairSize);
                $totalLoosePcs += (int) ($totalQty % $pairSize);
            } else {
                $totalLoosePcs += $totalQty;
            }

            $mrpPrice = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);
            $effectiveQty = $product->pair_product ? ($totalQty / $pairSize) : (float) $totalQty;
            $totalPurchaseValue += $effectiveQty * (float) $product->purchase_price;
            $totalMrpValue += $effectiveQty * $mrpPrice;
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

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $isGst = $request->query('is_gst');

        $user = auth()->user();
        $query = Purchase::query()
            ->where('purchases.status', Purchase::STATUS_APPROVE);

        if ($user->location_id && !$user->hasRole('super-admin')) {
            $allowedPurchaseIds = DB::table('purchase_allocations')
                ->join('purchase_items', 'purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
                ->where('purchase_allocations.location_id', $user->location_id)
                ->pluck('purchase_items.purchase_id')
                ->unique();
            $query->whereIn('purchases.id', $allowedPurchaseIds);
        }

        if ($startDate) {
            $query->whereDate('purchases.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('purchases.created_at', '<=', $endDate);
        }
        if ($supplierId) {
            $query->where('purchases.supplier_id', $supplierId);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('purchases.is_gst', (bool) $isGst);
        }

        // Totals via 1 combined fast SQL aggregate query
        $stats = (clone $query)
            ->selectRaw('
                COALESCE(SUM(purchases.total_amount), 0) as total_purchases,
                COUNT(purchases.id) as invoice_count,
                COALESCE(SUM(GREATEST(0, purchases.total_amount - purchases.paid_amount)), 0) as total_pending
            ')
            ->first();

        $totalPurchases = (float) ($stats->total_purchases ?? 0);
        $invoiceCount = (int) ($stats->invoice_count ?? 0);
        $confirmedCount = $invoiceCount;
        $totalPendingAmount = (float) ($stats->total_pending ?? 0);

        // Purchase by Supplier (Donut Chart) via fast direct SQL grouping
        $supplierMap = $suppliers->pluck('name', 'id');
        $rawSupplierTotals = (clone $query)
            ->selectRaw('purchases.supplier_id, SUM(purchases.total_amount) as total')
            ->groupBy('purchases.supplier_id')
            ->orderByDesc('total')
            ->pluck('total', 'purchases.supplier_id')
            ->toArray();

        $supplierData = [];
        foreach ($rawSupplierTotals as $supId => $tot) {
            $name = $supplierMap[$supId] ?? 'Unknown';
            $supplierData[$name] = (float) $tot;
        }

        // Purchases Over Time (Monthly Chart) via SQL
        $purchasesTrend = (clone $query)
            ->selectRaw("DATE_FORMAT(purchases.created_at, '%Y-%m') as month, SUM(purchases.total_amount) as total")
            ->groupBy(DB::raw("DATE_FORMAT(purchases.created_at, '%Y-%m')"))
            ->orderBy('month', 'asc')
            ->pluck('total', 'month')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // Return empty collections for initial Blade shell; DataTables AJAX loads paginated rows
        $invoices = collect();
        $productPurchases = collect();

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

    public function purchasesData(Request $request)
    {
        $this->authorize('view purchase reports');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $isGst = $request->query('is_gst');
        $searchValue = $request->input('search.value');

        $user = auth()->user();
        $query = Purchase::query()
            ->where('purchases.status', Purchase::STATUS_APPROVE);

        if ($user->location_id && !$user->hasRole('super-admin')) {
            $allowedPurchaseIds = DB::table('purchase_allocations')
                ->join('purchase_items', 'purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
                ->where('purchase_allocations.location_id', $user->location_id)
                ->pluck('purchase_items.purchase_id')
                ->unique();
            $query->whereIn('purchases.id', $allowedPurchaseIds);
        }

        if ($startDate) {
            $query->whereDate('purchases.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('purchases.created_at', '<=', $endDate);
        }
        if ($supplierId) {
            $query->where('purchases.supplier_id', $supplierId);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('purchases.is_gst', (bool) $isGst);
        }

        if ($searchValue) {
            $query->where(function ($sub) use ($searchValue) {
                $sub
                    ->where('purchases.invoice_no', 'like', "%{$searchValue}%")
                    ->orWhereHas('supplier', function ($sq) use ($searchValue) {
                        $sq->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsTotal = Purchase::where('status', Purchase::STATUS_APPROVE)->count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0)
            $length = 25;

        $invoices = (clone $query)
            ->select(['purchases.id', 'purchases.invoice_no', 'purchases.supplier_id', 'purchases.status', 'purchases.total_amount', 'purchases.paid_amount', 'purchases.created_at'])
            ->with(['supplier:id,name'])
            ->latest('purchases.created_at')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($invoices as $invoice) {
            $statusBadge = '<span class="badge bg-label-success">Approve</span>';
            $actions = '
                <div class="dropdown table-action-dropdown">
                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                        <span>Actions</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                        <a href="' . route('admin.purchases.show', $invoice->id) . '" class="dropdown-item">
                            <i class="ti ti-eye me-2"></i>View
                        </a>
                    </div>
                </div>';

            $data[] = [
                'id' => $invoice->id,
                'invoice_no' => '<code>' . e($invoice->invoice_no) . '</code>',
                'supplier' => '<span class="fw-semibold">' . e($invoice->supplier->name ?? 'Unknown') . '</span>',
                'status' => $statusBadge,
                'total_amount' => format_price($invoice->total_amount),
                'actions' => $actions,
                'date_group' => $invoice->created_at->format('d M Y'),
                'date_sort' => $invoice->created_at->format('Ymd'),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function purchasesProductsData(Request $request)
    {
        $this->authorize('view purchase reports');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $isGst = $request->query('is_gst');
        $searchValue = $request->input('search.value');

        $user = auth()->user();
        $productPurchasesQuery = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', Purchase::STATUS_APPROVE);

        if ($startDate) {
            $productPurchasesQuery->whereDate('purchases.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $productPurchasesQuery->whereDate('purchases.created_at', '<=', $endDate);
        }
        if ($supplierId) {
            $productPurchasesQuery->where('purchases.supplier_id', $supplierId);
        }
        if ($isGst !== null && $isGst !== '') {
            $productPurchasesQuery->where('purchases.is_gst', (bool) $isGst);
        }
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $allowedPurchaseIds = DB::table('purchase_allocations')
                ->join('purchase_items', 'purchase_items.id', '=', 'purchase_allocations.purchase_item_id')
                ->where('purchase_allocations.location_id', $user->location_id)
                ->pluck('purchase_items.purchase_id')
                ->unique();
            $productPurchasesQuery->whereIn('purchases.id', $allowedPurchaseIds);
        }

        if ($searchValue) {
            $productPurchasesQuery->whereHas('product', function ($pq) use ($searchValue) {
                $pq
                    ->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('barcode', 'like', "%{$searchValue}%");
            });
        }

        $groupedQuery = (clone $productPurchasesQuery)
            ->selectRaw('purchase_items.product_id, SUM(purchase_items.quantity) as qty_purchased, SUM(purchase_items.total) as total_cost')
            ->groupBy('purchase_items.product_id');

        $recordsTotal = DB::table('purchase_items')->distinct('product_id')->count('product_id');
        $recordsFiltered = DB::table(DB::raw("({$groupedQuery->toSql()}) as sub"))
            ->mergeBindings($groupedQuery->getQuery())
            ->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0)
            $length = 25;

        $productPurchases = $groupedQuery
            ->orderByDesc('qty_purchased')
            ->skip($start)
            ->take($length)
            ->with(['product:id,name,barcode', 'product.primaryImage'])
            ->get();

        $data = [];
        foreach ($productPurchases as $item) {
            $prodName = e($item->product->name ?? 'Unknown');
            $prodUrl = route('admin.products.show', $item->product_id);
            $imgUrl = $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg');

            $prodHtml = '
                <div class="d-flex align-items-center">
                    <img src="' . $imgUrl . '" alt="' . $prodName . '" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                    <a href="' . $prodUrl . '" class="fw-semibold">' . $prodName . '</a>
                </div>';

            $data[] = [
                'product' => $prodHtml,
                'barcode' => '<code>' . e($item->product->barcode ?? '-') . '</code>',
                'qty_purchased' => '<span class="fw-bold text-info">' . $item->qty_purchased . '</span>',
                'total_cost' => '<span class="fw-bold text-success">' . format_price($item->total_cost) . '</span>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function sales(Request $request)
    {
        $this->authorize('view sale reports');

        $user = auth()->user();
        $isRestricted = $user->location_id && !$user->hasRole('super-admin');
        if ($isRestricted) {
            $locations = Location::where('id', $user->location_id)->get(['id', 'name']);
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get(['id', 'name']);
            $locationId = $request->query('location_id');
        }
        $customers = Customer::when($isRestricted, fn($q) => $q->where('location_id', $user->location_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');
        $isGst = $request->query('is_gst');

        $query = Order::query()
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $query->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $query->where('orders.location_id', $locationId);
        }
        if ($paymentStatus) {
            $query->where('orders.payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $query->where('orders.payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('orders.is_gst', (bool) $isGst);
        }

        // Summary metrics via fast SQL aggregates
        $totalSales = (float) (clone $query)->sum('orders.final_amount');
        $orderCount = (int) (clone $query)->count();
        $avgOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0.0;
        $paidCount = (int) (clone $query)->where('orders.payment_status', Order::PAYMENT_STATUS_PAID)->count();
        $partialCount = (int) (clone $query)->where('orders.payment_status', Order::PAYMENT_STATUS_PARTIAL)->count();
        $pendingCount = (int) (clone $query)->where('orders.payment_status', Order::PAYMENT_STATUS_PENDING)->count();

        // Calculate pending amount across all non-declined orders matching filter
        $pendingQuery = Order::query()
            ->where('orders.order_type', 'sale')
            ->where('orders.status', '!=', Order::STATUS_DECLINE)
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $pendingQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $pendingQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $pendingQuery->where('orders.location_id', $locationId);
        }
        if ($paymentStatus) {
            $pendingQuery->where('orders.payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $pendingQuery->where('orders.payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $pendingQuery->where('orders.is_gst', (bool) $isGst);
        }

        $totalPendingAmount = (float) (clone $pendingQuery)
            ->where('orders.payment_status', '!=', Order::PAYMENT_STATUS_PAID)
            ->selectRaw('SUM(GREATEST(0, orders.final_amount - (COALESCE(orders.paid_cash_amount, 0) + COALESCE(orders.paid_online_amount, 0)))) as pending')
            ->value('pending');

        // Sales Over Time (Monthly Chart Data) via SQL
        $salesTrend = (clone $query)
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as month, SUM(orders.final_amount) as total")
            ->groupBy(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m')"))
            ->orderBy('month', 'asc')
            ->pluck('total', 'month')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // Sales by Payment Method via SQL
        $paidOrdersTotals = (clone $query)
            ->whereIn('orders.payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
            ->selectRaw('
                SUM(CASE 
                    WHEN COALESCE(paid_cash_amount, 0) <= 0 AND COALESCE(paid_online_amount, 0) <= 0 AND payment_status = ? AND LOWER(COALESCE(payment_method, "")) NOT IN ("online", "razorpay") THEN final_amount 
                    ELSE COALESCE(paid_cash_amount, 0) 
                END) as cash_total,
                SUM(CASE 
                    WHEN COALESCE(paid_cash_amount, 0) <= 0 AND COALESCE(paid_online_amount, 0) <= 0 AND payment_status = ? AND LOWER(COALESCE(payment_method, "")) IN ("online", "razorpay") THEN final_amount 
                    ELSE COALESCE(paid_online_amount, 0) 
                END) as online_total
            ', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PAID])
            ->first();

        $cashTotal = (float) ($paidOrdersTotals->cash_total ?? 0);
        $onlineTotal = (float) ($paidOrdersTotals->online_total ?? 0);

        $paymentMethodData = [];
        if ($cashTotal > 0) {
            $paymentMethodData['Cash'] = $cashTotal;
        }
        if ($onlineTotal > 0) {
            $paymentMethodData['Online'] = $onlineTotal;
        }

        // Return empty collections for initial Blade shell; DataTables AJAX loads paginated rows
        $orders = collect();
        $productSales = collect();

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

    public function salesData(Request $request)
    {
        $this->authorize('view sale reports');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $locationId = $request->query('location_id');
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');
        $isGst = $request->query('is_gst');
        $searchValue = $request->input('search.value');

        $user = auth()->user();
        $query = Order::query()
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $query->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $query->where('orders.location_id', $locationId);
        }
        if ($paymentStatus) {
            $query->where('orders.payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $query->where('orders.payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $query->where('orders.is_gst', (bool) $isGst);
        }

        if ($searchValue) {
            $query->where(function ($sub) use ($searchValue) {
                $sub
                    ->where('orders.order_no', 'like', "%{$searchValue}%")
                    ->orWhereHas('customer', function ($cq) use ($searchValue) {
                        $cq->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $recordsTotal = Order::where('order_type', 'sale')->whereIn('status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])->count();
        $recordsFiltered = (clone $query)->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0)
            $length = 25;

        $orders = (clone $query)
            ->select(['orders.id', 'orders.order_no', 'orders.customer_id', 'orders.location_id', 'orders.user_id', 'orders.status', 'orders.payment_status', 'orders.payment_method', 'orders.final_amount', 'orders.paid_cash_amount', 'orders.paid_online_amount', 'orders.created_at'])
            ->with(['customer:id,name', 'location:id,name', 'user:id,name'])
            ->latest('orders.created_at')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($orders as $order) {
            $pmText = match (strtolower((string) ($order->payment_method ?? ''))) {
                'online_cash' => 'Online + Cash',
                'cod'         => 'COD',
                'upi'         => 'UPI',
                ''            => '-',
                default       => ucwords(str_replace('_', ' + ', (string) $order->payment_method)),
            };
            $pmBadge = '<span class="badge bg-label-info">' . e($pmText) . '</span>';
            $psBadge = '<span class="badge bg-label-success">Paid</span>';
            if ((int) $order->payment_status === Order::PAYMENT_STATUS_PARTIAL) {
                $psBadge = '<span class="badge bg-label-warning">Partial</span>';
            } elseif ((int) $order->payment_status === Order::PAYMENT_STATUS_PENDING) {
                $psBadge = '<span class="badge bg-label-danger">Pending</span>';
            }

            $actions = '
                <div class="dropdown table-action-dropdown">
                    <button class="btn btn-sm btn-label-primary action-dropdown-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                        <span>Actions</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end action-dropdown-menu m-0">
                        <a href="' . route('admin.sales.show', $order->id) . '" class="dropdown-item">
                            <i class="ti ti-eye me-2"></i>View
                        </a>
                    </div>
                </div>';

            $data[] = [
                'id' => $order->id,
                'invoice_no' => '<code>' . e($order->order_no) . '</code>',
                'customer' => '<span class="fw-semibold">' . e($order->customer->name ?? 'Walk-in Customer') . '</span>',
                'location' => e($order->location->name ?? '-'),
                'payment_method' => $pmBadge,
                'payment_status' => $psBadge,
                'final_amount' => format_price($order->final_amount),
                'actions' => $actions,
                'date_group' => $order->created_at->format('d M Y'),
                'date_sort' => $order->created_at->format('Ymd'),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function salesProductsData(Request $request)
    {
        $this->authorize('view sale reports');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $locationId = $request->query('location_id');
        $paymentStatus = $request->query('payment_status');
        $paymentMethod = $request->query('payment_method');
        $isGst = $request->query('is_gst');
        $searchValue = $request->input('search.value');

        $user = auth()->user();
        $productSalesQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED]);

        if ($startDate) {
            $productSalesQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $productSalesQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $productSalesQuery->where('orders.location_id', $locationId);
        }
        if ($paymentStatus) {
            $productSalesQuery->where('orders.payment_status', $paymentStatus);
        }
        if ($paymentMethod) {
            $productSalesQuery->where('orders.payment_method', $paymentMethod);
        }
        if ($isGst !== null && $isGst !== '') {
            $productSalesQuery->where('orders.is_gst', (bool) $isGst);
        }

        if ($searchValue) {
            $productSalesQuery->whereHas('product', function ($pq) use ($searchValue) {
                $pq
                    ->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('barcode', 'like', "%{$searchValue}%");
            });
        }

        $groupedQuery = (clone $productSalesQuery)
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty_sold, SUM(order_items.total) as total_revenue')
            ->groupBy('order_items.product_id');

        $recordsTotal = DB::table('order_items')->distinct('product_id')->count('product_id');
        $recordsFiltered = DB::table(DB::raw("({$groupedQuery->toSql()}) as sub"))
            ->mergeBindings($groupedQuery->getQuery())
            ->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0)
            $length = 25;

        $productSales = $groupedQuery
            ->orderByDesc('qty_sold')
            ->skip($start)
            ->take($length)
            ->with(['product:id,name,barcode', 'product.primaryImage'])
            ->get();

        $data = [];
        foreach ($productSales as $item) {
            $prodName = e($item->product->name ?? 'Unknown');
            $prodUrl = route('admin.products.show', $item->product_id);
            $imgUrl = $item->product?->primary_image_url ?? asset('website/assets/images/no-image.svg');

            $prodHtml = '
                <div class="d-flex align-items-center">
                    <img src="' . $imgUrl . '" alt="' . $prodName . '" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                    <a href="' . $prodUrl . '" class="fw-semibold">' . $prodName . '</a>
                </div>';

            $data[] = [
                'product' => $prodHtml,
                'barcode' => '<code>' . e($item->product->barcode ?? '-') . '</code>',
                'qty_sold' => '<span class="fw-bold text-info">' . $item->qty_sold . '</span>',
                'total_revenue' => '<span class="fw-bold text-success">' . format_price($item->total_revenue) . '</span>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function profitLoss(Request $request)
    {
        $this->authorize('view profit loss reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get(['id', 'name']);
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get(['id', 'name']);
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $salesQuery = Order::query()
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('orders.payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $salesQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $salesQuery->where('orders.location_id', $locationId);
        }

        // Total Revenue via direct SQL sum
        $totalRevenue = (float) (clone $salesQuery)
            ->selectRaw('SUM(COALESCE(orders.paid_cash_amount, 0) + COALESCE(orders.paid_online_amount, 0)) as total_rev')
            ->value('total_rev');

        // Direct SQL aggregation for COGS and Product Profitability (without loading all sales into memory)
        $productProfitabilityQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('orders.payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $productProfitabilityQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $productProfitabilityQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $productProfitabilityQuery->where('orders.location_id', $locationId);
        }

        $productProfitabilityRaw = $productProfitabilityQuery
            ->selectRaw('
                order_items.product_id,
                products.name,
                products.barcode,
                SUM(order_items.quantity) as qty_sold,
                SUM(order_items.total) as total_revenue,
                SUM(order_items.quantity * COALESCE(product_variants.purchase_price, products.purchase_price, 0)) as total_cost
            ')
            ->groupBy('order_items.product_id', 'products.name', 'products.barcode')
            ->orderByDesc('order_items.product_id')
            ->get();

        $totalCogs = (float) $productProfitabilityRaw->sum('total_cost');

        // Return empty array for initial Blade shell; DataTables AJAX loads paginated rows
        $productProfitability = [];

        // Expenses via direct SQL
        $expensesQuery = Expense::query()
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('expenses.location_id', $user->location_id));
        if ($startDate) {
            $expensesQuery->whereDate('expenses.expense_date', '>=', $startDate);
        }
        if ($endDate) {
            $expensesQuery->whereDate('expenses.expense_date', '<=', $endDate);
        }
        if ($locationId) {
            $expensesQuery->where('expenses.location_id', $locationId);
        }

        $totalExpenses = (float) (clone $expensesQuery)->sum('expenses.amount');

        $netProfit = $totalRevenue - $totalCogs - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        // Group Monthly Revenue directly via SQL
        $monthlyRevenueMap = (clone $salesQuery)
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as month, SUM(COALESCE(orders.paid_cash_amount, 0) + COALESCE(orders.paid_online_amount, 0)) as total_rev")
            ->groupBy(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m')"))
            ->pluck('total_rev', 'month')
            ->toArray();

        // Group Monthly Expenses directly via SQL
        $monthlyExpensesMap = (clone $expensesQuery)
            ->selectRaw("DATE_FORMAT(expenses.expense_date, '%Y-%m') as month, SUM(expenses.amount) as total_exp")
            ->groupBy(DB::raw("DATE_FORMAT(expenses.expense_date, '%Y-%m')"))
            ->pluck('total_exp', 'month')
            ->toArray();

        // Group Monthly COGS directly via SQL
        $monthlyCogsQuery = (clone $productProfitabilityQuery);
        $monthlyCogsMap = $monthlyCogsQuery
            ->selectRaw("DATE_FORMAT(orders.created_at, '%Y-%m') as month, SUM(order_items.quantity * COALESCE(product_variants.purchase_price, products.purchase_price, 0)) as cogs")
            ->groupBy(DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m')"))
            ->pluck('cogs', 'month')
            ->toArray();

        // Unique months list
        $allMonths = collect(array_merge(
            array_keys($monthlyRevenueMap),
            array_keys($monthlyExpensesMap),
            array_keys($monthlyCogsMap)
        ))->unique()->sort()->values();

        $monthlyRevenue  = [];
        $monthlyCogs     = [];
        $monthlyExpenses = [];

        foreach ($allMonths as $month) {
            $monthlyRevenue[$month]  = (float) ($monthlyRevenueMap[$month] ?? 0.0);
            $monthlyCogs[$month]     = (float) ($monthlyCogsMap[$month] ?? 0.0);
            $monthlyExpenses[$month] = (float) ($monthlyExpensesMap[$month] ?? 0.0);
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

    public function profitLossData(Request $request)
    {
        $this->authorize('view profit loss reports');

        $user = auth()->user();
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $locationId = ($user->location_id && !$user->hasRole('super-admin')) ? $user->location_id : $request->query('location_id');
        $searchValue = $request->input('search.value');

        $productProfitabilityQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('orders.payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $productProfitabilityQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $productProfitabilityQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $productProfitabilityQuery->where('orders.location_id', $locationId);
        }

        if ($searchValue) {
            $productProfitabilityQuery->where(function($pq) use ($searchValue) {
                $pq->where('products.name', 'like', "%{$searchValue}%")
                   ->orWhere('products.barcode', 'like', "%{$searchValue}%");
            });
        }

        $groupedQuery = (clone $productProfitabilityQuery)
            ->selectRaw('
                order_items.product_id,
                products.name,
                products.barcode,
                SUM(order_items.quantity) as qty_sold,
                SUM(order_items.total) as total_revenue,
                SUM(order_items.quantity * COALESCE(product_variants.purchase_price, products.purchase_price, 0)) as total_cost
            ')
            ->groupBy('order_items.product_id', 'products.name', 'products.barcode');

        $recordsTotal = DB::table('order_items')->distinct('product_id')->count('product_id');
        $recordsFiltered = DB::table(DB::raw("({$groupedQuery->toSql()}) as sub"))
            ->mergeBindings($groupedQuery->getQuery())
            ->count();

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        if ($length <= 0) $length = 25;

        $rows = $groupedQuery
            ->orderByDesc('order_items.product_id')
            ->skip($start)
            ->take($length)
            ->get();

        $productIds = $rows->pluck('product_id')->filter()->unique();
        $productImagesMap = ProductImage::whereIn('product_id', $productIds)
            ->where('is_primary', true)
            ->pluck('image_path', 'product_id');

        $data = [];
        foreach ($rows as $row) {
            $prodName = e($row->name ?? 'Unknown');
            $prodUrl = route('admin.products.show', $row->product_id);
            $imgPath = $productImagesMap[$row->product_id] ?? null;
            $imgUrl = $imgPath ? asset('uploads/'.$imgPath) : asset('website/assets/images/placeholder.png');

            $prodProfit = (float)$row->total_revenue - (float)$row->total_cost;
            $prodMargin = (float)$row->total_revenue > 0 ? ($prodProfit / (float)$row->total_revenue) * 100 : 0.0;

            $prodHtml = '
                <div class="d-flex align-items-center">
                    <img src="' . $imgUrl . '" alt="' . $prodName . '" class="rounded me-3 product-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                    <a href="' . $prodUrl . '" class="fw-semibold">' . $prodName . '</a>
                </div>';

            $profitBadge = '<span class="' . ($prodProfit >= 0 ? 'text-success' : 'text-danger') . ' fw-semibold">' . format_price($prodProfit) . '</span>';
            $marginBadge = '<span class="badge ' . ($prodProfit >= 0 ? 'bg-label-success' : 'bg-label-danger') . '">' . round($prodMargin, 1) . '%</span>';

            $data[] = [
                'product'       => $prodHtml,
                'barcode'       => '<code>' . e($row->barcode ?? '-') . '</code>',
                'qty_sold'      => '<span class="fw-semibold">' . (int)$row->qty_sold . '</span>',
                'total_revenue' => '<span class="text-success fw-semibold">' . format_price($row->total_revenue) . '</span>',
                'total_cost'    => '<span class="text-danger fw-semibold">' . format_price($row->total_cost) . '</span>',
                'profit'        => $profitBadge,
                'margin'        => $marginBadge,
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function exportProductsExcel(Request $request)
    {
        $this->authorize('view product reports');

        $subCategoryId = $request->query('sub_category_id') ?: $request->query('category_id');
        $status = $request->query('status');
        $stockStatus = $request->query('stock');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locations = Location::where('id', $user->location_id)->get();
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
        }

        $query = Product::with(['category', 'subCategory', 'primaryImage', 'inventories', 'variants.attributeValue.attribute']);

        if ($subCategoryId) {
            $query->where('sub_category_id', $subCategoryId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $products = $query->orderBy('name')->get();
        Product::preloadVariantStock($products);

        $productsList = collect();
        foreach ($products as $product) {
            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0)
                $pairSize = 1.0;

            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();
                $parentLocStock = [];
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent = (int) ($variantStock[$location->id]['parent'] ?? 0);
                    if ($locVariantSum > 0) {
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
                    'product'        => $product,
                    'pair_size'      => $pairSize,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'sub_category'   => $product->subCategory->name ?? '-',
                    'purchase_price' => (float) $product->purchase_price,
                    'sale_price'     => (float) $product->sale_price,
                    'total_stock'    => $parentStock,
                    'status'         => $product->status,
                    'variant_name'   => null,
                ]);

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
                        'product'        => $product,
                        'pair_size'      => $pairSize,
                        'name'           => $product->name,
                        'barcode'        => $product->barcode,
                        'sub_category'   => $product->subCategory->name ?? '-',
                        'purchase_price' => (float) $v->purchase_price,
                        'sale_price'     => (float) $v->sale_price,
                        'total_stock'    => $vStock,
                        'status'         => $v->status,
                        'variant_name'   => "{$attrName}: {$valName}",
                    ]);
                }
            } else {
                $totalStock = (int) $product->inventories
                    ->when($user->location_id && !$user->hasRole('super-admin'), fn($col) => $col->where('location_id', $user->location_id))
                    ->sum('quantity');

                $productsList->push([
                    'product'        => $product,
                    'pair_size'      => $pairSize,
                    'name'           => $product->name,
                    'barcode'        => $product->barcode,
                    'sub_category'   => $product->subCategory->name ?? '-',
                    'purchase_price' => (float) $product->purchase_price,
                    'sale_price'     => (float) $product->sale_price,
                    'total_stock'    => $totalStock,
                    'status'         => $product->status,
                    'variant_name'   => null,
                ]);
            }
        }

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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products Report');

        // Headers
        $headers = ['#', 'Product Name', 'Barcode', 'Sub Category', 'Purchase Price', 'Sale Price', 'Margin', 'Stock', 'Status'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers as $colIdx => $headerText) {
            $colLetter = $columns[$colIdx];
            $sheet->setCellValue($colLetter . '1', $headerText);
        }

        // Style Header Row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1D2939'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAECF0'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Populate Data Rows
        $rowIndex = 2;
        $totalPairs = 0;
        $totalLoosePcs = 0;

        foreach ($productsList as $idx => $item) {
            $statusText = ($item['status'] == 1 || $item['status'] === 'active') ? 'Active' : 'Inactive';
            $name = $item['name'] . ($item['variant_name'] ? ' (' . $item['variant_name'] . ')' : '');

            $purchasePrice = $item['purchase_price'];
            $salePrice = $item['sale_price'];
            $marginAmt = $salePrice - $purchasePrice;
            $marginPct = $purchasePrice > 0 ? round(($marginAmt / $purchasePrice) * 100, 1) : 0;
            $marginStr = '₹' . number_format($marginAmt, 2) . ' (' . $marginPct . '%)';

            /** @var Product $prod */
            $prod = $item['product'];
            $stockQty = (int) $item['total_stock'];

            if ($stockQty > 0) {
                if ($prod->pair_product) {
                    $pSize = $item['pair_size'] > 0 ? $item['pair_size'] : 1;
                    $totalPairs += (int) floor($stockQty / $pSize);
                    $totalLoosePcs += (int) ($stockQty % $pSize);
                } else {
                    $totalLoosePcs += $stockQty;
                }
            }

            $stockDisplay = $prod->formatStockDisplay($stockQty);

            $sheet->setCellValue('A' . $rowIndex, $idx + 1);
            $sheet->setCellValue('B' . $rowIndex, $name);
            $sheet->setCellValue('C' . $rowIndex, $item['barcode'] ?? '');
            $sheet->setCellValue('D' . $rowIndex, $item['sub_category'] ?? '-');
            $sheet->setCellValue('E' . $rowIndex, '₹' . number_format($purchasePrice, 2));
            $sheet->setCellValue('F' . $rowIndex, '₹' . number_format($salePrice, 2));
            $sheet->setCellValue('G' . $rowIndex, $marginStr);
            $sheet->setCellValue('H' . $rowIndex, $stockDisplay);
            $sheet->setCellValue('I' . $rowIndex, $statusText);

            $sheet->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        // Totals Row
        $stockTotalParts = [];
        if ($totalPairs > 0) {
            $stockTotalParts[] = number_format($totalPairs) . ' Pair' . ($totalPairs > 1 ? 's' : '');
        }
        if ($totalLoosePcs > 0 || empty($stockTotalParts)) {
            $stockTotalParts[] = number_format($totalLoosePcs) . ' Pcs';
        }
        $stockTotalDisplay = implode(' ', $stockTotalParts);

        $sheet->setCellValue('A' . $rowIndex, 'Total');
        $sheet->setCellValue('H' . $rowIndex, $stockTotalDisplay);
        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet->getRowDimension($rowIndex)->setRowHeight(24);

        $lastRow = $rowIndex;

        // Thin Border for all cells in table
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];
        $sheet->getStyle('A1:I' . $lastRow)->applyFromArray($borderStyle);

        // Alignments
        $sheet->getStyle('A1:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I1:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto-adjust Column Widths
        foreach ($columns as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Reports', 'export', null, null, null, 'Products report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'products_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportStockInventoryExcel(Request $request)
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

        $query = Product::with(['category', 'subCategory', 'primaryImage', 'inventories.location', 'variants.attributeValue.attribute']);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('name')->get();
        Product::preloadVariantStock($products);

        $productsList = collect();
        foreach ($products as $product) {
            $purchasePrice = (float) $product->purchase_price;
            $salePrice = (float) $product->sale_price;
            $mrpPrice = (float) (($product->mrp ?? 0) > 0 ? $product->mrp : $product->sale_price);

            $sizes = collect($product->custom_sizes ?? [])->pluck('size')->map(fn($s) => (float) $s)->filter(fn($s) => $s > 0);
            $pairSize = ($product->pair_product && $sizes->count() > 0) ? (float) $sizes->max() : 1.0;
            if ($pairSize <= 0)
                $pairSize = 1.0;

            if ($product->type === 'variable') {
                $variantStock = $product->getVariantStock();

                $parentLocStock = [];
                $hasVariantStock = false;
                foreach ($locations as $location) {
                    $locVariantSum = array_sum($variantStock[$location->id]['variants'] ?? []);
                    $locParent = (int) ($variantStock[$location->id]['parent'] ?? 0);
                    if ($locVariantSum > 0) {
                        $hasVariantStock = true;
                        $pQty = $locVariantSum;
                    } else {
                        $pQty = max(0, $locParent);
                    }
                    $parentLocStock[$location->id] = $pQty;
                }
                $parentTotal = array_sum($parentLocStock);

                if ($parentTotal <= 0) {
                    foreach ($locations as $location) {
                        $inventory = $product->inventories->firstWhere('location_id', $location->id);
                        $pQty = $inventory ? (int) $inventory->quantity : 0;
                        $parentLocStock[$location->id] = $pQty;
                    }
                    $parentTotal = array_sum($parentLocStock);
                    $hasVariantStock = false;
                }

                if ($hasVariantStock) {
                    $parentPurchaseVal = 0.0;
                    $parentMrpVal = 0.0;
                } else {
                    $effectiveTotal = $product->pair_product ? ($parentTotal / $pairSize) : (float) $parentTotal;
                    $parentPurchaseVal = $effectiveTotal * $purchasePrice;
                    $parentMrpVal = $effectiveTotal * $mrpPrice;
                }

                $variantRows = [];
                foreach ($product->variants as $v) {
                    $vLocStock = [];
                    foreach ($locations as $location) {
                        $vQty = max(0, (int) ($variantStock[$location->id]['variants'][$v->id] ?? 0));
                        $vLocStock[$location->id] = $vQty;
                    }
                    $vTotal = array_sum($vLocStock);

                    $vPurchasePrice = (float) $v->purchase_price;
                    $vSalePrice = (float) $v->sale_price;
                    $vMrpPrice = (float) (($v->mrp ?? 0) > 0 ? $v->mrp : $vSalePrice);
                    $vEffectiveTotal = $product->pair_product ? ($vTotal / $pairSize) : (float) $vTotal;

                    $attrName = $v->attributeValue->attribute->name ?? '';
                    $valName = $v->attributeValue->value ?? '';

                    $variantRows[] = [
                        'product' => $product,
                        'pair_size' => $pairSize,
                        'name' => $product->name . " ({$attrName}: {$valName})",
                        'barcode' => $product->barcode,
                        'category' => $product->category->name ?? '-',
                        'stock' => $vLocStock,
                        'total' => $vTotal,
                        'purchase_value' => $vEffectiveTotal * $vPurchasePrice,
                        'mrp_value' => $vEffectiveTotal * $vMrpPrice,
                        'status' => $v->status,
                    ];
                }

                $productsList->push([
                    'product' => $product,
                    'pair_size' => $pairSize,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'category' => $product->category->name ?? '-',
                    'stock' => $parentLocStock,
                    'total' => $parentTotal,
                    'purchase_value' => $parentPurchaseVal,
                    'mrp_value' => $parentMrpVal,
                    'status' => $product->status,
                ]);

                foreach ($variantRows as $vRow) {
                    $productsList->push($vRow);
                }
            } else {
                $stock = [];
                foreach ($locations as $location) {
                    $inventory = $product->inventories->firstWhere('location_id', $location->id);
                    $sQty = $inventory ? (int) $inventory->quantity : 0;
                    $stock[$location->id] = $sQty;
                }
                $total = array_sum($stock);
                $effectiveQty = $product->pair_product ? ($total / $pairSize) : (float) $total;

                $productsList->push([
                    'product' => $product,
                    'pair_size' => $pairSize,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'category' => $product->category->name ?? '-',
                    'stock' => $stock,
                    'total' => $total,
                    'purchase_value' => $effectiveQty * $purchasePrice,
                    'mrp_value' => $effectiveQty * $mrpPrice,
                    'status' => $product->status,
                ]);
            }
        }

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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Inventory');

        // Headers
        $headers = ['#', 'Product Name', 'Barcode', 'Category'];
        foreach ($locations as $location) {
            $headers[] = $location->name;
        }
        $headers[] = 'Total Stock';
        $headers[] = 'Purchase Value';
        $headers[] = 'MRP Value';

        $colCount = count($headers);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        for ($i = 0; $i < $colCount; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . '1', $headers[$i]);
        }

        // Header Styling: Bold, Grey Fill, Height
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1D2939'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAECF0'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Data Rows
        $rowIndex = 2;

        foreach ($productsList as $idx => $item) {
            /** @var Product $prod */
            $prod = $item['product'];

            $sheet->setCellValue('A' . $rowIndex, $idx + 1);
            $sheet->setCellValue('B' . $rowIndex, $item['name']);
            $sheet->setCellValue('C' . $rowIndex, $item['barcode'] ?? '');
            $sheet->setCellValue('D' . $rowIndex, $item['category'] ?? '-');

            $colIdx = 5;
            foreach ($locations as $location) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $lStock = (int) ($item['stock'][$location->id] ?? 0);
                $lDisplay = $prod->formatStockDisplay($lStock);
                $sheet->setCellValue($colLetter . $rowIndex, $lDisplay);
                $colIdx++;
            }

            $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
            $purchaseValCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
            $mrpValCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);

            $totalQty = (int) $item['total'];
            $totalDisplay = $prod->formatStockDisplay($totalQty);

            $sheet->setCellValue($totalCol . $rowIndex, $totalDisplay);
            $sheet->setCellValue($purchaseValCol . $rowIndex, '₹' . number_format($item['purchase_value'], 2));
            $sheet->setCellValue($mrpValCol . $rowIndex, '₹' . number_format($item['mrp_value'], 2));

            $sheet->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        // Totals Row via exact computeStockTotals helper
        $calcQuery = clone $query;
        $totals = $this->computeStockTotals($calcQuery, $locations, $user->location_id && !$user->hasRole('super-admin') ? $user->location_id : null);

        $sheet->setCellValue('A' . $rowIndex, 'Total');

        $colIdx = 5;
        foreach ($locations as $location) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $locFormatted = str_replace('<br>', ' ', $totals['location_totals'][$location->id] ?? '0 Pcs');
            $sheet->setCellValue($colLetter . $rowIndex, $locFormatted);
            $colIdx++;
        }

        $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
        $purchaseValCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
        $mrpValCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);

        $overallFormatted = str_replace('<br>', ' ', $totals['qty_total']);
        $sheet->setCellValue($totalCol . $rowIndex, $overallFormatted);
        $sheet->setCellValue($purchaseValCol . $rowIndex, '₹' . number_format($totals['purchase_total'], 2));
        $sheet->setCellValue($mrpValCol . $rowIndex, '₹' . number_format($totals['mrp_total'], 2));

        $sheet->getStyle('A' . $rowIndex . ':' . $lastColLetter . $rowIndex)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowIndex . ':' . $lastColLetter . $rowIndex)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet->getRowDimension($rowIndex)->setRowHeight(26);

        $lastRow = $rowIndex;

        // Thin Borders for all cells in table
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];
        $sheet->getStyle('A1:' . $lastColLetter . $lastRow)->applyFromArray($borderStyle);

        // Alignments
        $sheet->getStyle('A1:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto-adjust Column Widths
        for ($i = 1; $i <= $colCount; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        ActivityLogger::log('Reports', 'export', null, null, null, 'Stock inventory report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'stock_inventory_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function getImageBase64(?Product $product): string
    {
        if (!$product || !$product->primaryImage || empty($product->primaryImage->image_path)) {
            return '';
        }

        $rawPath = ltrim($product->primaryImage->image_path, '/\\');
        $cleanPath = preg_replace('#^uploads[\/]#i', '', $rawPath);

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

    public function exportPurchasesExcel(Request $request)
    {
        $this->authorize('view purchase reports');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $supplierId = $request->query('supplier_id');
        $isGst = $request->query('is_gst');

        $user = auth()->user();
        $query = Purchase::with(['supplier', 'items.product'])
            ->where('status', Purchase::STATUS_APPROVE)
            ->when($user->location_id && !$user->hasRole('super-admin'), function ($q) use ($user) {
                $q->whereHas('items.allocations', function ($sub) use ($user) {
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
            $query->where('is_gst', (bool) $isGst);
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

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Purchase List
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Purchase List');

        $headers1 = ['#', 'Purchase No', 'Supplier', 'Type', 'Status', 'Date', 'Total Amount'];
        $columns1 = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers1 as $colIdx => $headerText) {
            $sheet1->setCellValue($columns1[$colIdx] . '1', $headerText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet1->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(1)->setRowHeight(26);

        $purchaseStatuses = [1 => 'Pending', 2 => 'Approve', 3 => 'Rejected'];
        $rowIndex = 2;
        $totalAmountSum = 0;

        foreach ($invoices as $idx => $invoice) {
            $statusLabel = $purchaseStatuses[$invoice->status] ?? 'Unknown';
            $typeLabel = $invoice->is_gst ? 'GST' : 'Non GST';
            $amount = (float) $invoice->total_amount;
            $totalAmountSum += $amount;

            $sheet1->setCellValue('A' . $rowIndex, $idx + 1);
            $sheet1->setCellValue('B' . $rowIndex, $invoice->invoice_no);
            $sheet1->setCellValue('C' . $rowIndex, $invoice->supplier->name ?? 'Unknown');
            $sheet1->setCellValue('D' . $rowIndex, $typeLabel);
            $sheet1->setCellValue('E' . $rowIndex, $statusLabel);
            $sheet1->setCellValue('F' . $rowIndex, $invoice->created_at->format('d-m-Y'));
            $sheet1->setCellValue('G' . $rowIndex, '₹' . number_format($amount, 2));

            $sheet1->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        // Totals Row
        $sheet1->setCellValue('A' . $rowIndex, 'Total');
        $sheet1->setCellValue('G' . $rowIndex, '₹' . number_format($totalAmountSum, 2));
        $sheet1->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFont()->setBold(true);
        $sheet1->getRowDimension($rowIndex)->setRowHeight(22);

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];
        $sheet1->getStyle('A1:G' . $rowIndex)->applyFromArray($borderStyle);
        $sheet1->getStyle('A1:A' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('D1:F' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('G1:G' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns1 as $colLetter) {
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 2: Top Purchased Products
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Top Purchased Products');

        $headers2 = ['#', 'Product Name', 'Barcode', 'Qty Purchased', 'Total Cost'];
        $columns2 = ['A', 'B', 'C', 'D', 'E'];

        foreach ($headers2 as $colIdx => $headerText) {
            $sheet2->setCellValue($columns2[$colIdx] . '1', $headerText);
        }
        $sheet2->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet2->getRowDimension(1)->setRowHeight(26);

        $rowIndex2 = 2;
        $totalQtySum = 0;
        $totalCostSum = 0;

        foreach ($productPurchases as $idx => $item) {
            $qty = (int) $item->qty_purchased;
            $cost = (float) $item->total_cost;
            $totalQtySum += $qty;
            $totalCostSum += $cost;

            $sheet2->setCellValue('A' . $rowIndex2, $idx + 1);
            $sheet2->setCellValue('B' . $rowIndex2, $item->product->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $rowIndex2, $item->product->barcode ?? '-');
            $sheet2->setCellValue('D' . $rowIndex2, $qty);
            $sheet2->setCellValue('E' . $rowIndex2, '₹' . number_format($cost, 2));

            $sheet2->getRowDimension($rowIndex2)->setRowHeight(20);
            $rowIndex2++;
        }

        // Totals Row for Sheet 2
        $sheet2->setCellValue('A' . $rowIndex2, 'Total');
        $sheet2->setCellValue('D' . $rowIndex2, $totalQtySum);
        $sheet2->setCellValue('E' . $rowIndex2, '₹' . number_format($totalCostSum, 2));
        $sheet2->getStyle('A' . $rowIndex2 . ':E' . $rowIndex2)->getFont()->setBold(true);
        $sheet2->getRowDimension($rowIndex2)->setRowHeight(22);

        $sheet2->getStyle('A1:E' . $rowIndex2)->applyFromArray($borderStyle);
        $sheet2->getStyle('A1:A' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C1:C' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D1:D' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet2->getStyle('E1:E' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns2 as $colLetter) {
            $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Reports', 'export', null, null, null, 'Purchases report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'purchases_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportSalesExcel(Request $request)
    {
        $this->authorize('view sale reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locationId = $user->location_id;
        } else {
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
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
            $query->where('is_gst', (bool) $isGst);
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

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Orders List
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Orders List');

        $headers1 = ['#', 'Order No', 'Customer', 'Location', 'Payment Status', 'Payment Method', 'Date', 'Final Amount'];
        $columns1 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers1 as $colIdx => $headerText) {
            $sheet1->setCellValue($columns1[$colIdx] . '1', $headerText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet1->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(1)->setRowHeight(26);

        $paymentStatuses = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
        $rowIndex = 2;
        $totalFinalAmountSum = 0;

        foreach ($orders as $idx => $order) {
            $payStatusLabel = $paymentStatuses[$order->payment_status] ?? 'Pending';
            $payMethodLabel = strtoupper(str_replace('_', ' ', $order->payment_method ?? '-'));
            $finalAmount = (float) $order->final_amount;
            $totalFinalAmountSum += $finalAmount;

            $sheet1->setCellValue('A' . $rowIndex, $idx + 1);
            $sheet1->setCellValue('B' . $rowIndex, $order->order_no);
            $sheet1->setCellValue('C' . $rowIndex, $order->customer->name ?? 'Walk-in');
            $sheet1->setCellValue('D' . $rowIndex, $order->location->name ?? '-');
            $sheet1->setCellValue('E' . $rowIndex, $payStatusLabel);
            $sheet1->setCellValue('F' . $rowIndex, $payMethodLabel);
            $sheet1->setCellValue('G' . $rowIndex, $order->created_at->format('d-m-Y'));
            $sheet1->setCellValue('H' . $rowIndex, '₹' . number_format($finalAmount, 2));

            $sheet1->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        // Totals Row
        $sheet1->setCellValue('A' . $rowIndex, 'Total');
        $sheet1->setCellValue('H' . $rowIndex, '₹' . number_format($totalFinalAmountSum, 2));
        $sheet1->getStyle('A' . $rowIndex . ':H' . $rowIndex)->getFont()->setBold(true);
        $sheet1->getRowDimension($rowIndex)->setRowHeight(22);

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];
        $sheet1->getStyle('A1:H' . $rowIndex)->applyFromArray($borderStyle);
        $sheet1->getStyle('A1:A' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('E1:G' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('H1:H' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns1 as $colLetter) {
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 2: Top Selling Products
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Top Selling Products');

        $headers2 = ['#', 'Product Name', 'Barcode', 'Qty Sold', 'Total Revenue'];
        $columns2 = ['A', 'B', 'C', 'D', 'E'];

        foreach ($headers2 as $colIdx => $headerText) {
            $sheet2->setCellValue($columns2[$colIdx] . '1', $headerText);
        }
        $sheet2->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet2->getRowDimension(1)->setRowHeight(26);

        $rowIndex2 = 2;
        $totalQtySoldSum = 0;
        $totalRevenueSum = 0;

        foreach ($productSales as $idx => $item) {
            $qty = (int) $item->qty_sold;
            $rev = (float) $item->total_revenue;
            $totalQtySoldSum += $qty;
            $totalRevenueSum += $rev;

            $sheet2->setCellValue('A' . $rowIndex2, $idx + 1);
            $sheet2->setCellValue('B' . $rowIndex2, $item->product->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $rowIndex2, $item->product->barcode ?? '-');
            $sheet2->setCellValue('D' . $rowIndex2, $qty);
            $sheet2->setCellValue('E' . $rowIndex2, '₹' . number_format($rev, 2));

            $sheet2->getRowDimension($rowIndex2)->setRowHeight(20);
            $rowIndex2++;
        }

        // Totals Row for Sheet 2
        $sheet2->setCellValue('A' . $rowIndex2, 'Total');
        $sheet2->setCellValue('D' . $rowIndex2, $totalQtySoldSum);
        $sheet2->setCellValue('E' . $rowIndex2, '₹' . number_format($totalRevenueSum, 2));
        $sheet2->getStyle('A' . $rowIndex2 . ':E' . $rowIndex2)->getFont()->setBold(true);
        $sheet2->getRowDimension($rowIndex2)->setRowHeight(22);

        $sheet2->getStyle('A1:E' . $rowIndex2)->applyFromArray($borderStyle);
        $sheet2->getStyle('A1:A' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C1:C' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D1:D' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet2->getStyle('E1:E' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns2 as $colLetter) {
            $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Reports', 'export', null, null, null, 'Sales report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'sales_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportGstJson(Request $request)
    {
        $this->authorize('view sale reports');

        $monthInput = $request->query('month'); // e.g. "2026-07"
        if ($monthInput && preg_match('/^(\d{4})-(\d{2})$/', $monthInput, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        } elseif ($monthInput && preg_match('/^(\d{2})-(\d{4})$/', $monthInput, $m)) {
            $month = (int) $m[1];
            $year = (int) $m[2];
        } else {
            $month = (int) now()->format('m');
            $year = (int) now()->format('Y');
        }

        $fp = sprintf('%02d%04d', $month, $year);

        $companyGstin = Location::whereNotNull('gst_number')
            ->where('gst_number', '!=', '')
            ->value('gst_number') ?? '24SCOPS0159A1ZB';
        $companyGstin = strtoupper(trim($companyGstin));
        if (!$companyGstin) {
            $companyGstin = '24SCOPS0159A1ZB';
        }

        $businessStateCode = (strlen($companyGstin) >= 2 && is_numeric(substr($companyGstin, 0, 2)))
            ? substr($companyGstin, 0, 2)
            : '24';

        $defaultGstRate = (float) \App\Models\Setting::getValue('purchase_gst_rate', 3);
        if ($defaultGstRate <= 0) {
            $defaultGstRate = 3.0;
        }

        $user = auth()->user();
        $userLocationId = ($user->location_id && !$user->hasRole('super-admin')) ? $user->location_id : null;

        $ordersQuery = Order::with(['customer', 'items.product', 'items.variant'])
            ->where('order_type', 'sale')
            ->where('is_gst', 1)
            ->whereIn('status', [
                Order::STATUS_APPROVE,
                Order::STATUS_SHIPPED,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_DELIVERED
            ])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($userLocationId) {
            $ordersQuery->where('location_id', $userLocationId);
        }

        $orders = $ordersQuery->orderBy('created_at', 'asc')->get();

        if ($orders->isEmpty()) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No GST sales records found for the selected period.'
                ], 404);
            }
            return redirect()->back()->with('error', 'No GST sales records found for the selected period.');
        }

        $allMonthOrdersQuery = Order::where('order_type', 'sale')
            ->where('is_gst', 1)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($userLocationId) {
            $allMonthOrdersQuery->where('location_id', $userLocationId);
        }

        $allMonthOrders = $allMonthOrdersQuery->orderBy('id', 'asc')->get();

        $b2cOrders = $orders->all();

        $getStateCode = function ($order) use ($businessStateCode) {
            if ($order->customer && !empty(trim($order->customer->gst_no ?? ''))) {
                $gstNo = strtoupper(trim($order->customer->gst_no));
                if (strlen($gstNo) >= 2 && is_numeric(substr($gstNo, 0, 2))) {
                    return substr($gstNo, 0, 2);
                }
            }
            return $businessStateCode;
        };

        $b2cGrouped = [];
        foreach ($b2cOrders as $order) {
            $pos = $getStateCode($order);
            $splyTy = ($pos === $businessStateCode) ? 'INTRA' : 'INTER';

            $invVal = (float) round((float) $order->final_amount, 2);
            $taxRate = $defaultGstRate;
            $taxableVal = (float) round($invVal / (1 + ($taxRate / 100)), 2);
            $taxAmt = (float) round($invVal - $taxableVal, 2);

            $isInterState = ($splyTy === 'INTER');
            $iamt = $isInterState ? $taxAmt : 0.0;
            $camt = $isInterState ? 0.0 : (float) round($taxAmt / 2, 2);
            $samt = $isInterState ? 0.0 : (float) round($taxAmt / 2, 2);

            $key = $splyTy . '_' . $pos . '_' . number_format($taxRate, 4, '.', '');

            if (!isset($b2cGrouped[$key])) {
                $b2cGrouped[$key] = [
                    'sply_ty' => $splyTy,
                    'pos' => $pos,
                    'typ' => 'OE',
                    'txval' => 0.0,
                    'rt' => (float) $taxRate,
                    'camt' => 0.0,
                    'samt' => 0.0,
                    'iamt' => 0.0,
                    'csamt' => 0.0
                ];
            }

            $b2cGrouped[$key]['txval'] = (float) round($b2cGrouped[$key]['txval'] + $taxableVal, 2);
            $b2cGrouped[$key]['camt'] = (float) round($b2cGrouped[$key]['camt'] + $camt, 2);
            $b2cGrouped[$key]['samt'] = (float) round($b2cGrouped[$key]['samt'] + $samt, 2);
            $b2cGrouped[$key]['iamt'] = (float) round($b2cGrouped[$key]['iamt'] + $iamt, 2);
        }
        $b2cList = array_values($b2cGrouped);

        $buildHsn = function ($ordersList) use ($getStateCode, $businessStateCode, $defaultGstRate) {
            $hsnGrouped = [];
            foreach ($ordersList as $order) {
                $pos = $getStateCode($order);
                $isInterState = ($pos !== $businessStateCode);

                foreach ($order->items as $item) {
                    $product = $item->product;
                    $hsnCode = !empty($product?->hsn) ? (string) $product->hsn : (!empty($product?->hsn_code) ? (string) $product->hsn_code : '7117');
                    $uqc = !empty($product?->uqc) ? (string) $product->uqc : 'UNT';
                    $qty = (float) $item->quantity;
                    $itemTotal = (float) $item->total;

                    $taxRate = $defaultGstRate;
                    $taxableVal = (float) round($itemTotal / (1 + ($taxRate / 100)), 2);
                    $taxAmt = (float) round($itemTotal - $taxableVal, 2);

                    $iamt = $isInterState ? $taxAmt : 0.0;
                    $camt = $isInterState ? 0.0 : (float) round($taxAmt / 2, 2);
                    $samt = $isInterState ? 0.0 : (float) round($taxAmt / 2, 2);

                    $key = $hsnCode . '_' . $uqc . '_' . number_format($taxRate, 2, '.', '');

                    if (!isset($hsnGrouped[$key])) {
                        $hsnGrouped[$key] = [
                            'hsn_sc' => $hsnCode,
                            'uqc' => $uqc,
                            'qty' => 0.0,
                            'txval' => 0.0,
                            'iamt' => 0.0,
                            'camt' => 0.0,
                            'samt' => 0.0,
                            'csamt' => 0.0,
                            'rt' => (float) $taxRate
                        ];
                    }

                    $hsnGrouped[$key]['qty'] += $qty;
                    $hsnGrouped[$key]['txval'] = (float) round($hsnGrouped[$key]['txval'] + $taxableVal, 2);
                    $hsnGrouped[$key]['iamt'] = (float) round($hsnGrouped[$key]['iamt'] + $iamt, 2);
                    $hsnGrouped[$key]['camt'] = (float) round($hsnGrouped[$key]['camt'] + $camt, 2);
                    $hsnGrouped[$key]['samt'] = (float) round($hsnGrouped[$key]['samt'] + $samt, 2);
                }
            }

            $result = [];
            $num = 1;
            foreach ($hsnGrouped as $row) {
                $result[] = [
                    'num' => $num++,
                    'hsn_sc' => $row['hsn_sc'],
                    'uqc' => $row['uqc'],
                    'qty' => (float) $row['qty'],
                    'txval' => (float) $row['txval'],
                    'iamt' => (float) $row['iamt'],
                    'camt' => (float) $row['camt'],
                    'samt' => (float) $row['samt'],
                    'csamt' => 0.0,
                    'rt' => (float) $row['rt'],
                ];
            }
            return $result;
        };

        $hsnB2c = $buildHsn($b2cOrders);

        $docsList = [];
        if ($allMonthOrders->isNotEmpty()) {
            $groupedByPrefix = [];
            foreach ($allMonthOrders as $ord) {
                $no = (string) $ord->order_no;
                preg_match('/^([A-Za-z_-]*)(.*)$/', $no, $matches);
                $prefix = $matches[1] ?? 'ORD';
                $groupedByPrefix[$prefix][] = $ord;
            }

            $docNum = 1;
            foreach ($groupedByPrefix as $prefix => $ordersInGroup) {
                $sortedGroup = collect($ordersInGroup)->sortBy(function ($ord) {
                    return (int) preg_replace('/\D/', '', (string) $ord->order_no);
                })->values();

                $fromOrd = $sortedGroup[0]->order_no;
                $toOrd = $sortedGroup[count($sortedGroup) - 1]->order_no;
                $totnum = count($sortedGroup);
                $cancel = $sortedGroup->where('status', Order::STATUS_DECLINE)->count();
                $netIssue = $totnum - $cancel;

                $docsList[] = [
                    'num' => $docNum++,
                    'from' => $fromOrd,
                    'to' => $toOrd,
                    'totnum' => $totnum,
                    'cancel' => $cancel,
                    'net_issue' => $netIssue,
                ];
            }
        }

        $docIssue = [
            'doc_det' => [
                [
                    'doc_num' => 1,
                    'doc_typ' => 'Invoices for outward supply',
                    'docs' => $docsList
                ]
            ]
        ];

        $jsonPayload = [
            'gstin' => $companyGstin,
            'fp' => $fp,
            'b2c' => $b2cList,
            'hsn' => [
                'hsn_b2c' => $hsnB2c,
            ],
            'doc_issue' => $docIssue
        ];

        ActivityLogger::log('Reports', 'export', null, null, null, 'GSTR-1 JSON exported for period ' . $fp);

        $monthInt = (int) $month;
        $startYear = $monthInt >= 4 ? (int) $year : ((int) $year - 1);
        $endYear = $startYear + 1;
        $filename = 'CHETAN IMITATION_' . $startYear . ' - ' . $endYear . '_' . $monthInt . '.json';
        $jsonContent = json_encode($jsonPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return response()->streamDownload(function () use ($jsonContent) {
            echo $jsonContent;
        }, $filename, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportProfitLossExcel(Request $request)
    {
        $this->authorize('view profit loss reports');

        $user = auth()->user();
        if ($user->location_id && !$user->hasRole('super-admin')) {
            $locationId = $user->location_id;
        } else {
            $locationId = $request->query('location_id');
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $salesQuery = Order::query()
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('orders.payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $salesQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $salesQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $salesQuery->where('orders.location_id', $locationId);
        }

        $totalRevenue = (float) (clone $salesQuery)
            ->selectRaw('SUM(COALESCE(orders.paid_cash_amount, 0) + COALESCE(orders.paid_online_amount, 0)) as total_rev')
            ->value('total_rev');

        $productProfitabilityQuery = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.order_type', 'sale')
            ->whereIn('orders.status', [Order::STATUS_APPROVE, Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])
            ->whereIn('orders.payment_status', [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_PARTIAL])
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('orders.location_id', $user->location_id));

        if ($startDate) {
            $productProfitabilityQuery->whereDate('orders.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $productProfitabilityQuery->whereDate('orders.created_at', '<=', $endDate);
        }
        if ($locationId) {
            $productProfitabilityQuery->where('orders.location_id', $locationId);
        }

        $productProfitability = $productProfitabilityQuery
            ->selectRaw('
                order_items.product_id,
                products.name,
                products.barcode,
                SUM(order_items.quantity) as qty_sold,
                SUM(order_items.total) as total_revenue,
                SUM(order_items.quantity * COALESCE(product_variants.purchase_price, products.purchase_price, 0)) as total_cost
            ')
            ->groupBy('order_items.product_id', 'products.name', 'products.barcode')
            ->orderByDesc('order_items.product_id')
            ->get();

        $totalCogs = (float) $productProfitability->sum('total_cost');

        $expensesQuery = Expense::query()
            ->when($user->location_id && !$user->hasRole('super-admin'), fn($q) => $q->where('expenses.location_id', $user->location_id));
        if ($startDate) {
            $expensesQuery->whereDate('expenses.expense_date', '>=', $startDate);
        }
        if ($endDate) {
            $expensesQuery->whereDate('expenses.expense_date', '<=', $endDate);
        }
        if ($locationId) {
            $expensesQuery->where('expenses.location_id', $locationId);
        }
        $totalExpenses = (float) $expensesQuery->sum('expenses.amount');

        $netProfit = $totalRevenue - $totalCogs - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        if ($totalRevenue <= 0 && $totalExpenses <= 0 && $productProfitability->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // Border style
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        // Sheet 1: Overview
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('P&L Overview');

        $headers1 = ['Metric', 'Amount'];
        $sheet1->setCellValue('A1', $headers1[0]);
        $sheet1->setCellValue('B1', $headers1[1]);

        $sheet1->getStyle('A1:B1')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(1)->setRowHeight(26);

        $overviewData = [
            ['Total Revenue', '₹' . number_format($totalRevenue, 2)],
            ['Cost of Goods Sold (COGS)', '₹' . number_format($totalCogs, 2)],
            ['Operating Expenses', '₹' . number_format($totalExpenses, 2)],
            ['Net Profit / (Loss)', '₹' . number_format($netProfit, 2)],
            ['Profit Margin (%)', number_format($profitMargin, 2) . '%'],
        ];

        $r = 2;
        foreach ($overviewData as $row) {
            $sheet1->setCellValue('A' . $r, $row[0]);
            $sheet1->setCellValue('B' . $r, $row[1]);
            $sheet1->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet1->getStyle('A1:B' . ($r - 1))->applyFromArray($borderStyle);
        $sheet1->getStyle('B2:B' . ($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet1->getStyle('A4:B5')->getFont()->setBold(true);

        $sheet1->getColumnDimension('A')->setAutoSize(true);
        $sheet1->getColumnDimension('B')->setAutoSize(true);

        // Sheet 2: Product Profitability
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Product Profitability');

        $headers2 = ['#', 'Product Name', 'Barcode', 'Qty Sold', 'Total Revenue', 'Purchase Cost', 'Net Profit', 'Margin (%)'];
        $columns2 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers2 as $colIdx => $headerText) {
            $sheet2->setCellValue($columns2[$colIdx] . '1', $headerText);
        }

        $sheet2->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet2->getRowDimension(1)->setRowHeight(26);

        $rowIndex2 = 2;
        $sumQtySold = 0;
        $sumRevenue = 0;
        $sumCost = 0;
        $sumNetProfit = 0;

        $idx = 1;
        foreach ($productProfitability as $item) {
            $qty = (int) $item->qty_sold;
            $rev = (float) $item->total_revenue;
            $cost = (float) $item->total_cost;
            $net = $rev - $cost;
            $margin = $rev > 0 ? round(($net / $rev) * 100, 1) : 0;

            $sumQtySold += $qty;
            $sumRevenue += $rev;
            $sumCost += $cost;
            $sumNetProfit += $net;

            $sheet2->setCellValue('A' . $rowIndex2, $idx++);
            $sheet2->setCellValue('B' . $rowIndex2, $item->name ?? 'Unknown');
            $sheet2->setCellValue('C' . $rowIndex2, $item->barcode ?? '-');
            $sheet2->setCellValue('D' . $rowIndex2, $qty);
            $sheet2->setCellValue('E' . $rowIndex2, '₹' . number_format($rev, 2));
            $sheet2->setCellValue('F' . $rowIndex2, '₹' . number_format($cost, 2));
            $sheet2->setCellValue('G' . $rowIndex2, '₹' . number_format($net, 2));
            $sheet2->setCellValue('H' . $rowIndex2, $margin . '%');

            $sheet2->getRowDimension($rowIndex2)->setRowHeight(20);
            $rowIndex2++;
        }

        // Totals Row
        $overallMargin = $sumRevenue > 0 ? round(($sumNetProfit / $sumRevenue) * 100, 1) : 0;
        $sheet2->setCellValue('A' . $rowIndex2, 'Total');
        $sheet2->setCellValue('D' . $rowIndex2, $sumQtySold);
        $sheet2->setCellValue('E' . $rowIndex2, '₹' . number_format($sumRevenue, 2));
        $sheet2->setCellValue('F' . $rowIndex2, '₹' . number_format($sumCost, 2));
        $sheet2->setCellValue('G' . $rowIndex2, '₹' . number_format($sumNetProfit, 2));
        $sheet2->setCellValue('H' . $rowIndex2, $overallMargin . '%');

        $sheet2->getStyle('A' . $rowIndex2 . ':H' . $rowIndex2)->getFont()->setBold(true);
        $sheet2->getRowDimension($rowIndex2)->setRowHeight(22);

        $sheet2->getStyle('A1:H' . $rowIndex2)->applyFromArray($borderStyle);
        $sheet2->getStyle('A1:A' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C1:C' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('D1:H' . $rowIndex2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns2 as $colLetter) {
            $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Reports', 'export', null, null, null, 'Profit & Loss report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'profit_loss_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
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

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $locationId = $request->query('location_id');
        $source = $request->query('source');
        $paymentMethod = $request->query('payment_method');
        $paymentStatus = $request->query('payment_status');

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        if ($user->location_id && !$isSuperAdmin) {
            $locations = Location::where('id', $user->location_id)->get();
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
        $totalCount = $orders->count();
        $avgAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0.0;

        $pendingOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING);
        $pendingFullAmount = (float) $pendingOrders->sum('final_amount');

        $partialOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PARTIAL);
        $partialDueAmount = (float) $partialOrders->sum(function ($order) {
            $paid = (float) ($order->paid_cash_amount ?? 0) + (float) ($order->paid_online_amount ?? 0);
            return max(0, (float) $order->final_amount - $paid);
        });

        $pendingAmount = $pendingFullAmount + $partialDueAmount;
        $pendingCount = $pendingOrders->count() + $partialOrders->count();

        $refundQuery = Order::with(['customer', 'payment', 'cancellationRequest'])
            ->where('order_type', 'sale')
            ->where('status', Order::STATUS_DECLINE)
            ->whereHas('cancellationRequest', function ($q) {
                $q
                    ->where('status', \App\Models\OrderCancellationRequest::STATUS_APPROVED)
                    ->where('refund_amount', '>', 0);
            });
        $applyCommonFilters($refundQuery);

        $refundedOrders = $refundQuery->latest()->get();
        $refundAmount = (float) $refundedOrders->sum(fn($order) => (float) $order->cancellationRequest->refund_amount);
        $refundCount = $refundedOrders->count();

        $normalizePaymentMethod = function (?string $method): string {
            return match ($method) {
                'razorpay' => 'online',
                'cod' => 'cod',
                'cash' => 'cash',
                'online' => 'online',
                'online_cash' => 'online_cash',
                null, '' => 'pending',
                default => (string) $method,
            };
        };

        $paymentMethodLabel = function (?string $method) use ($normalizePaymentMethod): string {
            return match ($normalizePaymentMethod($method)) {
                'cod' => 'COD',
                'online' => 'Online',
                'cash' => 'Cash',
                'online_cash' => 'Cash + Online',
                'pending' => 'Pending',
                default => ucwords(str_replace('_', ' ', (string) $method)),
            };
        };

        $paymentTrend = [];
        $trendGroup = $orders
            ->groupBy(fn($order) => $order->created_at->format('Y-m'))
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
        foreach ($orders->groupBy(fn($order) => $order->source ?? 'POS') as $src => $grp) {
            $sourceData[strtoupper($src)] = (float) $grp->sum('final_amount');
        }

        $availableSources = ['POS', 'ONLINE'];
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

    public function exportPaymentsExcel(Request $request)
    {
        $this->authorize('view payment reports');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $locationId = $request->query('location_id');
        $source = $request->query('source');
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
        $totalCount = $orders->count();
        $pendingOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PENDING);
        $pendingAmount = (float) $pendingOrders->sum('final_amount');
        $pendingCount = $pendingOrders->count();

        $refundQuery = Order::with(['customer', 'payment', 'cancellationRequest'])
            ->where('order_type', 'sale')
            ->where('status', Order::STATUS_DECLINE)
            ->whereHas('cancellationRequest', function ($q) {
                $q->where('status', \App\Models\OrderCancellationRequest::STATUS_APPROVED)
                    ->where('refund_amount', '>', 0);
            });
        $applyCommonFilters($refundQuery);

        $refundedOrders = $refundQuery->latest()->get();
        $refundAmount = (float) $refundedOrders->sum(fn($order) => (float) $order->cancellationRequest->refund_amount);
        $refundCount = $refundedOrders->count();

        $allOrders = $orders->merge($refundedOrders)->sortByDesc('created_at')->values();

        if ($allOrders->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        // Sheet 1: Payments List (Main Data Table)
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Payments List');

        $headers1 = ['#', 'Order / Refund No', 'Customer Name', 'Source', 'Payment Method', 'Payment Status', 'Date', 'Amount'];
        $columns1 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers1 as $colIdx => $headerText) {
            $sheet1->setCellValue($columns1[$colIdx] . '1', $headerText);
        }

        $sheet1->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(1)->setRowHeight(26);

        $paymentStatusLabels = [1 => 'Pending', 2 => 'Paid', 3 => 'Partially Paid'];
        $rowIndex1 = 2;
        $sumAmount = 0;

        foreach ($allOrders as $idx => $order) {
            $isRefund = $order->status === Order::STATUS_DECLINE;
            $orderNo = $isRefund ? ($order->cancellationRequest->request_no ?? $order->order_no) : $order->order_no;
            $customerName = $order->customer->name ?? 'Walk-in';
            $sourceLabel = ucfirst($order->source ?? 'pos');
            $methodLabel = ucfirst($order->payment_method ?? '-');

            if ($isRefund) {
                $statusLabel = 'Refunded';
                $amt = (float) ($order->cancellationRequest->refund_amount ?? 0);
            } else {
                $statusLabel = $paymentStatusLabels[$order->payment_status] ?? 'Unknown';
                $amt = (float) $order->final_amount;
            }
            $sumAmount += $amt;

            $sheet1->setCellValue('A' . $rowIndex1, $idx + 1);
            $sheet1->setCellValue('B' . $rowIndex1, $orderNo);
            $sheet1->setCellValue('C' . $rowIndex1, $customerName);
            $sheet1->setCellValue('D' . $rowIndex1, $sourceLabel);
            $sheet1->setCellValue('E' . $rowIndex1, $methodLabel);
            $sheet1->setCellValue('F' . $rowIndex1, $statusLabel);
            $sheet1->setCellValue('G' . $rowIndex1, $order->created_at->format('d-m-Y'));
            $sheet1->setCellValue('H' . $rowIndex1, '₹' . number_format($amt, 2));

            $sheet1->getRowDimension($rowIndex1)->setRowHeight(20);
            $rowIndex1++;
        }

        // Totals Row
        $sheet1->setCellValue('A' . $rowIndex1, 'Total');
        $sheet1->setCellValue('H' . $rowIndex1, '₹' . number_format($sumAmount, 2));
        $sheet1->getStyle('A' . $rowIndex1 . ':H' . $rowIndex1)->getFont()->setBold(true);
        $sheet1->getStyle('A' . $rowIndex1 . ':H' . $rowIndex1)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet1->getRowDimension($rowIndex1)->setRowHeight(24);

        $sheet1->getStyle('A1:H' . $rowIndex1)->applyFromArray($borderStyle);
        $sheet1->getStyle('A1:A' . $rowIndex1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('G1:G' . $rowIndex1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('H1:H' . $rowIndex1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns1 as $colLetter) {
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 2: Summary
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Payment Summary');

        $sheet2->setCellValue('A1', 'Metric');
        $sheet2->setCellValue('B1', 'Count');
        $sheet2->setCellValue('C1', 'Amount');
        $sheet2->getStyle('A1:C1')->applyFromArray($headerStyle);
        $sheet2->getRowDimension(1)->setRowHeight(26);

        $summaryData = [
            ['Total Sales Payments', $totalCount, '₹' . number_format($totalAmount, 2)],
            ['Pending Payments', $pendingCount, '₹' . number_format($pendingAmount, 2)],
            ['Refunded Payments', $refundCount, '₹' . number_format($refundAmount, 2)],
        ];

        $r = 2;
        foreach ($summaryData as $row) {
            $sheet2->setCellValue('A' . $r, $row[0]);
            $sheet2->setCellValue('B' . $r, $row[1]);
            $sheet2->setCellValue('C' . $r, $row[2]);
            $sheet2->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet2->getStyle('A1:C' . ($r - 1))->applyFromArray($borderStyle);
        $sheet2->getStyle('B2:C' . ($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet2->getColumnDimension('A')->setAutoSize(true);
        $sheet2->getColumnDimension('B')->setAutoSize(true);
        $sheet2->getColumnDimension('C')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Reports', 'export', null, null, null, 'Payment report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'payment_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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

    public function exportDailyReportExcel(Request $request)
    {
        $this->authorize('view daily reports');

        [$date, $locationId, $locations, $isSuperAdmin] = $this->resolveDailyReportFilters($request);
        $data = $this->buildDailyReportData($date, $locationId);

        if (
            $data['salesRows']->isEmpty() &&
            $data['purchaseRows']->isEmpty() &&
            $data['expenseRows']->isEmpty() &&
            $data['purchaseBillRows']->isEmpty()
        ) {
            return redirect()->back()->with('error', 'No data found for the selected date. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        // Sheet 1: Daily Summary (Always present and populated)
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Daily Summary');

        $sheet1->setCellValue('A1', 'Metric');
        $sheet1->setCellValue('B1', 'Value');
        $sheet1->getStyle('A1:B1')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(1)->setRowHeight(26);

        $selectedLocation = $locationId ? Location::find($locationId) : null;
        $locName = $selectedLocation->name ?? 'All Locations';

        $summaryData = [
            ['Report Date', $date],
            ['Location', $locName],
            ['Total Sales Amount', '₹' . number_format($data['totalSales'], 2)],
            ['Pending Sales Amount', '₹' . number_format($data['totalPendingSales'], 2)],
            ['Sales Orders Count', $data['totalSalesCount']],
            ['Total Purchases Amount', '₹' . number_format($data['totalPurchases'], 2)],
            ['Pending Purchases Amount', '₹' . number_format($data['totalPendingPurchases'], 2)],
            ['Purchases Count', $data['totalPurchasesCount']],
            ['Total Expenses Amount', '₹' . number_format($data['totalExpenses'], 2)],
            ['Expenses Count', $data['totalExpensesCount']],
            ['Transfers Count', $data['totalTransfersCount']],
            ['Transfers Total Qty', $data['totalTransfersQty']],
        ];

        $r = 2;
        foreach ($summaryData as $row) {
            $sheet1->setCellValue('A' . $r, $row[0]);
            $sheet1->setCellValue('B' . $r, $row[1]);
            $sheet1->getRowDimension($r)->setRowHeight(20);
            $r++;
        }

        $sheet1->getStyle('A1:B' . ($r - 1))->applyFromArray($borderStyle);
        $sheet1->getStyle('B3:B12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet1->getColumnDimension('A')->setAutoSize(true);
        $sheet1->getColumnDimension('B')->setAutoSize(true);

        // Sheet 2: Sales
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Sales');

        $headers2 = ['#', 'Sale No', 'Customer', 'Location', 'Source', 'Status', 'Payment Status', 'Method', 'Amount'];
        $columns2 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers2 as $colIdx => $hText) {
            $sheet2->setCellValue($columns2[$colIdx] . '1', $hText);
        }
        $sheet2->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet2->getRowDimension(1)->setRowHeight(26);

        if ($data['salesRows']->isNotEmpty()) {
            $r2 = 2;
            $sumTotal = 0;
            foreach ($data['salesRows'] as $idx => $row) {
                $amt = (float) ($row['amount'] ?? 0);
                $sumTotal += $amt;

                $sheet2->setCellValue('A' . $r2, $idx + 1);
                $sheet2->setCellValue('B' . $r2, $row['sale_no'] ?? '-');
                $sheet2->setCellValue('C' . $r2, $row['customer'] ?? '-');
                $sheet2->setCellValue('D' . $r2, $row['location'] ?? '-');
                $sheet2->setCellValue('E' . $r2, $row['source'] ?? '-');
                $sheet2->setCellValue('F' . $r2, strip_tags($row['status'] ?? '-'));
                $sheet2->setCellValue('G' . $r2, strip_tags($row['payment_status'] ?? '-'));
                $sheet2->setCellValue('H' . $r2, $row['method'] ?? '-');
                $sheet2->setCellValue('I' . $r2, '₹' . number_format($amt, 2));
                $sheet2->getRowDimension($r2)->setRowHeight(20);
                $r2++;
            }

            $sheet2->setCellValue('A' . $r2, 'Total');
            $sheet2->setCellValue('I' . $r2, '₹' . number_format($sumTotal, 2));
            $sheet2->getStyle('A' . $r2 . ':I' . $r2)->getFont()->setBold(true);
            $sheet2->getStyle('A' . $r2 . ':I' . $r2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');

            $sheet2->getStyle('A1:I' . $r2)->applyFromArray($borderStyle);
            $sheet2->getStyle('I1:I' . $r2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            $sheet2->setCellValue('A2', 'No sales data available for the selected date.');
            $sheet2->mergeCells('A2:I2');
            $sheet2->getStyle('A1:I2')->applyFromArray($borderStyle);
        }
        foreach ($columns2 as $colLetter) {
            $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 3: Purchases
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Purchases');

        $headers3 = ['#', 'Purchase No', 'Supplier', 'Status', 'Payment Status', 'Total Amount'];
        $columns3 = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers3 as $colIdx => $hText) {
            $sheet3->setCellValue($columns3[$colIdx] . '1', $hText);
        }
        $sheet3->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet3->getRowDimension(1)->setRowHeight(26);

        if ($data['purchaseRows']->isNotEmpty()) {
            $r3 = 2;
            $sumPur = 0;
            foreach ($data['purchaseRows'] as $idx => $row) {
                $amt = (float) ($row['total_amount'] ?? 0);
                $sumPur += $amt;

                $sheet3->setCellValue('A' . $r3, $idx + 1);
                $sheet3->setCellValue('B' . $r3, $row['purchase_no'] ?? '-');
                $sheet3->setCellValue('C' . $r3, $row['supplier'] ?? '-');
                $sheet3->setCellValue('D' . $r3, strip_tags($row['status'] ?? '-'));
                $sheet3->setCellValue('E' . $r3, strip_tags($row['payment_status'] ?? '-'));
                $sheet3->setCellValue('F' . $r3, '₹' . number_format($amt, 2));
                $sheet3->getRowDimension($r3)->setRowHeight(20);
                $r3++;
            }

            $sheet3->setCellValue('A' . $r3, 'Total');
            $sheet3->setCellValue('F' . $r3, '₹' . number_format($sumPur, 2));
            $sheet3->getStyle('A' . $r3 . ':F' . $r3)->getFont()->setBold(true);
            $sheet3->getStyle('A' . $r3 . ':F' . $r3)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');

            $sheet3->getStyle('A1:F' . $r3)->applyFromArray($borderStyle);
            $sheet3->getStyle('F1:F' . $r3)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            $sheet3->setCellValue('A2', 'No purchase data available for the selected date.');
            $sheet3->mergeCells('A2:F2');
            $sheet3->getStyle('A1:F2')->applyFromArray($borderStyle);
        }
        foreach ($columns3 as $colLetter) {
            $sheet3->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 4: Expenses
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Expenses');

        $headers4 = ['#', 'Title', 'Category', 'Location', 'Expense Date', 'Payment Method', 'Created By', 'Amount'];
        $columns4 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers4 as $colIdx => $hText) {
            $sheet4->setCellValue($columns4[$colIdx] . '1', $hText);
        }
        $sheet4->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet4->getRowDimension(1)->setRowHeight(26);

        if ($data['expenseRows']->isNotEmpty()) {
            $r4 = 2;
            $sumExp = 0;
            foreach ($data['expenseRows'] as $idx => $row) {
                $amt = (float) ($row['amount'] ?? 0);
                $sumExp += $amt;

                $sheet4->setCellValue('A' . $r4, $idx + 1);
                $sheet4->setCellValue('B' . $r4, $row['title'] ?? '-');
                $sheet4->setCellValue('C' . $r4, $row['category'] ?? '-');
                $sheet4->setCellValue('D' . $r4, $row['location'] ?? '-');
                $sheet4->setCellValue('E' . $r4, $row['expense_date'] ?? '-');
                $sheet4->setCellValue('F' . $r4, $row['payment_method'] ?? '-');
                $sheet4->setCellValue('G' . $r4, $row['created_by'] ?? '-');
                $sheet4->setCellValue('H' . $r4, '₹' . number_format($amt, 2));
                $sheet4->getRowDimension($r4)->setRowHeight(20);
                $r4++;
            }

            $sheet4->setCellValue('A' . $r4, 'Total');
            $sheet4->setCellValue('H' . $r4, '₹' . number_format($sumExp, 2));
            $sheet4->getStyle('A' . $r4 . ':H' . $r4)->getFont()->setBold(true);
            $sheet4->getStyle('A' . $r4 . ':H' . $r4)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');

            $sheet4->getStyle('A1:H' . $r4)->applyFromArray($borderStyle);
            $sheet4->getStyle('H1:H' . $r4)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            $sheet4->setCellValue('A2', 'No expense data available for the selected date.');
            $sheet4->mergeCells('A2:H2');
            $sheet4->getStyle('A1:H2')->applyFromArray($borderStyle);
        }
        foreach ($columns4 as $colLetter) {
            $sheet4->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Reports', 'export', null, null, null, 'Daily report exported to Excel for ' . $date);

        $writer = new Xlsx($spreadsheet);
        $filename = 'daily_report_' . $date . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function resolveDailyReportFilters(Request $request): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);
        $date = (!$validator->fails() && $request->query('date'))
            ? $request->query('date')
            : now()->toDateString();

        if ($user->location_id && !$isSuperAdmin) {
            $locations = Location::where('id', $user->location_id)->get();
            $locationId = $user->location_id;
        } else {
            $locations = Location::where('status', 1)->orderBy('name')->get();
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

        $badge = fn(string $label, string $color) => '<span class="badge ' . $color . '">' . $label . '</span>';

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
                    $q
                        ->where('from_location_id', $locId)
                        ->orWhere('to_location_id', $locId);
                })
                ->get();

            $cnt = $locTransfers->count();
            $billIds = $locTransfers->pluck('id');
            $qty = PurchaseBillItem::whereIn('purchase_bill_id', $billIds)->sum('quantity');

            $transfersByLocation[$locId] = ['cnt' => (int) $cnt, 'qty' => (int) $qty];
        }

        $branchRows = $reportLocations->map(function ($location) use ($salesByLocation, $expensesByLocation, $purchasesByLocation, $transfersByLocation) {
            $sale = $salesByLocation->get($location->id);
            $expense = $expensesByLocation->get($location->id);
            $purchase = $purchasesByLocation->get($location->id);
            $transfer = $transfersByLocation[$location->id] ?? null;

            return [
                'location_name' => $location->name,
                'sales_amount' => (float) ($sale->total ?? 0),
                'sales_count' => (int) ($sale->cnt ?? 0),
                'purchase_amount' => (float) ($purchase->total ?? 0),
                'purchase_count' => (int) ($purchase->cnt ?? 0),
                'expense_amount' => (float) ($expense->total ?? 0),
                'expense_count' => (int) ($expense->cnt ?? 0),
                'transfer_count' => (int) ($transfer['cnt'] ?? 0),
                'transfer_qty' => (int) ($transfer['qty'] ?? 0),
            ];
        })->values();

        $totalSales = (float) $salesByLocation->sum('total');
        $totalSalesCount = (int) $salesByLocation->sum('cnt');

        $pendingSalesOrders = Order::where('order_type', 'sale')
            ->where('status', '!=', Order::STATUS_DECLINE)
            ->whereDate('created_at', $date)
            ->whereIn('location_id', $locationIds)
            ->get();
        $totalPendingSales = (float) $pendingSalesOrders->sum(function ($o) {
            if ((int) $o->payment_status === Order::PAYMENT_STATUS_PAID)
                return 0;
            return max(0, (float) $o->final_amount - ((float) $o->paid_cash_amount + (float) $o->paid_online_amount));
        });

        $totalPurchases = (float) $purchasesByLocation->sum('total');
        $totalPurchasesCount = (int) $purchasesByLocation->sum('cnt');

        $approvedPurchases = Purchase::where('status', Purchase::STATUS_APPROVE)
            ->whereDate('created_at', $date)
            ->when($locationId, function ($q) use ($locationId) {
                $q->whereHas('items.allocations', function ($sub) use ($locationId) {
                    $sub->where('location_id', $locationId);
                });
            })
            ->get();
        $totalPendingPurchases = (float) $approvedPurchases->sum(fn($i) => max(0, (float) $i->total_amount - (float) $i->paid_amount));
        $totalExpenses = (float) $expensesByLocation->sum('total');
        $totalExpensesCount = (int) $expensesByLocation->sum('cnt');

        $transferOverallQuery = PurchaseBill::where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('created_at', $date);

        if ($locationId) {
            $transferOverallQuery->where(function ($q) use ($locationId) {
                $q
                    ->where('from_location_id', $locationId)
                    ->orWhere('to_location_id', $locationId);
            });
        } else {
            $transferOverallQuery->where(function ($q) use ($locationIds) {
                $q
                    ->whereIn('from_location_id', $locationIds)
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
                    'index' => $index + 1,
                    'sale_no' => $order->order_no,
                    'customer' => $order->customer->name ?? 'Walk-in',
                    'location' => $order->location->name ?? '-',
                    'source' => $order->source ?? 'POS',
                    'amount' => (float) $order->final_amount,
                    'status' => $badge($orderStatusLabels[$order->status] ?? '-', $orderStatusColors[$order->status] ?? 'bg-label-secondary'),
                    'payment_status' => $badge($orderPaymentLabels[$order->payment_status] ?? '-', $orderPaymentColors[$order->payment_status] ?? 'bg-label-secondary'),
                    'method' => $order->payment_method === 'cod' ? 'COD' : ucwords(str_replace('_', ' ', (string) $order->payment_method)),
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
                    'index' => $index + 1,
                    'purchase_no' => $purchase->invoice_no,
                    'supplier' => $purchase->supplier->name ?? '-',
                    'total_amount' => (float) $purchase->total_amount,
                    'status' => $badge($purchaseStatusLabels[$purchase->status] ?? '-', $purchaseStatusColors[$purchase->status] ?? 'bg-label-secondary'),
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
                    'index' => $index + 1,
                    'title' => $expense->title,
                    'category' => $expense->category,
                    'amount' => (float) $expense->amount,
                    'payment_method' => $expense->payment_method,
                    'location' => $expense->location->name ?? '-',
                    'expense_date' => $expense->expense_date->format('d M Y'),
                    'created_by' => $expense->createdBy->name ?? '-',
                ];
            });

        $purchaseBillQuery = PurchaseBill::with(['fromLocation', 'toLocation', 'createdBy', 'items.product', 'items.variant'])
            ->where('status', PurchaseBill::STATUS_ACCEPTED)
            ->whereDate('created_at', $date);

        if ($locationId) {
            $purchaseBillQuery->where(function ($q) use ($locationId) {
                $q
                    ->where('from_location_id', $locationId)
                    ->orWhere('to_location_id', $locationId);
            });
        } else {
            $purchaseBillQuery->where(function ($q) use ($locationIds) {
                $q
                    ->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            });
        }

        $purchaseBillRows = $purchaseBillQuery
            ->withCount('items')
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
                    'index' => $index + 1,
                    'bill_no' => $transfer->transfer_no,
                    'source' => $transfer->fromLocation->name ?? '-',
                    'destination' => $transfer->toLocation->name ?? '-',
                    'total_quantity' => $transfer->items->sum('quantity'),
                    'amount' => (float) $amount,
                    'status' => $badge($transferStatusLabels[$transfer->status] ?? '-', $transferStatusColors[$transfer->status] ?? 'bg-label-secondary'),
                    'created_by' => $transfer->createdBy->name ?? '-',
                ];
            });

        return [
            'branchRows' => $branchRows,
            'salesRows' => $salesRows,
            'purchaseRows' => $purchaseRows,
            'expenseRows' => $expenseRows,
            'purchaseBillRows' => $purchaseBillRows,
            'totalSales' => $totalSales,
            'totalPendingSales' => $totalPendingSales,
            'totalSalesCount' => $totalSalesCount,
            'totalPurchases' => $totalPurchases,
            'totalPendingPurchases' => $totalPendingPurchases,
            'totalPurchasesCount' => $totalPurchasesCount,
            'totalExpenses' => $totalExpenses,
            'totalExpensesCount' => $totalExpensesCount,
            'totalTransfersCount' => $totalTransfersCount,
            'totalTransfersQty' => $totalTransfersQty,
        ];
    }

    // ============================================================
    // CUSTOMER REPORT
    // ============================================================

    private function buildCustomerReportData(Request $request): array
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $type = $request->query('type');
        $source = $request->query('source');
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
            ->when($isRestricted, fn($q) => $q->where('location_id', $user->location_id))
            ->orderBy('name')
            ->get();

        $obs = new \App\Observers\CustomerBalanceTransactionObserver();
        foreach ($creditCustomers as $c) {
            $obs->updateCustomerBalances($c->id);
        }

        $saleOrderNos = $transactions
            ->filter(fn($t) => $t->type === CustomerBalanceTransaction::TYPE_DEBIT)
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
        $totalDebit = (float) $transactions->where('type', CustomerBalanceTransaction::TYPE_DEBIT)->sum('amount');

        $customerIds = $transactions->pluck('customer_id')->unique();
        if ($customerId) {
            $customerIds = collect([(int) $customerId]);
        }
        $targetCustomers = Customer::whereIn('id', $customerIds)->get();
        if ($targetCustomers->isEmpty()) {
            $targetCustomers = Customer::where('is_credit_customer', true)
                ->when($isRestricted, fn($q) => $q->where('location_id', $user->location_id))
                ->get();
        }

        $cashBalance = (float) $targetCustomers->sum('cash_balance');
        $bankBalance = (float) $targetCustomers->sum('bank_balance');

        return [
            'transactions' => $transactions,
            'totalTransactions' => $transactions->count(),
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'cashBalance' => $cashBalance,
            'bankBalance' => $bankBalance,
            'type' => $type,
            'source' => $source,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customerId' => $customerId,
            'creditCustomers' => $creditCustomers,
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

    public function exportCustomerReportExcel(Request $request)
    {
        $this->authorize('view customer report');

        $data = $this->buildCustomerReportData($request);

        if ($data['transactions']->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected filters. Nothing to export.');
        }

        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1D2939'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAECF0']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D5DD'],
                ],
            ],
        ];

        // Sheet 1: Transactions
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Transactions');

        $headers1 = ['#', 'Date', 'Customer Name', 'Type', 'Source', 'Amount', 'Balance After', 'Notes', 'Created By'];
        $columns1 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers1 as $colIdx => $hText) {
            $sheet1->setCellValue($columns1[$colIdx] . '1', $hText);
        }
        $sheet1->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet1->getRowDimension(1)->setRowHeight(26);

        $rowIndex = 2;
        $totalCredit = 0;
        $totalDebit = 0;

        foreach ($data['transactions'] as $idx => $t) {
            $typeLabel = ucfirst($t->type);
            $sourceLabel = ucfirst($t->source);
            $amt = (float) $t->amount;
            $bal = (float) $t->balance_after;

            if ($t->type === CustomerBalanceTransaction::TYPE_CREDIT) {
                $totalCredit += $amt;
            } else {
                $totalDebit += $amt;
            }

            $sheet1->setCellValue('A' . $rowIndex, $idx + 1);
            $sheet1->setCellValue('B' . $rowIndex, $t->created_at->format('d-m-Y H:i'));
            $sheet1->setCellValue('C' . $rowIndex, $t->customer->name ?? '-');
            $sheet1->setCellValue('D' . $rowIndex, $typeLabel);
            $sheet1->setCellValue('E' . $rowIndex, $sourceLabel);
            $sheet1->setCellValue('F' . $rowIndex, '₹' . number_format($amt, 2));
            $sheet1->setCellValue('G' . $rowIndex, '₹' . number_format($bal, 2));
            $sheet1->setCellValue('H' . $rowIndex, $t->notes ?? '-');
            $sheet1->setCellValue('I' . $rowIndex, $t->createdBy->name ?? '-');

            $sheet1->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        // Totals Row
        $sheet1->setCellValue('A' . $rowIndex, 'Total Credit: ₹' . number_format($totalCredit, 2) . ' | Total Debit: ₹' . number_format($totalDebit, 2));
        $sheet1->mergeCells('A' . $rowIndex . ':I' . $rowIndex);
        $sheet1->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFont()->setBold(true);
        $sheet1->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');
        $sheet1->getRowDimension($rowIndex)->setRowHeight(24);

        $sheet1->getStyle('A1:I' . $rowIndex)->applyFromArray($borderStyle);
        $sheet1->getStyle('A1:A' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('B1:B' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('F1:G' . $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($columns1 as $colLetter) {
            $sheet1->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Sheet 2: Customer Balances
        if ($data['creditCustomers']->isNotEmpty()) {
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Customer Balances');

            $headers2 = ['#', 'Customer Name', 'Phone', 'Credit Limit', 'Current Credit Balance'];
            $columns2 = ['A', 'B', 'C', 'D', 'E'];

            foreach ($headers2 as $colIdx => $hText) {
                $sheet2->setCellValue($columns2[$colIdx] . '1', $hText);
            }
            $sheet2->getStyle('A1:E1')->applyFromArray($headerStyle);
            $sheet2->getRowDimension(1)->setRowHeight(26);

            $r2 = 2;
            $sumBal = 0;
            foreach ($data['creditCustomers'] as $idx => $c) {
                $bal = (float) $c->credit_balance;
                $sumBal += $bal;

                $sheet2->setCellValue('A' . $r2, $idx + 1);
                $sheet2->setCellValue('B' . $r2, $c->name);
                $sheet2->setCellValue('C' . $r2, $c->phone ?? '-');
                $sheet2->setCellValue('D' . $r2, '₹' . number_format((float) ($c->credit_limit ?? 0), 2));
                $sheet2->setCellValue('E' . $r2, '₹' . number_format($bal, 2));
                $sheet2->getRowDimension($r2)->setRowHeight(20);
                $r2++;
            }

            $sheet2->setCellValue('A' . $r2, 'Total Balance');
            $sheet2->setCellValue('E' . $r2, '₹' . number_format($sumBal, 2));
            $sheet2->getStyle('A' . $r2 . ':E' . $r2)->getFont()->setBold(true);
            $sheet2->getStyle('A' . $r2 . ':E' . $r2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAECF0');

            $sheet2->getStyle('A1:E' . $r2)->applyFromArray($borderStyle);
            $sheet2->getStyle('D1:E' . $r2)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            foreach ($columns2 as $colLetter) {
                $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        ActivityLogger::log('Reports', 'export', null, null, null, 'Customer credit report exported to Excel');

        $writer = new Xlsx($spreadsheet);
        $filename = 'customer_credit_report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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

        $transactionsQuery = $customer
            ->balanceTransactions()
            ->with('createdBy')
            ->orderByDesc('id');

        if (in_array($transactionType, [CustomerBalanceTransaction::TYPE_CREDIT, CustomerBalanceTransaction::TYPE_DEBIT], true)) {
            $transactionsQuery->where('type', $transactionType);
        }

        $transactions = $transactionsQuery->get();

        $totalCredit = $customer->balanceTransactions()->where('type', CustomerBalanceTransaction::TYPE_CREDIT)->sum('amount');
        $totalDebit = $customer->balanceTransactions()->where('type', CustomerBalanceTransaction::TYPE_DEBIT)->sum('amount');

        $saleOrderNos = $transactions
            ->filter(fn($t) => $t->type === CustomerBalanceTransaction::TYPE_DEBIT)
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
