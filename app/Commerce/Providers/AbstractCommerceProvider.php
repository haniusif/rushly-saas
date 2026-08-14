<?php

namespace App\Commerce\Providers;

use App\Commerce\Contracts\CommerceProviderInterface;
use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\Exceptions\ProviderUnavailableException;
use App\Commerce\Logging\ApiLogger;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Shared HTTP plumbing for every commerce provider. Concrete providers
 * implement the domain methods (fetchOrder, pushOrderUpdate, …) and call
 * $this->http(...) to send requests. Every call is automatically:
 *
 *   - timed (duration_ms)
 *   - logged to commerce_api_logs (request, response, status, error)
 *   - sensitive-header-masked before write
 *   - wrapped in a try/catch that turns transport errors into typed exceptions
 *
 * Providers stay focused on payload shape + response mapping. They never
 * write to the api_logs table directly. Mirrors
 * App\Shipping\Providers\AbstractProvider intentionally so the two
 * modules stay readable side by side.
 */
abstract class AbstractCommerceProvider implements CommerceProviderInterface
{
    public function __construct(
        protected readonly ApiLogger $logger,
    ) {}

    /**
     * Provider config block from config/commerce.php (base_url, timeout, ...).
     */
    protected function config(string $key, $default = null)
    {
        return config('commerce.providers.' . $this->code() . '.config.' . $key, $default);
    }

    /**
     * Single chokepoint for outbound HTTP. Concrete providers pass a
     * request builder (headers, body, query) via the $build closure; this
     * method handles timing, logging, error normalization.
     *
     * Returns the Laravel Http Response on success (status, body, json).
     * Throws ProviderUnavailableException on transport / 5xx errors.
     */
    protected function http(
        string $method,
        string $endpoint,
        ?CommerceConnectionDTO $connection,
        \Closure $build,                  // fn(PendingRequest $r): PendingRequest
        ?array $rawBody = null,           // for logging only — the actual body lives in $build
    ): Response {
        $base    = rtrim((string) $this->config('base_url', ''), '/');
        $url     = str_starts_with($endpoint, 'http') ? $endpoint : $base . $endpoint;
        $timeout = (int) ($this->config('timeout', 30));

        // Phase 8 — HTTP-level retry for transient faults (connection
        // resets, provider 5xx). Cheaper than a full queue retry: a
        // single job attempt now absorbs 1-2 transient failures before
        // giving up. The `throw: false` flag makes retry re-issue the
        // request but NEVER throw on non-2xx — we handle the
        // Response ourselves below.
        //
        // Retries only fire on ConnectionException / >=500 status. 4xx
        // (auth failure, malformed payload) fall straight through to
        // our own error handling because retrying won't help them.
        $tries   = (int) config('commerce.retry.http_tries',   2);
        $sleepMs = (int) config('commerce.retry.http_sleep_ms', 250);

        $req = Http::timeout($timeout)
            ->acceptJson()
            ->retry($tries, $sleepMs, function ($exception, $request) {
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) return true;
                // Retry on 5xx responses too — Laravel exposes them via $exception when throw() is set,
                // but with our throw:false above we only retry on transport failures.
                return false;
            }, throw: false);
        $req = $build($req);

        $startedAt = microtime(true);
        $error     = null;
        $resp      = null;

        try {
            $resp = $req->send(strtoupper($method), $url);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        $this->logger->record([
            'company_id'      => $connection?->companyId,
            'connection_id'   => $connection?->id,
            'provider_code'   => $this->code(),
            'endpoint'        => $endpoint,
            'method'          => $method,
            'request_headers' => $this->extractHeaders($req),
            'request_body'    => $rawBody,
            'response_status' => $resp?->status(),
            'response_body'   => $resp ? $this->safeJsonOrBody($resp) : null,
            'duration_ms'     => $durationMs,
            'error'           => $error,
        ]);

        if ($error !== null) {
            throw new ProviderUnavailableException(
                "{$this->code()} transport error: {$error}",
                ['endpoint' => $endpoint, 'method' => $method],
            );
        }

        if ($resp->serverError()) {
            throw new ProviderUnavailableException(
                "{$this->code()} returned HTTP {$resp->status()}",
                ['endpoint' => $endpoint, 'method' => $method, 'body' => $this->safeJsonOrBody($resp)],
            );
        }

        return $resp;
    }

    /**
     * Reflect the PendingRequest's options to pull the headers we'll send.
     * Used purely for logging; never relied on for behavior.
     */
    private function extractHeaders($pendingRequest): array
    {
        try {
            $ref = new \ReflectionClass($pendingRequest);
            $prop = $ref->getProperty('options');
            $prop->setAccessible(true);
            $opts = $prop->getValue($pendingRequest);
            return (array) ($opts['headers'] ?? []);
        } catch (\Throwable) {
            return [];
        }
    }

    private function safeJsonOrBody(Response $resp): array|string|null
    {
        try {
            $json = $resp->json();
            if (is_array($json)) return $json;
        } catch (\Throwable) {
            // fall through
        }
        $body = (string) $resp->body();
        return $body !== '' ? $body : null;
    }
}
