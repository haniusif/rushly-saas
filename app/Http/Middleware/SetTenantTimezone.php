<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Override app.timezone with the current tenant's setting for the duration
 * of this request. Falls back to the config default when the tenant has
 * no timezone on file, when the DB is unreachable, or when we're on the
 * central host without an authenticated user.
 *
 * Must run AFTER Stancl Tenancy's InitializeTenancyByDomain so that
 * `settings()` resolves to the tenant row rather than the central row.
 */
class SetTenantTimezone
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $tz = optional(settings())->timezone;
        } catch (\Throwable $e) {
            $tz = null;
        }

        if (is_string($tz) && $tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            Config::set('app.timezone', $tz);
            date_default_timezone_set($tz);
        }

        return $next($request);
    }
}
