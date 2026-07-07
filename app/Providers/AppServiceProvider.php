<?php

namespace App\Providers;

use App\Services\Waqty\WaqtyApiClient;
use App\Support\CurrentProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WaqtyApiClient::class, fn () => new WaqtyApiClient(
            baseUrl: (string) config('waqty.base_url'),
            timeout: (int) config('waqty.timeout', 15),
        ));

        $this->app->scoped(CurrentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('provider', $this->app->make(CurrentProvider::class));
    }
}
