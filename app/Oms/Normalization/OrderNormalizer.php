<?php

namespace App\Oms\Normalization;

use App\Oms\DTOs\OrderDTO;
use App\Oms\Exceptions\NormalizationException;
use InvalidArgumentException;

/**
 * Facade over per-provider mappers. Callers (SallaWebhookHandler, Phase 5
 * bulk-backfill jobs, admin "reprocess this event" button) hand it a
 * (providerCode, rawPayload) pair and get back a canonical OrderDTO.
 *
 * Resolution: config('commerce.providers.<code>.order_mapper') → FQCN → resolved
 * through the container so mappers can constructor-inject shared helpers
 * (AddressResolver, PayloadValidator, config).
 *
 * Kept intentionally slim — no logging, no side effects, no state. Test
 * doubles can substitute this cleanly.
 */
class OrderNormalizer
{
    /** @var array<string, OrderMapperInterface> */
    private array $mappers = [];

    public function __construct(
        private readonly \Illuminate\Contracts\Container\Container $container,
    ) {}

    /**
     * @throws NormalizationException  when the mapper rejects the payload
     * @throws InvalidArgumentException when no mapper is registered for the code
     */
    public function normalize(string $providerCode, array $payload, ?int $companyId = null): OrderDTO
    {
        return $this->mapperFor($providerCode)->map($payload, $companyId);
    }

    public function mapperFor(string $providerCode): OrderMapperInterface
    {
        if (isset($this->mappers[$providerCode])) {
            return $this->mappers[$providerCode];
        }

        $class = config('commerce.providers.' . $providerCode . '.order_mapper');
        if (! $class || ! class_exists($class)) {
            throw new InvalidArgumentException("No order mapper registered for provider '{$providerCode}'.");
        }

        $instance = $this->container->make($class);
        if (! $instance instanceof OrderMapperInterface) {
            throw new InvalidArgumentException(
                "Class {$class} for provider {$providerCode} does not implement OrderMapperInterface."
            );
        }

        return $this->mappers[$providerCode] = $instance;
    }

    /** Codes with a registered mapper. Useful for admin UI / diagnostics. */
    public function supportedCodes(): array
    {
        $out = [];
        foreach ((array) config('commerce.providers', []) as $code => $conf) {
            if (! empty($conf['order_mapper']) && class_exists($conf['order_mapper'])) {
                $out[] = $code;
            }
        }
        return $out;
    }
}
