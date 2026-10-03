<?php

namespace App\Commerce\Factory;

use App\Commerce\Contracts\CommerceProviderInterface;
use App\Commerce\Models\CommerceConnection;
use InvalidArgumentException;

/**
 * Resolve a commerce provider implementation by its registered code.
 *
 * Lookup is via config('commerce.providers.<code>.class'). The factory
 * itself is a thin pure dispatcher — no provider-specific knowledge here.
 * Adding a provider = adding a row to config/commerce.php + writing the
 * provider class.
 *
 * Both `make($code)` and `forConnection($conn)` are exposed so callers can
 * resolve from either a string or a hydrated connection row. Mirrors
 * App\Shipping\Factory\ShippingProviderFactory intentionally.
 */
class CommerceProviderFactory
{
    /** @var array<string, CommerceProviderInterface> */
    private array $instances = [];

    public function __construct(
        private readonly \Illuminate\Contracts\Container\Container $container,
    ) {}

    public function make(string $code): CommerceProviderInterface
    {
        if (isset($this->instances[$code])) {
            return $this->instances[$code];
        }

        $class = config('commerce.providers.' . $code . '.class');
        if (! $class || ! class_exists($class)) {
            throw new InvalidArgumentException("Unknown commerce provider: {$code}");
        }

        $instance = $this->container->make($class);
        if (! $instance instanceof CommerceProviderInterface) {
            throw new InvalidArgumentException(
                "Class {$class} for provider {$code} does not implement CommerceProviderInterface."
            );
        }

        return $this->instances[$code] = $instance;
    }

    public function forConnection(CommerceConnection $connection): CommerceProviderInterface
    {
        return $this->make($connection->provider->code);
    }

    /** Codes registered in config. Used by the admin UI's "Add connection" dropdown. */
    public function codes(): array
    {
        return array_keys((array) config('commerce.providers', []));
    }
}
