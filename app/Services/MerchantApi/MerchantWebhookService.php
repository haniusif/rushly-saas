<?php

namespace App\Services\MerchantApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client for the rushly-api façade "webhook management API".
 *
 * Webhook endpoints are NOT stored in rushly-saas — they live in rushly-api.
 * This service is the HTTP bridge the merchant dashboard uses to register /
 * list / update / rotate / delete a merchant's webhook endpoints, and to read
 * their recent delivery log, across the "live" and "test" environments (each
 * its own façade base URL + bearer token, from config/merchant_api.php — the
 * SAME per-environment url+token as the API-keys management calls).
 *
 * The caller (controller) resolves merchant_id / company_id from the
 * authenticated merchant; those values are ALWAYS sent as passed in and are
 * NEVER taken from client input.
 */
class MerchantWebhookService
{
    /** The only events the façade accepts. Anything else is dropped. */
    public const VALID_EVENTS = [
        'shipment.created',
        'shipment.pickup_scheduled',
        'shipment.picked_up',
        'shipment.at_hub',
        'shipment.in_transit',
        'shipment.out_for_delivery',
        'shipment.delivered',
        'shipment.delivery_failed',
        'shipment.returning',
        'shipment.returned',
        'shipment.cancelled',
    ];

    /** HTTP timeout (seconds) for every façade call. */
    private const TIMEOUT = 15;

    /**
     * List every webhook endpoint this merchant has, across ALL configured
     * environments (so the merchant sees both live and test endpoints in one
     * table). Each returned endpoint array is tagged with its `environment`
     * for the UI.
     */
    public function list(int $merchantId, int $companyId): array
    {
        $endpoints = [];

        foreach ($this->availableEnvironments() as $environment) {
            $cfg = $this->config($environment);

            try {
                $response = Http::withToken($cfg['token'])
                    ->timeout(self::TIMEOUT)
                    ->acceptJson()
                    ->get($this->endpoint($environment, 'manage/v1/webhooks'), [
                        'merchant_id' => $merchantId,
                        'company_id'  => $companyId,
                    ]);
            } catch (\Throwable $e) {
                Log::error('MerchantWebhook list request failed', [
                    'environment' => $environment,
                    'merchant_id' => $merchantId,
                    'message'     => $e->getMessage(),
                ]);
                throw new RuntimeException('Could not load webhooks. Please try again.');
            }

            if ($response->failed()) {
                $this->logUpstreamFailure('list', $environment, $merchantId, $response);
                throw new RuntimeException($this->safeError($response, 'Could not load webhooks.'));
            }

            foreach ((array) $response->json('data', []) as $endpoint) {
                $endpoint['environment'] = $endpoint['environment'] ?? $environment;
                $endpoints[] = $endpoint;
            }
        }

        return $endpoints;
    }

    /**
     * Register an endpoint in a specific environment. Returns
     * ['secret' => '<shown once>', 'endpoint' => [...]]. Caps events to the
     * valid set and refuses environments that are not configured.
     */
    public function create(
        int $merchantId,
        int $companyId,
        string $environment,
        string $url,
        array $events,
        ?string $description = null
    ): array {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        $payload = [
            'merchant_id' => $merchantId,
            'company_id'  => $companyId,
            'url'         => $url,
            'events'      => $this->capEvents($events),
        ];
        if ($description !== null && $description !== '') {
            $payload['description'] = $description;
        }

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->post($this->endpoint($environment, 'manage/v1/webhooks'), $payload);
        } catch (\Throwable $e) {
            Log::error('MerchantWebhook create request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not create the webhook. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('create', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not create the webhook.'));
        }

        $data     = (array) $response->json('data', []);
        $secret   = $data['secret'] ?? null;
        $endpoint = (array) ($data['endpoint'] ?? []);

        if (! $secret || ! $endpoint) {
            Log::error('MerchantWebhook create returned an unexpected shape', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
            ]);
            throw new RuntimeException('Could not create the webhook. Please try again.');
        }

        $endpoint['environment'] = $endpoint['environment'] ?? $environment;

        return ['secret' => $secret, 'endpoint' => $endpoint];
    }

    /**
     * Update an endpoint by uuid in a given environment. Only the whitelisted
     * fields (url, events, is_active) are forwarded; events are capped to the
     * valid set. Returns the updated endpoint, tagged with its environment.
     */
    public function update(
        int $merchantId,
        int $companyId,
        string $environment,
        string $uuid,
        array $fields
    ): array {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        $payload = [
            'merchant_id' => $merchantId,
            'company_id'  => $companyId,
        ];
        if (array_key_exists('url', $fields)) {
            $payload['url'] = $fields['url'];
        }
        if (array_key_exists('events', $fields)) {
            $payload['events'] = $this->capEvents((array) $fields['events']);
        }
        if (array_key_exists('is_active', $fields)) {
            $payload['is_active'] = (bool) $fields['is_active'];
        }

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->post($this->endpoint($environment, "manage/v1/webhooks/{$uuid}"), $payload);
        } catch (\Throwable $e) {
            Log::error('MerchantWebhook update request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'uuid'        => $uuid,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not update the webhook. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('update', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not update the webhook.'));
        }

        $endpoint = (array) $response->json('data.endpoint', $response->json('data', []));
        $endpoint['environment'] = $endpoint['environment'] ?? $environment;

        return $endpoint;
    }

    /**
     * Rotate an endpoint's signing secret. Returns
     * ['secret' => '<shown once>', 'endpoint' => [...]].
     */
    public function rotateSecret(int $merchantId, int $companyId, string $environment, string $uuid): array
    {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->post($this->endpoint($environment, "manage/v1/webhooks/{$uuid}/rotate-secret"), [
                    'merchant_id' => $merchantId,
                    'company_id'  => $companyId,
                ]);
        } catch (\Throwable $e) {
            Log::error('MerchantWebhook rotate-secret request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'uuid'        => $uuid,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not rotate the webhook secret. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('rotate-secret', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not rotate the webhook secret.'));
        }

        $data     = (array) $response->json('data', []);
        $secret   = $data['secret'] ?? null;
        $endpoint = (array) ($data['endpoint'] ?? []);

        if (! $secret || ! $endpoint) {
            Log::error('MerchantWebhook rotate-secret returned an unexpected shape', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
            ]);
            throw new RuntimeException('Could not rotate the webhook secret. Please try again.');
        }

        $endpoint['environment'] = $endpoint['environment'] ?? $environment;

        return ['secret' => $secret, 'endpoint' => $endpoint];
    }

    /**
     * Delete (soft-disable) an endpoint by uuid in a given environment.
     * Idempotent from the caller's point of view: any non-2xx is normalised
     * to an exception.
     */
    public function delete(int $merchantId, int $companyId, string $environment, string $uuid): void
    {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->delete($this->endpoint($environment, "manage/v1/webhooks/{$uuid}"), [
                    'merchant_id' => $merchantId,
                    'company_id'  => $companyId,
                ]);
        } catch (\Throwable $e) {
            Log::error('MerchantWebhook delete request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'uuid'        => $uuid,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not delete the webhook. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('delete', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not delete the webhook.'));
        }
    }

    /**
     * Recent delivery log for one endpoint in a given environment.
     *
     * @return array<int, array<string, mixed>>
     */
    public function deliveries(int $merchantId, int $companyId, string $environment, string $uuid): array
    {
        if (! $this->isConfigured($environment)) {
            throw new RuntimeException("The \"{$environment}\" environment is not configured.");
        }

        $cfg = $this->config($environment);

        try {
            $response = Http::withToken($cfg['token'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->get($this->endpoint($environment, "manage/v1/webhooks/{$uuid}/deliveries"), [
                    'merchant_id' => $merchantId,
                    'company_id'  => $companyId,
                ]);
        } catch (\Throwable $e) {
            Log::error('MerchantWebhook deliveries request failed', [
                'environment' => $environment,
                'merchant_id' => $merchantId,
                'uuid'        => $uuid,
                'message'     => $e->getMessage(),
            ]);
            throw new RuntimeException('Could not load webhook deliveries. Please try again.');
        }

        if ($response->failed()) {
            $this->logUpstreamFailure('deliveries', $environment, $merchantId, $response);
            throw new RuntimeException($this->safeError($response, 'Could not load webhook deliveries.'));
        }

        return (array) $response->json('data', []);
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

    /** Keep only recognised events (and de-duplicate). */
    private function capEvents(array $events): array
    {
        return array_values(array_intersect(
            array_values(array_unique($events)),
            self::VALID_EVENTS,
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
        Log::error("MerchantWebhook {$op} returned an error", [
            'environment' => $environment,
            'merchant_id' => $merchantId,
            'status'      => $response->status(),
            'error_code'  => $response->json('error.code'),
            'error'       => $response->json('error.message'),
        ]);
    }
}
