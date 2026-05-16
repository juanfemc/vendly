<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreBanner;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StoreBannerPolicy;
use App\Policies\StorePolicy;
use App\Support\WindowsFriendlyFilesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('files', fn () => new WindowsFriendlyFilesystem);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            $request = request();

            URL::forceRootUrl($request->getSchemeAndHttpHost());

            if ($request->isSecure()) {
                URL::forceScheme('https');
            }
        }

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Store::class, StorePolicy::class);
        Gate::policy(StoreBanner::class, StoreBannerPolicy::class);
    }
}
