<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Traits\ApiReturnFormatTrait;
use Closure;
use Illuminate\Http\Request;

/**
 * Admits any user_type that runs the back-office: ADMIN, SUPER_ADMIN,
 * INCHARGE, HUB. Merchants and deliverymen are rejected so they can't
 * reach the admin API even with a valid Sanctum token.
 *
 * Run AFTER auth:sanctum.
 */
class CheckAdminRoleMiddleware
{
    use ApiReturnFormatTrait;

    private const ADMIT = [
        UserType::ADMIN,
        UserType::SUPER_ADMIN,
        UserType::INCHARGE,
        UserType::HUB,
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !in_array((int) $user->user_type, self::ADMIT, true)) {
            return $this->responseWithError('Forbidden', [], 403);
        }

        return $next($request);
    }
}
