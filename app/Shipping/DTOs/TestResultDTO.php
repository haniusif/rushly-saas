<?php

namespace App\Shipping\DTOs;

final class TestResultDTO
{
    public function __construct(
        public readonly bool   $ok,
        public readonly string $message,
        public readonly array  $diagnostics = [],
    ) {}

    public static function ok(string $message, array $diagnostics = []): self
    {
        return new self(true, $message, $diagnostics);
    }

    public static function fail(string $message, array $diagnostics = []): self
    {
        return new self(false, $message, $diagnostics);
    }
}
