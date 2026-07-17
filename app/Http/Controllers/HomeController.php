<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', Category::STATUS_ACTIVE)
            ->whereHas('products', function ($q) {
                $q->where('status', Product::STATUS_ACTIVE);
            })
            ->orderBy('sort_order')
            ->get();

        $lovedProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->hasImages()
            ->whereHas('inventories', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->with('primaryImage', 'variants.attributeValue')
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->having('inventories_sum_quantity', '>', 0)
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $latestProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->hasImages()
            ->whereHas('inventories', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->with('primaryImage', 'variants.attributeValue')
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->having('inventories_sum_quantity', '>', 0)
            ->latest()
            ->limit(4)
            ->get();

        if (auth('customer')->check()) {
            auth('customer')->user()->load('wishlists');
        }

        return view('website.home', compact('categories', 'lovedProducts', 'latestProducts'));
    }

    public function about()
    {
        return view('website.about');
    }

    public function contact()
    {
        return view('website.contact');
    }

    public function terms()
    {
        $setting = Setting::where('key', 'terms_conditions')->first();
        $content = $setting ? $setting->value : '';
        $lastUpdated = $setting ? $setting->updated_at : null;
        return view('website.terms', compact('content', 'lastUpdated'));
    }

    public function privacy()
    {
        $setting = Setting::where('key', 'privacy_policy')->first();
        $content = $setting ? $setting->value : '';
        $lastUpdated = $setting ? $setting->updated_at : null;
        return view('website.privacy', compact('content', 'lastUpdated'));
    }

    public function deliveryReturns()
    {
        $setting = Setting::where('key', 'delivery_returns')->first();
        $content = $setting ? $setting->value : '';
        $lastUpdated = $setting ? $setting->updated_at : null;
        return view('website.delivery-returns', compact('content', 'lastUpdated'));
    }

    public function refundCancellation()
    {
        $setting = Setting::where('key', 'refund_cancellation')->first();
        $content = $setting ? $setting->value : '';
        $lastUpdated = $setting ? $setting->updated_at : null;
        return view('website.refund-cancellation', compact('content', 'lastUpdated'));
    }

    public function login()
    {
        if (request()->query('intended')) {
            session()->put('url.intended', request()->query('intended'));
        }

        return view('website.login');
    }

    public function forgotPassword()
    {
        return view('website.forgot-password');
    }

    public function otpVerification()
    {
        $email = session('otp_pending_email');

        if (!$email) {
            return redirect()->route('forgot-password');
        }

        return view('website.otp-verification', compact('email'));
    }

    public function register()
    {
        return view('website.register');
    }

    public function detail($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', Product::STATUS_ACTIVE)
            ->with('primaryImage', 'images', 'variants.attributeValue.attribute', 'category', 'subCategory')
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->firstOrFail();

        $topReviews = $product->reviews()
            ->with('customer', 'images')
            ->latest()
            ->limit(2)
            ->get();

        $relatedProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->hasImages()
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                if ($product->category_id) {
                    $q->where('category_id', $product->category_id);
                }
            })
            ->with('primaryImage', 'variants.attributeValue')
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->inRandomOrder()
            ->limit(4)
            ->get();
        $wishlistItem = null;
        if (auth('customer')->check()) {
            auth('customer')->user()->load('wishlists');
            
            $variantId = request('variant');
            if ($variantId) {
                $wishlistItem = \App\Models\Wishlist::where('customer_id', auth('customer')->id())
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variantId)
                    ->first();
            }
            if (!$wishlistItem) {
                $wishlistItem = \App\Models\Wishlist::where('customer_id', auth('customer')->id())
                    ->where('product_id', $product->id)
                    ->first();
            }
        }

        return view('website.detail', compact('product', 'relatedProducts', 'wishlistItem', 'topReviews'));
    }
}
