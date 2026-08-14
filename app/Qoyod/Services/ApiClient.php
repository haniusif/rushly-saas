<?php

namespace App\Qoyod\Services;

use App\Qoyod\Models\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin Qoyod REST client. Auth is a single API-KEY header that the user
 * generates in their Qoyod General Settings page. Per-tenant: each Rushly
 * company has its own Qoyod account and api_key.
 *
 * Docs: https://apidoc.qoyod.com/
 */
class ApiClient
{
    private const BASE_URL = 'https://www.qoyod.com/2.0';

    public function __construct(private Settings $settings) {}

    public static function forCompany(int $companyId): self
    {
        $s = Settings::forCompany($companyId);
        if (! $s->exists || ! (string) $s->api_key) {
            throw new RuntimeException("Qoyod not configured for company {$companyId}");
        }
        return new self($s);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, ['query' => $query]);
    }

    public function post(string $path, array $body): array
    {
        return $this->request('post', $path, ['json' => $body]);
    }

    public function patch(string $path, array $body): array
    {
        return $this->request('patch', $path, ['json' => $body]);
    }

    public function put(string $path, array $body): array
    {
        return $this->request('put', $path, ['json' => $body]);
    }

    private function request(string $method, string $path, array $opts): array
    {
        $url = self::BASE_URL . '/' . ltrim($path, '/');
        $req = Http::withHeaders([
                'API-KEY' => (string) $this->settings->api_key,
                'Accept'  => 'application/json',
            ])
            ->acceptJson()
            ->timeout(30);

        $response = match ($method) {
            'get'   => $req->get($url, $opts['query'] ?? []),
            'post'  => $req->post($url, $opts['json'] ?? []),
            'patch' => $req->patch($url, $opts['json'] ?? []),
            'put'   => $req->put($url, $opts['json'] ?? []),
            default => throw new RuntimeException("Unsupported method: {$method}"),
        };

        if (! $response->successful()) {
            $this->logFailure($method, $url, $opts, $response);
            throw new RuntimeException(
                "Qoyod {$method} {$path} failed: HTTP {$response->status()} — " . $response->body()
            );
        }

        return (array) $response->json();
    }

    private function logFailure(string $method, string $url, array $opts, Response $response): void
    {
        Log::error('qoyod.api.failed', [
            'company' => $this->settings->company_id,
            'method'  => $method,
            'url'     => $url,
            'status'  => $response->status(),
            'body'    => $response->json() ?: $response->body(),
            'sent'    => $this->sanitisePayload($opts['json'] ?? []),
        ]);
    }

    private function sanitisePayload(array $payload): array
    {
        // Don't log the API key (it's a header, but if a future caller embeds it
        // in a body for some reason we strip it).
        if (isset($payload['api_key'])) $payload['api_key'] = '[REDACTED]';
        return $payload;
    }
}
