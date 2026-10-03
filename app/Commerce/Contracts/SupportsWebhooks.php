<?php

namespace App\Commerce\Contracts;

use App\Commerce\DTOs\WebhookEventDTO;
use Illuminate\Http\Request;

/**
 * Marker for providers that push events to us instead of (or in addition
 * to) requiring us to poll. The Phase 3 WebhookIngestService dispatches
 * inbound requests to whichever provider matches the URL slug and calls
 * verifyWebhook() + parseWebhookEvent().
 */
interface SupportsWebhooks
{
    /**
     * Verify HMAC / shared-secret / Basic-Auth on the inbound request
     * against the connection's webhook_secret. Returns true on pass; the
     * ingest service returns 401 on false.
     */
    public function verifyWebhook(Request $request, ?string $webhookSecret): bool;

    /**
     * Parse the inbound payload into a WebhookEventDTO with a stable
     * idempotency_key (so retried deliveries are deduped by
     * webhook_events.idempotency_key unique index — Phase 3).
     */
    public function parseWebhookEvent(Request $request): WebhookEventDTO;
}
