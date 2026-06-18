<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $priceRange = (clone $catalogQuery)
            ->selectRaw('COALESCE(MIN(sale_price), 0) as min_price, COALESCE(MAX(sale_price), 0) as max_price')
            ->first();

        $catalogMinPrice = (int) floor((float) ($priceRange->min_price ?? 0));
        $catalogMaxPrice = (int) ceil((float) ($priceRange->max_price ?? 0));
        if ($catalogMaxPrice < $catalogMinPrice) {
            $catalogMaxPrice = $catalogMinPrice;
        }

        $query = $this->buildFilteredQuery($slug, true)
            ->with('primaryImage', 'variants.attributeValue')
            ->withSum('inventories', 'quantity');

        switch (request('sort')) {
            case 'price-low':
                $query->orderBy('sale_price', 'asc');
                break;
            case 'price-high':
                $query->orderBy('sale_price', 'desc');
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
                $query->whereIn('sub_category_id', $subIds);
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
            if (request()->filled('min_price')) {
                $query->where('sale_price', '>=', (float) request('min_price'));
            }
            if (request()->filled('max_price')) {
                $query->where('sale_price', '<=', (float) request('max_price'));
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
