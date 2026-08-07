<?php

namespace App\Providers;

use App\Models\BusinessCategory;
use App\Support\BusinessCategoryContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $categories = Schema::hasTable('business_categories')
                ? BusinessCategory::orderBy('code')->orderBy('name')->get()
                : collect();
            $view->with('globalBusinessCategories', $categories)
                ->with('activeBusinessCategory', BusinessCategoryContext::selected());
        });
    }
}
