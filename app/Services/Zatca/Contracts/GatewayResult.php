<?php

namespace App\Services\Zatca\Contracts;

final class GatewayResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $referenceId = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function noop(): self
    {
        return new self(true, null, 'Phase 1: no gateway integration');
    }
}
