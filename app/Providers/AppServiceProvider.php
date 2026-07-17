<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Cookie\Middleware\EncryptCookies::except('guest_cart');

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        view()->composer('layouts.website', function ($view) {
            $categories = Category::where('status', Category::STATUS_ACTIVE)
                ->whereHas('products', function ($q) {
                    $q->where('status', Product::STATUS_ACTIVE);
                })
                ->with(['subCategories' => function ($q) {
                    $q->where('status', SubCategory::STATUS_ACTIVE)
                      ->whereHas('products', function ($pq) {
                          $pq->where('status', Product::STATUS_ACTIVE);
                      })
                      ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
            $view->with('sharedCategories', $categories);
        });
    }
}
