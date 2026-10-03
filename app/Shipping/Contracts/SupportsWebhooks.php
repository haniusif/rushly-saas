<?php

namespace App\Shipping\Contracts;

use App\Shipping\DTOs\TrackingDTO;
use Illuminate\Http\Request;

/**
 * Marker interface for providers that push status updates rather than (or in
 * addition to) supporting polling. WebhookService dispatches incoming requests
 * to whichever provider matches the URL/secret and calls handleWebhook().
 */
interface SupportsWebhooks
{
    /**
     * Verify HMAC / shared-secret on the inbound request. Throws on auth
     * failure (lets the WebhookService return 401 cleanly).
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Parse the inbound payload into a TrackingDTO. Caller persists the
     * mapped status against the matching Shipment.
     */
    public function handleWebhook(Request $request): TrackingDTO;
}
