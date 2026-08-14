<?php

namespace App\Commerce\Services;

use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\DTOs\TestResultDTO;
use App\Commerce\Exceptions\ConnectionTestFailedException;
use App\Commerce\Factory\CommerceProviderFactory;
use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\CommerceProvider;
use App\Commerce\Repositories\CommerceConnectionRepository;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle of a tenant's commerce connection: create, test, set default,
 * deactivate, delete. Business logic lives here; controllers stay thin.
 *
 * Mirrors App\Shipping\Services\ConnectionService intentionally so the two
 * modules read side-by-side. Differences are forced by the wider auth-shape
 * variance on the commerce side (OAuth tokens, static API keys, webhook
 * HMAC secret) — each kept in its own column rather than collapsed into a
 * single password field.
 */
class ConnectionService
{
    public function __construct(
        private readonly CommerceProviderFactory $factory,
        private readonly CommerceConnectionRepository $repo,
    ) {}

    /**
     * Validate a candidate connection. Always returns a TestResultDTO so
     * callers can render the diagnostic uniformly; doesn't throw on
     * logical failure (caller can decide how to surface).
     */
    public function test(CommerceConnectionDTO $dto): TestResultDTO
    {
        $provider = $this->factory->make($dto->providerCode);
        return $provider->testConnection($dto);
    }

    /**
     * End-to-end "add integration" path:
     *   1. Run a connection test with the supplied creds.
     *   2. On success → persist (encrypted creds, set default if first for this provider).
     *   3. On failure → throw with the diagnostic so the controller can return 422.
     *
     * For OAuth providers in Phase 2 the access/refresh tokens are pasted
     * manually by the admin. Phase 3 will add the install flow that
     * populates them from a callback.
     */
    public function store(int $companyId, string $providerCode, array $input): CommerceConnection
    {
        $provider = CommerceProvider::query()->where('code', $providerCode)->firstOrFail();

        $candidate = $this->candidateFromInput($companyId, $providerCode, $input);

        $result = $this->test($candidate);
        if (! $result->ok) {
            throw new ConnectionTestFailedException($result->message, $result->diagnostics);
        }

        return DB::transaction(function () use ($candidate, $provider, $companyId) {
            $isFirst = ! CommerceConnection::query()
                ->where('company_id', $companyId)
                ->where('provider_id', $provider->id)
                ->exists();

            $conn = new CommerceConnection();
            $conn->company_id               = $companyId;
            $conn->provider_id              = $provider->id;
            $conn->connection_name          = $candidate->connectionName;
            $conn->remote_store_id          = $candidate->remoteStoreId;
            $conn->domain                   = $candidate->domain;
            $conn->merchant_id              = $candidate->merchantId;
            // 'encrypted' casts on the model — plaintext in, ciphertext on disk.
            $conn->access_token_encrypted   = $candidate->accessToken;
            $conn->refresh_token_encrypted  = $candidate->refreshToken;
            $conn->token_expires_at         = $candidate->tokenExpiresAt;
            $conn->api_key_encrypted        = $candidate->apiKey;
            $conn->api_secret_encrypted     = $candidate->apiSecret;
            $conn->webhook_secret_encrypted = $candidate->webhookSecret;
            $conn->settings                 = $candidate->settings;
            $conn->status                   = 'active';
            $conn->is_default               = $isFirst;
            $conn->last_tested_at           = now();
            $conn->save();

            return $conn->load('provider');
        });
    }

    public function update(CommerceConnection $conn, array $input): CommerceConnection
    {
        $conn->connection_name = trim((string) ($input['connection_name'] ?? $conn->connection_name)) ?: $conn->connection_name;

        if (array_key_exists('domain', $input)) {
            $conn->domain = trim((string) $input['domain']) ?: null;
        }
        if (array_key_exists('remote_store_id', $input)) {
            $conn->remote_store_id = trim((string) $input['remote_store_id']) ?: null;
        }
        if (array_key_exists('merchant_id', $input)) {
            $mid = $input['merchant_id'];
            $conn->merchant_id = ($mid === null || $mid === '') ? null : (int) $mid;
        }

        // Encrypted secret fields: only overwrite when caller supplies a
        // non-empty, non-mask value. The mask sentinel `••••••` and `__keep__`
        // both mean "leave the stored ciphertext alone". This mirrors how
        // ShippingConnectionsController treats the password field.
        $this->maybeUpdateSecret($conn, 'access_token_encrypted',   $input['access_token']   ?? null);
        $this->maybeUpdateSecret($conn, 'refresh_token_encrypted',  $input['refresh_token']  ?? null);
        $this->maybeUpdateSecret($conn, 'api_key_encrypted',        $input['api_key']        ?? null);
        $this->maybeUpdateSecret($conn, 'api_secret_encrypted',     $input['api_secret']     ?? null);
        $this->maybeUpdateSecret($conn, 'webhook_secret_encrypted', $input['webhook_secret'] ?? null);

        if (array_key_exists('token_expires_at', $input)) {
            $val = trim((string) $input['token_expires_at']);
            $conn->token_expires_at = $val === '' ? null : $val;
        }
        if (array_key_exists('settings', $input)) {
            $conn->settings = (array) $input['settings'];
        }
        if (array_key_exists('status', $input)) {
            $conn->status = (string) $input['status'];
        }
        $conn->save();
        return $conn->load('provider');
    }

    public function setDefault(CommerceConnection $conn): void
    {
        $this->repo->setDefault($conn);
    }

    public function deactivate(CommerceConnection $conn): void
    {
        $conn->status = 'paused';
        $conn->save();
    }

    /**
     * Build the candidate DTO from raw form input. Centralizes the
     * value-normalization rules so create/test paths can't drift.
     */
    public function candidateFromInput(int $companyId, string $providerCode, array $input): CommerceConnectionDTO
    {
        return new CommerceConnectionDTO(
            id:              null,
            companyId:       $companyId,
            providerCode:    $providerCode,
            connectionName:  trim((string) ($input['connection_name'] ?? '')) ?: 'Default',
            remoteStoreId:   trim((string) ($input['remote_store_id'] ?? '')) ?: null,
            domain:          trim((string) ($input['domain'] ?? '')) ?: null,
            merchantId:      isset($input['merchant_id']) && $input['merchant_id'] !== '' ? (int) $input['merchant_id'] : null,
            accessToken:     $this->stringOrNull($input['access_token']   ?? null),
            refreshToken:    $this->stringOrNull($input['refresh_token']  ?? null),
            tokenExpiresAt:  $this->stringOrNull($input['token_expires_at'] ?? null),
            apiKey:          $this->stringOrNull($input['api_key']        ?? null),
            apiSecret:       $this->stringOrNull($input['api_secret']     ?? null),
            webhookSecret:   $this->stringOrNull($input['webhook_secret'] ?? null),
            settings:        (array) ($input['settings'] ?? []),
        );
    }

    private function maybeUpdateSecret(CommerceConnection $conn, string $column, $value): void
    {
        if ($value === null) return;
        $v = (string) $value;
        if ($v === '' || $v === '__keep__' || str_starts_with($v, '••')) return;
        $conn->{$column} = $v;
    }

    private function stringOrNull($value): ?string
    {
        if ($value === null) return null;
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }
}
