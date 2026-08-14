<?php

namespace App\Shipping\Providers;

use App\Shipping\Contracts\ShippingProviderInterface;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\Exceptions\ProviderUnavailableException;
use App\Shipping\Logging\ApiLogger;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Shared HTTP plumbing for all providers. Concrete providers implement the
 * domain methods (createShipment, getStatus, ...) and call $this->http(...) to
 * send requests. Every call is automatically:
 *
 *   - timed (duration_ms)
 *   - logged to shipping_api_logs (request, response, status, error)
 *   - sensitive-header-masked before write
 *   - wrapped in a try/catch that turns transport errors into typed exceptions
 *
 * Providers stay focused on payload shape + response mapping. They never write
 * to the api_logs table directly.
 */
abstract class AbstractProvider implements ShippingProviderInterface
{
    public function __construct(
        protected readonly ApiLogger $logger,
    ) {}

    /**
     * Provider config block from config/shipping.php (base_url, timeout, ...).
     */
    protected function config(string $key, $default = null)
    {
        return config('shipping.providers.' . $this->code() . '.config.' . $key, $default);
    }

    /**
     * Single chokepoint for outbound HTTP. Concrete providers pass a request
     * builder (headers, body, query) via the $build closure; this method
     * handles timing, logging, error normalization.
     *
     * Returns the Laravel Http Response on success (status, body, json).
     * Throws ProviderUnavailableException on transport / 5xx errors.
     */
    protected function http(
        string $method,
        string $endpoint,
        ?ConnectionDTO $connection,
        \Closure $build,                  // fn(PendingRequest $r): PendingRequest
        ?array $rawBody = null,           // for logging only — the actual body lives in $build
    ): Response {
        $base    = rtrim((string) $this->config('base_url', ''), '/');
        $url     = $base . $endpoint;
        $timeout = (int) ($this->config('timeout', 30));

        // Phase 8 — HTTP-level retry for transient transport faults.
        // Sits below the queue-level retry in the resilience stack:
        // absorbs 1-2 connection failures within a single job attempt
        // before failing back to the job runner. Never retries on 4xx —
        // provider-rejected requests won't get better on retry.
        $tries   = (int) config('shipping.retry.http_tries',   2);
        $sleepMs = (int) config('shipping.retry.http_sleep_ms', 250);

        $req = Http::timeout($timeout)
            ->acceptJson()
            ->retry($tries, $sleepMs, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
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
        // Illuminate\Http\Client\PendingRequest stores options in a protected
        // property — read via reflection to avoid stringifying the whole request.
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
