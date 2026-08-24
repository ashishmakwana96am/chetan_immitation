<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SubCategory;
use App\Observers\CustomerBalanceTransactionObserver;
use App\Observers\ExpenseObserver;
use App\Observers\OrderObserver;
use App\Observers\PurchaseObserver;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        CustomerBalanceTransaction::observe(CustomerBalanceTransactionObserver::class);

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
                    $q
                        ->where('status', SubCategory::STATUS_ACTIVE)
                        ->whereHas('products', function ($pq) {
                            $pq->where('status', Product::STATUS_ACTIVE)->has('images');
                        })
                        ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
            $collections = Collection::whereIn('status', [Collection::STATUS_ACTIVE, 1, '1', 'active'])
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->whereHas('products', function ($q) {
                    $q->where('status', Product::STATUS_ACTIVE)
                      ->where('hide_from_website', false)
                      ->has('images');
                })
                ->orderBy('name')
                ->get();
            $view->with('sharedCategories', $categories);
            $view->with('sharedCollections', $collections);
            $view->with('sharedInstagramPosts', \App\Models\Setting::getInstagramPosts());
            $view->with('sharedInstagramProfileUrl', \App\Models\Setting::getInstagramProfileUrl());
        });
    }
}
