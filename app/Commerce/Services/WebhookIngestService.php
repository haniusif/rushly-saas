<?php

namespace App\Commerce\Services;

use App\Commerce\Contracts\SupportsWebhooks;
use App\Commerce\Exceptions\CommerceException;
use App\Commerce\Factory\CommerceProviderFactory;
use App\Commerce\Jobs\IngestWebhookJob;
use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\WebhookEvent;
use App\Commerce\Repositories\CommerceConnectionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for inbound webhook deliveries from commerce providers.
 *
 * Pipeline:
 *   1. Provider exists + implements SupportsWebhooks         → 400 otherwise
 *   2. Provider parses payload into WebhookEventDTO          → 400 on parse fail
 *   3. Resolve target connection from event metadata         → 404 / 401 if unknown
 *   4. Provider verifies HMAC against connection.webhook_secret → 401 if invalid
 *   5. Persist webhook_event row (UNIQUE idempotency_key)    → 200 (no-op) if dup
 *   6. Dispatch IngestWebhookJob                             → 202 returned
 *
 * The signature verify happens AFTER connection resolution because the
 * secret lives on the connection row — we can't verify without first
 * knowing which connection the event belongs to. Connection resolution
 * uses provider-emitted metadata (remote_store_id, merchant id) which is
 * unsigned at this point — but if the resolved connection's secret then
 * verifies the body HMAC, the trust chain is complete. An attacker who
 * knows a tenant's remote_store_id still can't forge events without the
 * webhook_secret.
 *
 * Duplicate handling: SQL unique-constraint violation on idempotency_key
 * is caught and treated as success (the event was already received). The
 * job is NOT re-dispatched.
 */
class WebhookIngestService
{
    public function __construct(
        private readonly CommerceProviderFactory $factory,
        private readonly CommerceConnectionRepository $repo,
    ) {}

    /**
     * @return array{event: ?WebhookEvent, duplicate: bool, message: string}
     *
     * Throws CommerceException for input/auth failures so the calling
     * controller can map cleanly to HTTP status codes.
     */
    public function ingest(string $providerCode, Request $request): array
    {
        $provider = $this->factory->make($providerCode);
        if (! $provider instanceof SupportsWebhooks) {
            throw new CommerceException("Provider '{$providerCode}' does not support webhooks.", ['provider' => $providerCode], code: 400);
        }

        // Parse — provider extracts event type, signature, idempotency key,
        // and the raw payload. Throws if the body is malformed.
        try {
            $parsed = $provider->parseWebhookEvent($request);
        } catch (\Throwable $e) {
            throw new CommerceException(
                "Provider failed to parse webhook payload: " . $e->getMessage(),
                ['provider' => $providerCode],
                $e,
                code: 400,
            );
        }

        // Resolve the connection. Providers expose a hook so each can use
        // whichever field of its payload identifies the storefront install
        // (Salla: merchant; Shopify: shop domain). The default — look up
        // by (provider_code, remote_store_id) from parsed metadata — works
        // for most.
        $connection = $this->resolveConnection($providerCode, $parsed);
        if (! $connection) {
            throw new CommerceException(
                "No active connection found for provider '{$providerCode}'. The store may have uninstalled the app.",
                ['provider' => $providerCode, 'event' => $parsed->eventType],
                code: 404,
            );
        }

        // Verify HMAC / token against the connection's stored secret.
        if (! $provider->verifyWebhook($request, $connection->webhook_secret_encrypted)) {
            // Don't disclose which check failed (signature vs secret-missing) to
            // the caller. They get one 401 either way.
            Log::warning('commerce.webhook.signature_invalid', [
                'provider'      => $providerCode,
                'connection_id' => $connection->id,
                'event_type'    => $parsed->eventType,
            ]);
            throw new CommerceException('Invalid webhook signature.', ['provider' => $providerCode], code: 401);
        }

        // Persist + dispatch in one transaction. The unique idempotency_key
        // catches duplicate deliveries — when caught, we return success
        // without re-enqueueing the job. Same response shape as a fresh
        // delivery from the provider's perspective.
        $duplicate = false;
        $event = null;
        try {
            $event = DB::transaction(function () use ($parsed, $connection, $request) {
                return WebhookEvent::create([
                    'company_id'      => $connection->company_id,
                    'connection_id'   => $connection->id,
                    'provider_code'   => $parsed->providerCode,
                    'event_type'      => $parsed->eventType,
                    'idempotency_key' => $parsed->idempotencyKey,
                    'signature'       => $parsed->signature ?? $request->header('X-Signature') ?? null,
                    'payload'         => $parsed->payload,
                    'received_at'     => now(),
                ]);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $duplicate = true;
            $event = WebhookEvent::query()->where('idempotency_key', $parsed->idempotencyKey)->first();
            Log::info('commerce.webhook.duplicate', [
                'provider'        => $providerCode,
                'connection_id'   => $connection->id,
                'idempotency_key' => $parsed->idempotencyKey,
            ]);
        }

        if (! $duplicate && $event) {
            IngestWebhookJob::dispatch($event->id)
                ->onQueue((string) config('commerce.queue.name', 'commerce'));
        }

        return [
            'event'     => $event,
            'duplicate' => $duplicate,
            'message'   => $duplicate ? 'Duplicate delivery; original event already accepted.' : 'Event accepted.',
        ];
    }

    /**
     * Default connection resolver: lookup by (provider_code, remote_store_id)
     * extracted from the parsed event payload. Providers that need a
     * different strategy (e.g. Shopify resolving by shop domain in
     * X-Shopify-Shop-Domain header) can override the field path via the
     * provider's parseWebhookEvent → idempotency key composition.
     */
    private function resolveConnection(string $providerCode, $parsed): ?CommerceConnection
    {
        // The default extraction pulls from the provider-shaped payload.
        // Salla puts `merchant` at the top of the body OR at data.merchant.id;
        // Shopify uses the X-Shopify-Shop-Domain header (parser captures it
        // into payload['_shop_domain'] by convention).
        $remoteStoreId = (string) (
            data_get($parsed->payload, 'merchant')
            ?? data_get($parsed->payload, 'data.merchant.id')
            ?? data_get($parsed->payload, 'data.store.id')
            ?? data_get($parsed->payload, '_shop_domain')
            ?? ''
        );

        if ($remoteStoreId === '') {
            return null;
        }

        return $this->repo->findByRemoteStore($providerCode, $remoteStoreId);
    }
}
