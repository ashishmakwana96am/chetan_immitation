<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\Inventory;

class ShopCategoryController extends Controller
{
    public function index(Request $request, $slug = null)
    {
        if (!$request->ajax()) {
            if ($request->has('clear_search')) {
                session()->forget('shop_filters');
                return redirect()->route('shop-by-category');
            }

            $filters = [];
            $redirect = false;

            if ($slug && !($request->has('sub_category') && $request->input('sub_category'))) {
                $filters['category'] = $slug;
                $redirect = true;
            }

            if ($request->filled('sub_category')) {
                $filters['sub_category'] = $request->input('sub_category');
                $redirect = true;
            }

            if ($request->has('search')) {
                $searchVal = trim($request->get('search'));
                $filters['search'] = $searchVal;

                $searchValLower = strtolower($searchVal);
                $singular = \Illuminate\Support\Str::singular($searchValLower);
                $plural = \Illuminate\Support\Str::plural($searchValLower);
                $searchTerms = array_unique([$searchValLower, $singular, $plural]);

                $matchingCategory = Category::where('status', Category::STATUS_ACTIVE)
                    ->where(function($q) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $q->orWhereRaw('LOWER(name) = ?', [$term]);
                        }
                    })
                    ->first();

                if ($matchingCategory) {
                    $filters['category'] = $matchingCategory->slug;
                } else {
                    $matchingSubCategory = SubCategory::where('status', SubCategory::STATUS_ACTIVE)
                        ->where(function($q) use ($searchTerms) {
                            foreach ($searchTerms as $term) {
                                $q->orWhereRaw('LOWER(name) = ?', [$term]);
                            }
                        })
                        ->first();
                    if ($matchingSubCategory) {
                        $filters['sub_category'] = $matchingSubCategory->slug;
                    }
                }

                $redirect = true;
            }

            if ($redirect) {
                session(['shop_filters' => $filters]);
                return redirect()->route('shop-by-category', ['_f' => '1']);
            }

            if (!$request->has('_f')) {
                session()->forget('shop_filters');
            }
        }

        $data = $this->getFilteredProducts($slug);

        if ($data['products']->total() === 0 && !request()->ajax() && !$request->filled('search') && !$slug) {
            $hasAnyProducts = Product::forWebsite()->has('images')->exists();
            if (!$hasAnyProducts) {
                return redirect()->route('home');
            }
        }

        if (auth('customer')->check()) {
            auth('customer')->user()->load('wishlists');
        }

        if ($request->ajax()) {
            $gridHtml = view('website.partials.product-grid-items', ['products' => $data['products']])->render();

            return response()->json([
                'html' => $gridHtml,
                'pagination' => (string) $data['products']->links('vendor.pagination.tailwind'),
                'count' => $data['products']->total(),
                'price_range' => [
                    'min' => $data['catalogMinPrice'],
                    'max' => $data['catalogMaxPrice'],
                ],
            ]);
        }

        return view('website.shop-by-category', $data);
    }

    public function filter(Request $request)
    {
        try {
            $filterData = [
                'category'    => $request->input('category'),
                'sub_category'=> $request->input('sub_category'),
                'min_price'   => $request->input('min_price'),
                'max_price'   => $request->input('max_price'),
                'size'        => $request->input('size'),
                'sort'        => $request->input('sort'),
                'search'      => $request->input('search'),
            ];

            $hasActiveFilters = !empty($filterData['category'])
                || !empty($filterData['sub_category'])
                || !empty($filterData['min_price'])
                || !empty($filterData['max_price'])
                || !empty($filterData['size'])
                || !empty($filterData['sort'])
                || !empty($filterData['search']);

            if ($hasActiveFilters) {
                session()->put('shop_filters', $filterData);
            } else {
                session()->forget('shop_filters');
            }

            if (auth('customer')->check()) {
                auth('customer')->user()->load('wishlists');
            }

            $data = $this->getFilteredProducts();

            $gridHtml = view('website.partials.product-grid-items', ['products' => $data['products']])->render();

            return response()->json([
                'html'        => $gridHtml,
                'pagination'  => (string) $data['products']->links('vendor.pagination.tailwind'),
                'count'       => $data['products']->total(),
                'price_range' => [
                    'min' => $data['catalogMinPrice'],
                    'max' => $data['catalogMaxPrice'],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('ShopCategoryController@filter error: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Filter failed. Please try again.',
            ], 500);
        }
    }

    private function getFilteredProducts($slug = null)
    {
        $categories = Category::where('status', Category::STATUS_ACTIVE)
            ->whereHas('products', function ($q) {
                $q->forWebsite()->has('images');
            })
            ->with(['subCategories' => function ($q) {
                $q->where('status', SubCategory::STATUS_ACTIVE)
                  ->whereHas('products', function ($pq) {
                      $pq->forWebsite()->has('images');
                  })
                  ->orderBy('sort_order');
            }])
            ->withCount(['products' => function ($q) {
                $q->whereNull('products.deleted_at')
                  ->forWebsite()
                  ->has('images');
            }])
            ->orderBy('sort_order')
            ->get();

        $catalogQuery = $this->buildFilteredQuery($slug, false);

        $filteredProductIds = (clone $catalogQuery)->pluck('products.id');

        $priceRange = \DB::table('products')
            ->leftJoin(\DB::raw('(
                SELECT product_id,
                       MIN(sale_price) as variant_min,
                       MAX(sale_price) as variant_max
                FROM product_variants
                WHERE status IN (1, "active")
                GROUP BY product_id
            ) as pv'), 'pv.product_id', '=', 'products.id')
            ->whereIn('products.id', $filteredProductIds)
            ->selectRaw('
                MIN(COALESCE(pv.variant_min, products.sale_price)) as min_price,
                MAX(COALESCE(pv.variant_max, products.sale_price)) as max_price
            ')
            ->first();

        $catalogMinPrice = (int) floor((float) ($priceRange->min_price ?? 0));
        $catalogMaxPrice = (int) ceil((float) ($priceRange->max_price ?? 0));
        if ($catalogMinPrice === 0 && $catalogMaxPrice === 0) {
            $globalRange = \DB::table('products')
                ->leftJoin(\DB::raw('(
                    SELECT product_id,
                           MIN(sale_price) as variant_min,
                           MAX(sale_price) as variant_max
                    FROM product_variants
                    WHERE status = 1
                    GROUP BY product_id
                ) as pv'), 'pv.product_id', '=', 'products.id')
                ->where('products.status', Product::STATUS_ACTIVE)
                ->where('products.hide_from_website', 0)
                ->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                      ->from('product_images')
                      ->whereColumn('product_images.product_id', 'products.id');
                })
                ->selectRaw('
                    MIN(COALESCE(pv.variant_min, products.sale_price)) as min_price,
                    MAX(COALESCE(pv.variant_max, products.sale_price)) as max_price
                ')
                ->first();
            $catalogMinPrice = (int) floor((float) ($globalRange->min_price ?? 0));
            $catalogMaxPrice = (int) ceil((float) ($globalRange->max_price ?? 0));
        }
        if ($catalogMaxPrice < $catalogMinPrice) {
            $catalogMaxPrice = $catalogMinPrice;
        }

        $query = $this->buildFilteredQuery($slug, true)
            ->with('primaryImage', 'variants.attributeValue')
            ->withSum('inventories', 'quantity')
            ->withReviewStats();

        $filters = session('shop_filters', []);
        $sort = $filters['sort'] ?? 'default';

        $query->orderByRaw('
            (SELECT COUNT(*) FROM product_images WHERE product_images.product_id = products.id) = 0
        ');

        switch ($sort) {
            case 'price-low':
                $query->orderByRaw('
                    COALESCE(
                        (SELECT MIN(sale_price) FROM product_variants
                         WHERE product_variants.product_id = products.id
                           AND product_variants.status = 1),
                        products.sale_price
                    ) ASC
                ');
                break;
            case 'price-high':
                $query->orderByRaw('
                    COALESCE(
                        (SELECT MIN(sale_price) FROM product_variants
                         WHERE product_variants.product_id = products.id
                           AND product_variants.status = 1),
                        products.sale_price
                    ) DESC
                ');
                break;
            case 'newest':
                $query->latest('created_at');
                break;
            case 'popular':
                $query->orderByRaw('(SELECT COUNT(*) FROM product_reviews WHERE product_reviews.product_id = products.id) DESC')
                      ->orderByRaw('(SELECT COALESCE(AVG(rating), 0) FROM product_reviews WHERE product_reviews.product_id = products.id) DESC');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(9)->onEachSide(1);

        $sizes = $this->getSizes();
        $hasPriceFilter = !empty($filters['min_price']) || !empty($filters['max_price']);
        $selectedMinPrice = !empty($filters['min_price'])
            ? (int) $filters['min_price']
            : $catalogMinPrice;
        $selectedMaxPrice = !empty($filters['max_price'])
            ? (int) $filters['max_price']
            : $catalogMaxPrice;

        return compact(
            'categories',
            'products',
            'sizes',
            'catalogMinPrice',
            'catalogMaxPrice',
            'selectedMinPrice',
            'selectedMaxPrice',
            'hasPriceFilter'
        );
    }

    private function buildFilteredQuery($slug = null, bool $applyPriceFilter = true)
    {
        $query = Product::forWebsite()->hasImages();

        $filters = session('shop_filters', []);

        $categorySlugs = [];
        if (!empty($filters['category'])) {
            $categorySlugs = array_values(array_filter(array_map('trim', explode(',', $filters['category']))));
        } elseif ($slug) {
            $categorySlugs = [$slug];
        }

        $subSlugs = [];
        if (!empty($filters['sub_category'])) {
            $subSlugs = array_values(array_filter(array_map('trim', explode(',', $filters['sub_category']))));
        }

        $catIds = collect();
        if (!empty($categorySlugs)) {
            $catIds = Category::whereIn('slug', $categorySlugs)
                ->where('status', Category::STATUS_ACTIVE)
                ->pluck('id');
        }

        $subIds = collect();
        if (!empty($subSlugs)) {
            $subIds = SubCategory::whereIn('slug', $subSlugs)
                ->where('status', SubCategory::STATUS_ACTIVE)
                ->pluck('id');
        }

        if ($catIds->isNotEmpty() || $subIds->isNotEmpty()) {
            $this->applyCategorySubCategoryFilters($query, $catIds, $subIds);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            if (!$this->isMatchedFilterActive($search, $filters)) {
                $searchTerm = '%' . $search . '%';
                $query->where(function ($q) use ($searchTerm, $search) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('sale_price', is_numeric($search) ? (float) $search : -1)
                      ->orWhereHas('category', function ($cq) use ($searchTerm) {
                          $cq->where('name', 'like', $searchTerm);
                      })
                      ->orWhereHas('subCategory', function ($sq) use ($searchTerm) {
                          $sq->where('name', 'like', $searchTerm);
                      })
                      ->orWhereHas('variants.attributeValue', function ($vq) use ($searchTerm) {
                          $vq->where('value', 'like', $searchTerm);
                      });
                });
            }
        }

        if ($applyPriceFilter) {
            $minPrice = !empty($filters['min_price']) ? (float) $filters['min_price'] : null;
            $maxPrice = !empty($filters['max_price']) ? (float) $filters['max_price'] : null;

            if ($minPrice !== null || $maxPrice !== null) {
                $query->whereRaw('
                    COALESCE(
                        (SELECT MIN(sale_price) FROM product_variants
                         WHERE product_variants.product_id = products.id
                           AND product_variants.status = 1),
                        products.sale_price
                    ) >= ?
                ', [$minPrice ?? 0]);

                if ($maxPrice !== null) {
                    $query->whereRaw('
                        COALESCE(
                            (SELECT MIN(sale_price) FROM product_variants
                             WHERE product_variants.product_id = products.id
                               AND product_variants.status = 1),
                            products.sale_price
                        ) <= ?
                    ', [$maxPrice]);
                }
            }
        }

        if (!empty($filters['size'])) {
            $sizeAttribute = Attribute::where('slug', 'size')->first();
            if ($sizeAttribute) {
                $sizeAttribute->load('values');
                $sizeValues = explode(',', $filters['size']);
                $sizeValueIds = $sizeAttribute->values->whereIn('value', $sizeValues)->pluck('id');
                if ($sizeValueIds->isNotEmpty()) {
                    $query->whereHas('variants', function ($q) use ($sizeValueIds) {
                        $q->whereIn('attribute_value_id', $sizeValueIds);
                    });
                }
            }
        }

        return $query;
    }

    /**
     * Apply category / sub-category filters.
     *
     * Rules:
     * - Only category selected (no subs): show all products of that category.
     * - Subcategory(ies) selected:
     *     - If selected sub has products → show those sub's products only.
     *     - If selected sub has NO products → show nothing (no fallback).
     * - Multiple subs selected → OR all the above conditions together.
     * - If both full categories AND subs are selected:
     *     - Full-category entries show ALL products of that category.
     *     - Sub entries apply per-sub logic above.
     *     - Combined with OR.
     */
    private function applyCategorySubCategoryFilters($query, $catIds, $subIds)
    {
        $subCategories = $subIds->isNotEmpty()
            ? SubCategory::whereIn('id', $subIds)->get(['id', 'category_id'])
            : collect();

        $subProductCounts = $subIds->isNotEmpty()
            ? Product::forWebsite()
                ->whereIn('sub_category_id', $subIds)
                ->selectRaw('sub_category_id, COUNT(*) as total')
                ->groupBy('sub_category_id')
                ->pluck('total', 'sub_category_id')
            : collect();

        $query->where(function ($q) use ($catIds, $subCategories, $subProductCounts) {
            $applied = false;

            if ($catIds->isNotEmpty()) {
                $q->whereIn('category_id', $catIds);
                $applied = true;
            }

            foreach ($subCategories as $subCategory) {
                $branch = function ($subQ) use ($subCategory, $subProductCounts) {
                    $subCount = (int) ($subProductCounts[$subCategory->id] ?? 0);

                    if ($subCount > 0) {
                        $subQ->where('sub_category_id', $subCategory->id);
                        return;
                    }

                    $subQ->whereRaw('1 = 0');
                };

                if ($applied) {
                    $q->orWhere($branch);
                } else {
                    $q->where($branch);
                    $applied = true;
                }
            }
        });
    }

    private function getSizes()
    {
        $sizeAttribute = Attribute::where('slug', 'size')->first();
        if (!$sizeAttribute) {
            return collect();
        }

        $sizeAttribute->load('values');

        return $sizeAttribute->values;
    }

    private function isMatchedFilterActive($searchVal, $filters)
    {
        if (empty($searchVal)) {
            return false;
        }

        $searchValLower = strtolower(trim($searchVal));
        $singular = \Illuminate\Support\Str::singular($searchValLower);
        $plural = \Illuminate\Support\Str::plural($searchValLower);
        $searchTerms = array_unique([$searchValLower, $singular, $plural]);

        // Find if there is a matching Category
        $matchingCategory = Category::where('status', Category::STATUS_ACTIVE)
            ->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhereRaw('LOWER(name) = ?', [$term]);
                }
            })
            ->first();

        if ($matchingCategory) {
            $selectedCats = !empty($filters['category']) ? explode(',', $filters['category']) : [];
            if (in_array($matchingCategory->slug, $selectedCats)) {
                return true;
            }
        }

        // Find if there is a matching SubCategory
        $matchingSubCategory = SubCategory::where('status', SubCategory::STATUS_ACTIVE)
            ->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhereRaw('LOWER(name) = ?', [$term]);
                }
            })
            ->first();

        if ($matchingSubCategory) {
            $selectedSubs = !empty($filters['sub_category']) ? explode(',', $filters['sub_category']) : [];
            if (in_array($matchingSubCategory->slug, $selectedSubs)) {
                return true;
            }
        }

        return false;
    }
}
