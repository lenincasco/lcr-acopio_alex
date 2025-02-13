<?php

namespace App\Providers;

use App\Models\Liquidacion;
use App\Models\Prestamo;
use App\Observers\LiquidacionObserver;
use App\Observers\PrestamoObserver;
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
        Prestamo::observe(PrestamoObserver::class);
    }
}
