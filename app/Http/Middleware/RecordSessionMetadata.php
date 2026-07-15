<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Stamps the current session with the request's IP + user agent so the
 * Browser Sessions listing on /admin/profile/{id} can identify each
 * device. Only runs on GET / HEAD (skips XHR / API traffic) so the
 * IP/UA reflect the actual browsing device, not the last webhook or
 * background poller.
 */
class RecordSessionMetadata
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasSession() && $request->isMethod('GET')) {
            $session = $request->session();
            $ip      = (string) $request->ip();
            $ua      = (string) $request->userAgent();
            if ($ip !== '' && $session->get('_ip') !== $ip) {
                $session->put('_ip', $ip);
            }
            if ($ua !== '' && $session->get('_ua') !== $ua) {
                $session->put('_ua', $ua);
            }
        }
        return $next($request);
    }
}
