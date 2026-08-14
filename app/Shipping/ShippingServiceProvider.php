<?php

namespace App\Shipping;

use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Logging\ApiLogger;
use App\Shipping\Repositories\ShipmentRepository;
use App\Shipping\Repositories\ShippingConnectionRepository;
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/shipping.php', 'shipping');

        $this->app->singleton(ApiLogger::class);
        $this->app->singleton(ShippingProviderFactory::class);
        $this->app->singleton(ShippingConnectionRepository::class);
        $this->app->singleton(ShipmentRepository::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/shipping.php' => config_path('shipping.php'),
        ], 'shipping-config');

        // Event subscriptions live in EventServiceProvider so the wiring is
        // visible in one place (consistent with how SallaObserver et al are
        // registered). We don't auto-register listeners here.
    }
}
