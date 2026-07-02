<?php

namespace App\Http\Middleware;

use App\Models\PublicTrackingApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies a public tracking API key presented on a stateless API call.
 *
 * Accepts the key from either the `X-API-Key` header (preferred, since
 * browsers can attach it via fetch/XHR) or the `api_key` query string
 * (fallback for callers that can't set headers).
 *
 * On success:
 *   - stashes the key row on the request as `publicTrackingApiKey`
 *   - fire-and-forget bumps `last_used_at` + `request_count` via a raw
 *     UPDATE that skips model events / global scopes (this endpoint
 *     will be hot; a full model save() is overkill).
 *   - if the key has an `allowed_origins` list, enforces the request's
 *     Origin/Referer against it (browser-side abuse gate).
 *
 * On failure: 401 JSON with a stable machine-readable error code.
 */
class VerifyPublicTrackingApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plaintext = $this->extractKey($request);
        if ($plaintext === null || $plaintext === '') {
            return $this->deny('missing_api_key', 'API key required. Send X-API-Key header or ?api_key= query parameter.');
        }

        $key = PublicTrackingApiKey::findByPlaintext($plaintext);
        if (! $key) {
            return $this->deny('invalid_api_key', 'The provided API key is invalid or has been revoked.');
        }

        // Optional origin allow-list. Only enforced when the caller
        // actually sent an Origin/Referer (server-to-server callers
        // typically send neither, and we don't want to break them).
        if (is_array($key->allowed_origins) && ! empty($key->allowed_origins)) {
            $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');
            if ($origin && ! $this->matchesAllowList($origin, $key->allowed_origins)) {
                return $this->deny('origin_not_allowed', 'Request origin is not permitted for this API key.');
            }
        }

        // Cheap usage bump — no model events, no global scopes.
        DB::table('public_tracking_api_keys')
            ->where('id', $key->id)
            ->update([
                'last_used_at'  => now(),
                'request_count' => DB::raw('request_count + 1'),
            ]);

        $request->attributes->set('publicTrackingApiKey', $key);

        return $next($request);
    }

    private function extractKey(Request $request): ?string
    {
        return $request->header('X-API-Key')
            ?? $request->header('apiKey')             // tolerate legacy header name
            ?? $request->query('api_key');
    }

    private function matchesAllowList(string $origin, array $allowed): bool
    {
        $host = parse_url($origin, PHP_URL_HOST);
        if (! $host) return false;
        foreach ($allowed as $entry) {
            $entryHost = parse_url($entry, PHP_URL_HOST) ?: $entry;
            if (strcasecmp($host, $entryHost) === 0) return true;
        }
        return false;
    }

    private function deny(string $code, string $message): Response
    {
        return response()->json([
            'success' => false,
            'error'   => $code,
            'message' => $message,
        ], 401);
    }
}
