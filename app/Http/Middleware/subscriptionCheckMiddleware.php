<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class subscriptionCheckMiddleware
{
    protected $except = [
        'admin/profile*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is(...$this->except)) {
            $user = Auth::user();
            if ($user && $user->user_type != UserType::SUPER_ADMIN && ! subscriptionCheck()) {
                return redirect()->route('subscription.index');
            }
        }

        $response = $next($request);

        if (! $response instanceof Response) {
            // Some controller returned void/null. Log enough context to track it down,
            // then return an empty 204 instead of crashing on the return-type contract.
            Log::warning('subscriptionCheckMiddleware: downstream returned non-Response', [
                'route'  => optional($request->route())->getName(),
                'action' => optional($request->route())->getActionName(),
                'url'    => $request->fullUrl(),
            ]);
            return response()->noContent();
        }

        return $response;
    }
}
