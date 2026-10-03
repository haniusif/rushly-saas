<?php

namespace App\Commerce;

use App\Commerce\Factory\CommerceProviderFactory;
use App\Commerce\Logging\ApiLogger;
use App\Commerce\Repositories\CommerceConnectionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Phase 1 scaffold provider. Registers the infrastructure singletons
 * (ApiLogger, ProviderFactory, ConnectionRepository) and merges
 * config/commerce.php. No routes, no event subscriptions, no commands —
 * those land in later phases behind config('features.commerce_layer').
 *
 * Registering unconditionally is safe: until a concrete provider is
 * registered in config/commerce.php, the factory has nothing to resolve.
 * The feature flag gates user-visible behavior, not module loading.
 */
class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/commerce.php', 'commerce');

        $this->app->singleton(ApiLogger::class);
        $this->app->singleton(CommerceProviderFactory::class);
        $this->app->singleton(CommerceConnectionRepository::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/commerce.php' => config_path('commerce.php'),
        ], 'commerce-config');

        // Event subscriptions, route registration, scheduled commands all
        // land in later phases, gated by config('features.commerce_layer').
    }
}
