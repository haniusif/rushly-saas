<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the internal service-to-service API (/api/internal/v1/merchant/*).
 *
 * Deliberately NOT the weak shared `apiKey` (config('rxcourier.api_key')):
 * this validates a strong, env-backed secret with a constant-time compare.
 * Company/merchant context is taken from the (validated) request payload by
 * the controller/service, never trusted from headers alone.
 */
class VerifyInternalServiceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('internal_api.token');
        $presented = (string) $request->bearerToken();

        if ($expected === '' || $presented === '' || ! hash_equals($expected, $presented)) {
            return response()->json([
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Invalid internal service token.'],
            ], 401);
        }

        return $next($request);
    }
}
