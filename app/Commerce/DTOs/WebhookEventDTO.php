<?php

namespace App\Commerce\DTOs;

/**
 * Parsed inbound webhook event. WebhookIngestService (Phase 3) builds one
 * via SupportsWebhooks::parseWebhookEvent() and uses idempotencyKey as the
 * unique key against webhook_events to dedupe retries.
 *
 * Carries the raw payload so re-processing after a normalization-rule
 * change doesn't require the storefront to redeliver.
 */
final class WebhookEventDTO
{
    public function __construct(
        public readonly string  $providerCode,
        public readonly ?int    $connectionId,
        public readonly string  $eventType,        // 'order.created', 'order.updated', 'order.cancelled', 'shipment.creating', ...
        public readonly string  $idempotencyKey,   // Unique per (connection, event). Composed by parseWebhookEvent.
        public readonly array   $payload,
        public readonly ?string $signature  = null,
        public readonly ?string $occurredAt = null,
    ) {}
}
