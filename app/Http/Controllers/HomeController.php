<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Banner;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Banner::where('status', Banner::STATUS_ACTIVE)->latest()->get();

        $categories = Category::where('status', Category::STATUS_ACTIVE)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->whereHas('products', function ($q) {
                $q->forWebsite()->has('images');
            })
            ->orderBy('sort_order')
            ->get();

        $lovedProducts = Product::forWebsite()
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

        $latestProducts = Product::forWebsite()
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

        $instagramPosts = $this->getInstagramPosts();
        $instagramProfileUrl = Setting::getValue('instagram_profile_url', 'https://www.instagram.com/chetan_imitation?igsh=Zm9lNHNoaTQ3c2t4');

        return view('website.home', compact('banners', 'categories', 'lovedProducts', 'latestProducts', 'instagramPosts', 'instagramProfileUrl'));
    }

    private function getInstagramPosts()
    {
        return Setting::getInstagramPosts();
    }

    private function getDefaultInstagramPosts($profileUrl)
    {
        return Setting::getDefaultInstagramPosts($profileUrl);
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
            ->forWebsite()
            ->with('primaryImage', 'images', 'variants.attributeValue.attribute', 'category', 'subCategory')
            ->withSum('inventories', 'quantity')
            ->withReviewStats()
            ->first();

        if (!$product) {
            return redirect()->route('shop-by-category');
        }

        $topReviews = $product->reviews()
            ->with('customer', 'images')
            ->latest()
            ->limit(2)
            ->get();

        $relatedProducts = Product::forWebsite()
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
