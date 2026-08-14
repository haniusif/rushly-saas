<?php

namespace App\Daftra\Services;

use App\Daftra\Models\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Daftra REST client. Per-tenant: each Rushly company has its own Daftra
 * subdomain ({sub}.daftra.com) and API key.
 *
 * Auth: APIKEY: <key> header
 * Resources: clients, products, invoices, invoice_payments
 * Body wrappers: { "Client": {...} } / { "Invoice": {...}, "InvoiceItem": [...] }
 *
 * Docs: https://docs.daftara.dev/
 */
class ApiClient
{
    public function __construct(private Settings $settings) {}

    public static function forCompany(int $companyId): self
    {
        $s = Settings::forCompany($companyId);
        if (! $s->exists || ! (string) $s->api_key || ! $s->baseUrl()) {
            throw new RuntimeException("Daftra not configured for company {$companyId}");
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

    public function put(string $path, array $body): array
    {
        return $this->request('put', $path, ['json' => $body]);
    }

    public function delete(string $path): array
    {
        return $this->request('delete', $path, []);
    }

    private function request(string $method, string $path, array $opts): array
    {
        $url = rtrim($this->settings->baseUrl(), '/') . '/' . ltrim($path, '/');
        $req = Http::withHeaders([
                'APIKEY' => (string) $this->settings->api_key,
                'Accept' => 'application/json',
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(30);

        $response = match ($method) {
            'get'    => $req->get($url, $opts['query'] ?? []),
            'post'   => $req->post($url, $opts['json'] ?? []),
            'put'    => $req->put($url, $opts['json'] ?? []),
            'delete' => $req->delete($url),
            default  => throw new RuntimeException("Unsupported method: {$method}"),
        };

        if (! $response->successful()) {
            $this->logFailure($method, $url, $opts, $response);
            throw new RuntimeException(
                "Daftra {$method} {$path} failed: HTTP {$response->status()} — " . $response->body()
            );
        }

        return (array) $response->json();
    }

    private function logFailure(string $method, string $url, array $opts, Response $response): void
    {
        Log::error('daftra.api.failed', [
            'company' => $this->settings->company_id,
            'method'  => $method,
            'url'     => $url,
            'status'  => $response->status(),
            'body'    => $response->json() ?: $response->body(),
        ]);
    }
}
