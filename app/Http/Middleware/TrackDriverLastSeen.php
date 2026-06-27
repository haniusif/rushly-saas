<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Models\Backend\DeliveryMan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Bumps delivery_man.last_seen_at = now() whenever a driver makes a request.
 *
 * Throttled to once per N seconds per driver via cache to avoid hammering the
 * row on every API call. The cache lock is per-driver so high-throughput
 * drivers don't block each other.
 *
 * The middleware ALWAYS forwards the request — instrumentation failure must
 * not break the actual driver flow, so writes are wrapped in try/catch.
 */
class TrackDriverLastSeen
{
    /** Don't bump more often than this. */
    private const THROTTLE_SECONDS = 60;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = Auth::user();
            if ($user && (int) ($user->user_type ?? 0) === UserType::DELIVERYMAN) {
                $key = "driver_last_seen:{$user->id}";
                if (! Cache::has($key)) {
                    Cache::put($key, 1, self::THROTTLE_SECONDS);
                    DeliveryMan::where('user_id', $user->id)
                        ->update(['last_seen_at' => now()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TrackDriverLastSeen failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
