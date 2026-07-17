<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\SubCategory;
use App\Models\Product;
use App\Observers\OrderObserver;
use App\Observers\PurchaseObserver;
use App\Observers\ExpenseObserver;
use Illuminate\Cookie\Middleware\EncryptCookies;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        EncryptCookies::except('guest_cart');

        Order::observe(OrderObserver::class);
        Purchase::observe(PurchaseObserver::class);
        Expense::observe(ExpenseObserver::class);

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        view()->composer('layouts.website', function ($view) {
            $categories = Category::where('status', Category::STATUS_ACTIVE)
                ->whereHas('products', function ($q) {
                    $q->where('status', Product::STATUS_ACTIVE)->has('images');
                })
                ->with(['subCategories' => function ($q) {
                    $q->where('status', SubCategory::STATUS_ACTIVE)
                      ->whereHas('products', function ($pq) {
                          $pq->where('status', Product::STATUS_ACTIVE)->has('images');
                      })
                      ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
            $view->with('sharedCategories', $categories);
        });
    }
}
