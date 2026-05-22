<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiLog
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $this->logRequest($request);
        return $next($request);
    }

    /**
     * Log request details.
     */
    protected function logRequest(Request $request): void
    {
        $date = now()->format('Y-m-d');
        $logPath = "log/{$date}";

        $ip = $request->ip();
        $url = $request->fullUrl();
        $agent = $request->header('User-Agent', 'unknown');

        // Log basic request info
        $meta = sprintf("[%s] IP: %s | URL: %s | Agent: %s", now()->toDateTimeString(), $ip, $url, $agent);
        Storage::disk('local')->append("{$logPath}/urls.txt", $meta);

        // Sanitize sensitive data
        $data = $request->all();
        if (isset($data['image'])) {
            $data['image'] = '[base64 hidden]';
        }

        // Log request data
        Storage::disk('local')->append("{$logPath}/requests.txt", json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Log the response after sending it to the client.
     */
    public function terminate(Request $request, $response): void
    {
        $date = now()->format('Y-m-d');
        $logPath = "log/{$date}";

        // Capture a shortened version of the response to avoid huge logs
        $content = method_exists($response, 'getContent')
            ? mb_substr($response->getContent(), 0, 1000)
            : json_encode($response);

        $entry = sprintf("[%s] Response: %s", now()->toDateTimeString(), $content);
        Storage::disk('local')->append("{$logPath}/responses.txt", $entry);
    }
}
