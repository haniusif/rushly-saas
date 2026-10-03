<?php

namespace App\Commerce\Webhooks;

use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\WebhookEvent;

/**
 * Per-provider business-logic handler for inbound webhooks.
 *
 * The pipeline split:
 *   - Provider class (e.g. SallaProvider) — verifyWebhook + parseWebhookEvent.
 *     Knows the wire format, not what to do with the event.
 *   - Handler class (e.g. SallaWebhookHandler) — what business logic fires
 *     for `order.created` / `app.uninstalled` / etc. Knows our domain, not
 *     the wire format.
 *
 * The handler is invoked from IngestWebhookJob after the event row has
 * been persisted and the connection resolved. It receives both so it can
 * persist new entities scoped to the right tenant/connection without
 * re-querying.
 *
 * Throw on failure — the job catches, stamps last_error, and lets Laravel
 * retry per the queue policy.
 */
interface HandlerInterface
{
    public function handle(WebhookEvent $event, CommerceConnection $connection): void;
}
