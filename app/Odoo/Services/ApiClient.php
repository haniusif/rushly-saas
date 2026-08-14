<?php

namespace App\Odoo\Services;

use App\Odoo\Models\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Odoo JSON-RPC client. Per-tenant: each Rushly company connects to its own
 * Odoo instance (host + database + username + api_key/password).
 *
 * Endpoint: POST {host}/jsonrpc
 *
 * Auth is a two-step dance:
 *  1. service=common, method=authenticate, args=[db, username, password, {}] → uid
 *  2. service=object, method=execute_kw, args=[db, uid, password, model, method, [args], {kwargs}]
 *
 * The UID is cached on the Settings row so we don't re-authenticate every call.
 *
 * Docs: https://www.odoo.com/documentation/17.0/developer/reference/external_api.html
 */
class ApiClient
{
    public function __construct(private Settings $settings) {}

    public static function forCompany(int $companyId): self
    {
        $s = Settings::forCompany($companyId);
        if (! $s->exists || (string) $s->host_url === '' || (string) $s->database === '' || (string) $s->api_key === '') {
            throw new RuntimeException("Odoo not configured for company {$companyId}");
        }
        return new self($s);
    }

    public function authenticate(): int
    {
        if ($this->settings->cached_uid) {
            return (int) $this->settings->cached_uid;
        }

        $uid = $this->rpc('common', 'authenticate', [
            $this->settings->database,
            $this->settings->username,
            $this->settings->api_key,
            (object) [],
        ]);

        if (! is_int($uid) || $uid <= 0) {
            throw new RuntimeException('Odoo authentication failed — bad credentials or database');
        }

        $this->settings->forceFill(['cached_uid' => $uid])->save();
        return $uid;
    }

    /** Create a new record. Returns its integer ID. */
    public function create(string $model, array $values): int
    {
        $id = $this->executeKw($model, 'create', [$values]);
        if (! is_int($id) && ! is_numeric($id)) {
            throw new RuntimeException("Odoo create({$model}) returned non-integer: " . json_encode($id));
        }
        return (int) $id;
    }

    /** Update an existing record. */
    public function write(string $model, int $id, array $values): bool
    {
        return (bool) $this->executeKw($model, 'write', [[$id], $values]);
    }

    /** Search for record IDs. */
    public function search(string $model, array $domain = [], array $opts = []): array
    {
        return (array) $this->executeKw($model, 'search', [$domain], $opts);
    }

    /** Read fields from one or more records. */
    public function read(string $model, array $ids, array $fields = []): array
    {
        return (array) $this->executeKw($model, 'read', [$ids, $fields]);
    }

    /** Call any model method (e.g. action_post on account.move). */
    public function call(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        return $this->executeKw($model, $method, $args, $kwargs);
    }

    private function executeKw(string $model, string $method, array $args, array $kwargs = []): mixed
    {
        $uid = $this->authenticate();
        return $this->rpc('object', 'execute_kw', [
            $this->settings->database,
            $uid,
            $this->settings->api_key,
            $model,
            $method,
            $args,
            (object) $kwargs,
        ]);
    }

    private function rpc(string $service, string $method, array $args): mixed
    {
        $url = rtrim($this->settings->host_url, '/') . '/jsonrpc';
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'params'  => [
                'service' => $service,
                'method'  => $method,
                'args'    => $args,
            ],
        ];

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($url, $payload);

        if (! $response->successful()) {
            $this->logFailure($service, $method, $url, $response);
            throw new RuntimeException("Odoo HTTP {$response->status()}: " . $response->body());
        }

        $body = (array) $response->json();
        if (isset($body['error'])) {
            $err = $body['error'];
            $msg = $err['data']['message'] ?? $err['message'] ?? json_encode($err);
            Log::error('odoo.rpc.error', [
                'company' => $this->settings->company_id,
                'service' => $service,
                'method'  => $method,
                'error'   => $err,
            ]);
            throw new RuntimeException("Odoo {$service}.{$method} error: {$msg}");
        }

        return $body['result'] ?? null;
    }

    private function logFailure(string $service, string $method, string $url, Response $response): void
    {
        Log::error('odoo.rpc.failed', [
            'company' => $this->settings->company_id,
            'service' => $service,
            'method'  => $method,
            'url'     => $url,
            'status'  => $response->status(),
            'body'    => $response->body(),
        ]);
    }
}
