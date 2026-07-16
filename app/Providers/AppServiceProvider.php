<?php

namespace App\Providers;

use App\Models\CostingData;
use App\Models\DocumentRevision;
use App\Observers\CostingDataObserver;
use App\Observers\DocumentRevisionObserver;
use Illuminate\Support\ServiceProvider;

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
        DocumentRevision::observe(DocumentRevisionObserver::class);
        CostingData::observe(CostingDataObserver::class);
    }
}
