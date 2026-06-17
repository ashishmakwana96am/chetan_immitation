<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;

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

        $query = Product::where('status', Product::STATUS_ACTIVE)
            ->with('primaryImage');

        if ($slug) {
            $category = $categories->firstWhere('slug', $slug);
            if ($category) {
                $query->where('category_id', $category->id);
            } else {
                $catBySlug = Category::where('slug', $slug)->where('status', Category::STATUS_ACTIVE)->first();
                if ($catBySlug) {
                    $query->where('category_id', $catBySlug->id);
                } else {
                    abort(404);
                }
            }
        }

        if (request('sub_category')) {
            $subSlugs = explode(',', request('sub_category'));
            $subIds = SubCategory::whereIn('slug', $subSlugs)->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->whereIn('sub_category_id', $subIds);
            }
        }

        if (request('min_price')) {
            $query->where('sale_price', '>=', (float) request('min_price'));
        }
        if (request('max_price')) {
            $query->where('sale_price', '<=', (float) request('max_price'));
        }

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
                $query->orderBy('sort_order')->latest();
        }

        $products = $query->paginate(12)->withQueryString();

        if (request()->ajax()) {
            $gridHtml = view('website.partials.product-grid-items', compact('products'))->render();
            return response()->json([
                'html' => $gridHtml,
                'pagination' => (string) $products->links('vendor.pagination.tailwind'),
                'count' => $products->total(),
            ]);
        }

        return view('website.shop-by-category', compact('categories', 'products'));
    }
}
