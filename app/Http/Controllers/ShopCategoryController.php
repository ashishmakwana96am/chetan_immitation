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
    public function index($slug = null)
    {
        $categories = Category::where('status', Category::STATUS_ACTIVE)
            ->with(['subCategories' => function ($q) {
                $q->where('status', SubCategory::STATUS_ACTIVE)->orderBy('sort_order');
            }])
            ->withCount('products')
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
            ->withSum('inventories', 'quantity');

        switch (request('sort')) {
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
                $query->inRandomOrder();
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(9)->onEachSide(1)->withQueryString();

        if (auth('customer')->check()) {
            auth('customer')->user()->load('wishlists');
        }

        $sizes = $this->getSizes();
        $hasPriceFilter = request()->has('min_price') || request()->has('max_price');
        $selectedMinPrice = request()->has('min_price')
            ? (int) request('min_price')
            : $catalogMinPrice;
        $selectedMaxPrice = request()->has('max_price')
            ? (int) request('max_price')
            : $catalogMaxPrice;

        if (request()->ajax()) {
            $gridHtml = view('website.partials.product-grid-items', compact('products'))->render();

            return response()->json([
                'html' => $gridHtml,
                'pagination' => (string) $products->links('vendor.pagination.tailwind'),
                'count' => $products->total(),
                'price_range' => [
                    'min' => $catalogMinPrice,
                    'max' => $catalogMaxPrice,
                ],
            ]);
        }

        return view('website.shop-by-category', compact(
            'categories',
            'products',
            'sizes',
            'catalogMinPrice',
            'catalogMaxPrice',
            'selectedMinPrice',
            'selectedMaxPrice',
            'hasPriceFilter'
        ));
    }

    private function buildFilteredQuery($slug = null, bool $applyPriceFilter = true)
    {
        $query = Product::where('status', Product::STATUS_ACTIVE);

        $categorySlugs = [];
        if (request('category')) {
            $categorySlugs = explode(',', request('category'));
        } elseif ($slug) {
            $categorySlugs = [$slug];
        }

        if (!empty($categorySlugs)) {
            $catIds = Category::whereIn('slug', $categorySlugs)
                ->where('status', Category::STATUS_ACTIVE)
                ->pluck('id');
            if ($catIds->isNotEmpty()) {
                $query->whereIn('category_id', $catIds);
            } elseif ($slug) {
                abort(404);
            }
        }

        if (request('sub_category')) {
            $subSlugs = explode(',', request('sub_category'));
            $subIds = SubCategory::whereIn('slug', $subSlugs)->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->where(function ($q) use ($subIds) {
                    $q->whereIn('sub_category_id', $subIds)
                      ->orWhereNull('sub_category_id');
                });
            }
        }

        if ($search = request('search')) {
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

        if ($applyPriceFilter) {
            $minPrice = request()->filled('min_price') ? (float) request('min_price') : null;
            $maxPrice = request()->filled('max_price') ? (float) request('max_price') : null;

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

        $sizeAttribute = Attribute::where('slug', 'size')->first();
        if ($sizeAttribute) {
            $sizeAttribute->load('values');

            if (request('size')) {
                $sizeValues = explode(',', request('size'));
                $sizeValueIds = $sizeAttribute->values->whereIn('value', $sizeValues)->pluck('id');
                if ($sizeValueIds->isNotEmpty()) {
                    $query->whereHas('variants', function ($q) use ($sizeValueIds) {
                        $q->whereIn('attribute_value_id', $sizeValueIds);
                    });
                }
            }
        }

        if (request('stock') === 'hide') {
            $inStockIds = Inventory::select('product_id')
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty')
                ->groupBy('product_id')
                ->having('total_qty', '>', 0)
                ->pluck('product_id');
            $query->whereIn('id', $inStockIds);
        }

        return $query;
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
}
