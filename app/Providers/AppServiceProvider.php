<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Models\SubCategory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->type === 'super-admin') {
                return true;
            }
        });

        view()->composer('layouts.website', function ($view) {
            $categories = Category::where('status', Category::STATUS_ACTIVE)
                ->with(['subCategories' => function ($q) {
                    $q->where('status', SubCategory::STATUS_ACTIVE)
                      ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
            $view->with('sharedCategories', $categories);
        });
    }
}
