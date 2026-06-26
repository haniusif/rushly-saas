<?php

namespace App\Salla\Http\Middleware;

use App\Salla\Models\WebhookLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $strategy = $request->header('X-Salla-Security-Strategy', 'Signature');
        $event    = $request->input('event');
        $payload  = $request->all();

        Log::info('salla.webhook.received', [
            'event'    => $event,
            'strategy' => $strategy,
            'has_sig'  => $request->hasHeader('X-Salla-Signature'),
            'has_auth' => $request->hasHeader('Authorization'),
            'ip'       => $request->ip(),
        ]);

        // Per-tenant webhook secret: this route lives under the tenant
        // subdomain, so tenancy middleware has already initialized the right
        // tenant context before we run.
        $secret = (string) (sallaCreds('webhook_secret') ?: '');
        if ($secret === '') {
            $this->persist($request, $payload, $strategy, $event, WebhookLog::STATUS_REJECTED, false, 'missing_secret');
            abort(500, 'Salla webhook secret not configured for this tenant');
        }

        $rejection = null;
        if ($strategy === 'Token') {
            if (! hash_equals($secret, (string) $request->header('Authorization'))) {
                $rejection = 'invalid_token';
            }
        } else {
            $signature = (string) $request->header('X-Salla-Signature');
            $expected  = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, $signature)) {
                $rejection = 'invalid_signature';
            }
        }

        if ($rejection !== null) {
            Log::warning('salla.webhook.rejected', ['reason' => $rejection, 'event' => $event]);
            $this->persist($request, $payload, $strategy, $event, WebhookLog::STATUS_REJECTED, false, $rejection);
            abort(401, $rejection === 'invalid_token' ? 'Invalid token' : 'Invalid signature');
        }

        $request->attributes->set('salla_webhook_received_at', microtime(true));
        $request->attributes->set('salla_webhook_strategy', $strategy);
        return $next($request);
    }

    private function persist(
        Request $request,
        array $payload,
        string $strategy,
        ?string $event,
        string $status,
        bool $signatureValid,
        ?string $reason = null,
    ): void {
        try {
            WebhookLog::create([
                'event'             => $event ?: null,
                'strategy'          => $strategy,
                'salla_merchant_id' => $this->extractMerchantId($payload),
                'status'            => $status,
                'signature_valid'   => $signatureValid,
                'rejection_reason'  => $reason,
                'payload'           => WebhookLog::sanitisePayload($payload),
                'headers'           => WebhookLog::sanitiseHeaders($request->headers->all()),
                'ip'                => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('salla.webhook.persist_failed', ['error' => $e->getMessage()]);
        }
    }

    private function extractMerchantId(array $payload): ?int
    {
        return $payload['merchant']
            ?? $payload['data']['merchant']['id']
            ?? $payload['data']['store']['id']
            ?? null;
    }
}
