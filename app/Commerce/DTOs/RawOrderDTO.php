<?php

namespace App\Commerce\DTOs;

/**
 * Raw, provider-native order payload. Carries enough to identify the order
 * unambiguously plus the full provider response — normalization to the
 * canonical OrderDTO happens in the OMS layer (Phase 4 OrderNormalizer),
 * not here.
 *
 * Keeping the full payload around lets us reprocess events without
 * re-hitting the storefront when normalization rules change.
 */
final class RawOrderDTO
{
    public function __construct(
        public readonly string  $providerCode,
        public readonly string  $remoteOrderId,            // String — Zid uses opaque strings
        public readonly ?string $remoteOrderNumber = null, // Human-readable order # ("#1042")
        public readonly array   $payload           = [],   // Full provider response
        public readonly ?string $occurredAt        = null, // ISO 8601 — provider's event timestamp
    ) {}
}
