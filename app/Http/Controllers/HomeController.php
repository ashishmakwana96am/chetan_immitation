<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

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
}
