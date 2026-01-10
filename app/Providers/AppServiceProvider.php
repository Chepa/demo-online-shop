<?php

namespace App\Providers;

use App\Contracts\ProductCacheInterface;
use App\Services\Catalog\ProductRedisService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductCacheInterface::class, ProductRedisService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
