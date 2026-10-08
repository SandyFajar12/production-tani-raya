<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Blade::if('permission', function (string $slug) {
            return auth()->check() && auth()->user()->hasPermission($slug);
        });

        // Directive bawaan Laravel 9 (proyek ini Laravel 8)
        Blade::directive('selected', function ($expression) {
            return "<?php if ({$expression}) echo 'selected'; ?>";
        });

        Blade::directive('checked', function ($expression) {
            return "<?php if ({$expression}) echo 'checked'; ?>";
        });
    }
}