<?php

namespace App\Shipping\Factory;

use App\Shipping\Contracts\ShippingProviderInterface;
use App\Shipping\Models\ShippingConnection;
use InvalidArgumentException;

/**
 * Resolve a provider implementation by its registered code.
 *
 * Lookup is via config('shipping.providers.<code>.class'). The factory itself
 * is a thin pure dispatcher — no provider-specific knowledge here. Adding a
 * provider = adding a row to config/shipping.php + writing the provider class.
 *
 * Both `make($code)` and `forConnection($conn)` are exposed so callers can
 * resolve from either a string or a hydrated connection row.
 */
class ShippingProviderFactory
{
    /** @var array<string, ShippingProviderInterface> */
    private array $instances = [];

    public function __construct(
        private readonly \Illuminate\Contracts\Container\Container $container,
    ) {}

    public function make(string $code): ShippingProviderInterface
    {
        if (isset($this->instances[$code])) {
            return $this->instances[$code];
        }

        $class = config('shipping.providers.' . $code . '.class');
        if (! $class || ! class_exists($class)) {
            throw new InvalidArgumentException("Unknown shipping provider: {$code}");
        }

        $instance = $this->container->make($class);
        if (! $instance instanceof ShippingProviderInterface) {
            throw new InvalidArgumentException(
                "Class {$class} for provider {$code} does not implement ShippingProviderInterface."
            );
        }

        return $this->instances[$code] = $instance;
    }

    public function forConnection(ShippingConnection $connection): ShippingProviderInterface
    {
        return $this->make($connection->provider->code);
    }

    /** Codes registered in config. Used by the admin UI's "Add integration" dropdown. */
    public function codes(): array
    {
        return array_keys((array) config('shipping.providers', []));
    }
}
