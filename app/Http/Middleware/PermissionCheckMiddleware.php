<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PermissionCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        // Treat users with no permissions column (NULL or non-array) as unauthorised,
        // not a 500. Previously this fired in_array(..., null) → TypeError on every
        // gated route for newly-provisioned users.
        $perms = Auth::check() ? Auth::user()->permissions : null;
        if (is_array($perms) && in_array($permission, $perms, true)) {
            return $next($request);
        }
        return redirect('/');
    }
}
