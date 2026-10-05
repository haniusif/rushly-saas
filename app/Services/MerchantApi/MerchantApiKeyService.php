<?php

namespace App\Services\MerchantApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client for the rushly-api façade "management API".
 *
 * Keys are NOT stored in rushly-saas — they live in rushly-api. This service
 * is the HTTP bridge the merchant dashboard uses to create / list / revoke a
 * merchant's Rushly Merchant API keys, across the "live" and "test"
 * environments (each its own façade base URL + bearer token, from
 * config/merchant_api.php).
 *
 * The caller (controller) resolves merchant_id / company_id from the
 * authenticated merchant; those values are ALWAYS sent as passed in and are
 * NEVER taken from client input.
 */
class MerchantApiKeyService
{
    /** The only scopes the façade accepts. Anything else is dropped. */
    public const VALID_SCOPES = [
        'shipments:create',
        'shipments:read',
        'shipments:cancel',
        'tracking:read',
        'statuses:read',
    ];

    /** HTTP timeout (seconds) for every façade call. */
    private const TIMEOUT = 15;

    /**
     * List every key this merchant has, across ALL configured environments
     * (so the merchant sees both live and test keys in one table). Each
     * returned client array is tagged with its `environment` for the UI.
     */
    public function list(int $merchantId, int $companyId): array
    {
        $clients = [];

        foreach ($this->availableEnvironments() as $environment) {
            $cfg = $this->config($environment);

            try {
                $response = Http::withToken($cfg['token'])
                    ->timeout(self::TIMEOUT)
                    ->acceptJson()
                    ->get($this->endpoint($environment, 'manage/v1/clients'), [
                        'merchant_id' => $merchantId,
                        'company_id'  => $companyId,
                    ]);
            } catch (\Throwable $e) {
                Log::error('MerchantApi list request failed', [
                    'environment' => $environment,
                    'merchant_id' => $merchantId,
                    'message'     => $e->getMessage(),
                ]);
                throw new RuntimeException('Could not load API keys. Please try again.');
            }

            if ($response->failed()) {
                $this->logUpstreamFailure('list', $environment, $merchantId, $response);
                throw new RuntimeException($this->safeError($response, 'Could not load API keys.'));
            }

            foreach ((array) $response->json('data', []) as $client) {
                $client['environment'] = $client['environment'] ?? $environment;
                $clients[] = $client;
            }
        }

        return $clients;
    }

    /**
     * Create a key in a specific environment. Returns
     * ['token' => '<shown once>', 'client' => [...]]. Caps scopes to the
     * valid set and refuses environments that are not configured.
     */
    public function create(
        int $merchantId,
        int $companyId,
        string $name,
        string $environment,
        array $scopes,
        ?string $expiresAt = null
    ): array {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        $payload = [
            'merchant_id' => $merchantId,
            'company_id'  => $companyId,
            'name'        => $name,
            'environment' => $environment,
            'scopes'      => $this->capScopes($scopes),
        ];
        if ($expiresAt) {
            $payload['expires_at'] = $expiresAt;
        }

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->post($this->endpoint($environment, 'manage/v1/clients'), $payload);
        } catch (\Throwable $e) {
            Log::error('MerchantApi create request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not create the API key. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('create', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not create the API key.'));
        }

        $data   = (array) $response->json('data', []);
        $token  = $data['token'] ?? null;
        $client = (array) ($data['client'] ?? []);

        if (! $token || ! $client) {
            Log::error('MerchantApi create returned an unexpected shape', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
            ]);
            throw new RuntimeException('Could not create the API key. Please try again.');
        }

        $client['environment'] = $client['environment'] ?? $environment;

        return ['token' => $token, 'client' => $client];
    }

    /**
     * Revoke a key by uuid in a given environment. Idempotent from the
     * caller's point of view: any non-2xx is normalised to an exception.
     */
    public function revoke(int $merchantId, int $companyId, string $environment, string $uuid): void
    {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->post($this->endpoint($environment, "manage/v1/clients/{$uuid}/revoke"), [
                    'merchant_id' => $merchantId,
                    'company_id'  => $companyId,
                ]);
        } catch (\Throwable $e) {
            Log::error('MerchantApi revoke request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'uuid'        => $uuid,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not revoke the API key. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('revoke', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not revoke the API key.'));
        }
    }

    /**
     * Which of live/test are fully configured (both url and token set).
     *
     * @return array<int, string>
     */
    public function availableEnvironments(): array
    {
        return array_values(array_filter(
            ['live', 'test'],
            fn (string $env) => $this->isConfigured($env),
        ));
    }

    // ---- helpers ------------------------------------------------------------

    private function isConfigured(string $environment): bool
    {
        $cfg = (array) config("merchant_api.{$environment}", []);

        return ! empty($cfg['url']) && ! empty($cfg['token']);
    }

    /** @return array{url:string, token:string} */
    private function config(string $environment): array
    {
        return (array) config("merchant_api.{$environment}");
    }

    private function endpoint(string $environment, string $path): string
    {
        $base = rtrim((string) $this->config($environment)['url'], '/');

        return $base.'/'.ltrim($path, '/');
    }

    /** Keep only recognised scopes (and de-duplicate). */
    private function capScopes(array $scopes): array
    {
        return array_values(array_intersect(
            array_values(array_unique($scopes)),
            self::VALID_SCOPES,
        ));
    }

    /**
     * Pull a safe, human message out of the façade error envelope
     * ({"success":false,"error":{"code","message","details"}}) without
     * leaking internals; fall back to the given default.
     */
    private function safeError(\Illuminate\Http\Client\Response $response, string $default): string
    {
        $message = $response->json('error.message');

        return is_string($message) && $message !== '' ? $message : $default;
    }

    private function logUpstreamFailure(
        string $op,
        string $environment,
        int $merchantId,
        \Illuminate\Http\Client\Response $response
    ): void {
        Log::error("MerchantApi {$op} returned an error", [
            'environment' => $environment,
            'merchant_id' => $merchantId,
            'status'      => $response->status(),
            'error_code'  => $response->json('error.code'),
            'error'       => $response->json('error.message'),
        ]);
    }
}
