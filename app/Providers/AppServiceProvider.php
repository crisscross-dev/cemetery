<?php

namespace App\Providers;

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
        // Add static asset caching headers
        if (!app()->isLocal()) {
            // Cache static assets for 1 year
            header('Cache-Control: public, max-age=31536000, immutable', false);
        }
    }
}
