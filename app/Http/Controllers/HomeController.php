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
            ->orderBy('sort_order')
            ->get();

        $lovedProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->with('primaryImage')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $latestProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->with('primaryImage')
            ->latest()
            ->limit(4)
            ->get();

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
        return view('website.login');
    }

    public function forgotPassword()
    {
        return view('website.forgot-password');
    }

    public function otpVerification()
    {
        return view('website.otp-verification');
    }

    public function register()
    {
        return view('website.register');
    }

    public function shopByCategory($slug)
    {
        $category = Category::where('slug', $slug)
            ->where('status', Category::STATUS_ACTIVE)
            ->firstOrFail();

        $products = Product::where('category_id', $category->id)
            ->where('status', Product::STATUS_ACTIVE)
            ->with('primaryImage')
            ->paginate(12);

        $categories = Category::where('status', Category::STATUS_ACTIVE)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return view('website.shop-by-category', compact('category', 'products', 'categories'));
    }
}
