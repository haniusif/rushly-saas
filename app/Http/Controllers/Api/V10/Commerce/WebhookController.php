<?php

namespace App\Http\Controllers\Api\V10\Commerce;

use App\Commerce\Exceptions\CommerceException;
use App\Commerce\Services\WebhookIngestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Single generic entry point for all inbound commerce webhooks.
 *
 *   POST /api/v10/commerce/{provider}/webhook
 *
 * Per-provider HMAC verification + parsing + idempotent persistence + job
 * dispatch all live in WebhookIngestService. This controller just maps
 * its outcomes to HTTP status codes.
 *
 * No Sanctum / apiKey: the auth IS the webhook signature, verified against
 * the per-connection webhook_secret_encrypted. That makes the surface
 * tenant-safe even though it's exposed without a bearer token — an
 * attacker who knows the URL still can't deliver an event without the
 * right HMAC.
 *
 * Feature flag: the entire endpoint 404s when `features.commerce_layer`
 * is off, so flipping the env from `true` to `false` is an immediate
 * fail-closed kill switch for ingest.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookIngestService $ingest,
    ) {
        // Allow CLI introspection (route:list) to instantiate the controller
        // without aborting. Same pattern as the connections controller.
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        try {
            $result = $this->ingest->ingest($provider, $request);
        } catch (CommerceException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422;
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], $status);
        } catch (\Throwable $e) {
            // Unexpected fault — log and 500. Storefront will retry per its
            // own policy.
            Log::error('commerce.webhook.controller.unhandled', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
                'file'     => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(['ok' => false, 'message' => 'Internal error.'], 500);
        }

        return response()->json([
            'ok'        => true,
            'event_id'  => $result['event']?->id,
            'duplicate' => $result['duplicate'],
            'message'   => $result['message'],
        ], $result['duplicate'] ? 200 : 202);
    }
}
