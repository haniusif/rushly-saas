<?php

namespace App\Salla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Salla\Models\WebhookLog;
use App\Salla\Webhooks\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request, Dispatcher $dispatcher): JsonResponse
    {
        $payload  = $request->all();
        $event    = (string) ($payload['event'] ?? '');
        $strategy = (string) $request->attributes->get('salla_webhook_strategy', 'Signature');
        $startedAt = $request->attributes->get('salla_webhook_received_at') ?? microtime(true);

        $error = null;
        $status = WebhookLog::STATUS_HANDLED;

        try {
            $handled = $dispatcher->dispatch($payload);
            if (! $handled) {
                $status = WebhookLog::STATUS_UNHANDLED;
            }
        } catch (\Throwable $e) {
            $status = WebhookLog::STATUS_FAILED;
            $error = $e->getMessage();
            Log::error('salla.webhook.handler_failed', [
                'event' => $event,
                'error' => $error,
            ]);
        }

        try {
            WebhookLog::create([
                'event'             => $event ?: null,
                'strategy'          => $strategy,
                'salla_merchant_id' => $this->merchantId($payload),
                'status'            => $status,
                'signature_valid'   => true,
                'payload'           => WebhookLog::sanitisePayload($payload),
                'headers'           => WebhookLog::sanitiseHeaders($request->headers->all()),
                'error'             => $error,
                'duration_ms'       => (int) round((microtime(true) - $startedAt) * 1000),
                'ip'                => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('salla.webhook.persist_failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => $status !== WebhookLog::STATUS_FAILED]);
    }

    private function merchantId(array $payload): ?int
    {
        return $payload['merchant']
            ?? $payload['data']['merchant']['id']
            ?? $payload['data']['store']['id']
            ?? null;
    }
}
