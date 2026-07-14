<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Redirects authenticated tenant Admins to /onboarding while their
 * general_settings.onboarding_completed_at is still NULL. Skipped for:
 *   - super admins (they don't own a single tenant to onboard)
 *   - non-admin user types (merchants, deliverymen, hubs)
 *   - requests already inside the wizard, its assets, or logout
 *   - AJAX/JSON API traffic — never wall an API call
 */
class RequireOnboarding
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) return $next($request);
        if ((int) $user->user_type !== UserType::ADMIN) return $next($request);

        // The wizard itself + logout + any other whitelisted path shouldn't trigger a redirect.
        if ($this->isExemptPath($request)) return $next($request);

        try {
            $completed = optional(settings())->onboarding_completed_at;
        } catch (\Throwable $e) {
            return $next($request); // fail open on settings errors
        }
        if ($completed) return $next($request);

        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        // Hard-coded path (not route()) — the wizard's routes live inside
        // the tenant-domain gate in routes/web.php, so route lookup can fail
        // in CLI contexts and pre-tenant middleware layers.
        return redirect('/onboarding');
    }

    private function isExemptPath(Request $request): bool
    {
        $path = trim($request->path(), '/');
        if ($path === '') return false;
        return str_starts_with($path, 'onboarding')
            || str_starts_with($path, 'logout')
            || str_starts_with($path, 'lang/')
            || str_starts_with($path, 'set-locale')
            || $path === 'setlocalization'
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'uploads/')
            || str_starts_with($path, 'storage/');
    }
}
