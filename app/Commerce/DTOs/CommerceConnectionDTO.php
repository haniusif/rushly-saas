<?php

namespace App\Commerce\DTOs;

use App\Commerce\Models\CommerceConnection;

/**
 * Connection state passed into provider calls. Built either from an
 * Eloquent CommerceConnection row (most code paths) or directly during a
 * pre-save connection-test.
 *
 * Read-only by convention: `withX()` methods return new instances. Avoids
 * accidental mutation while the same DTO is passed through OAuth refresh,
 * test, and outbound HTTP layers.
 *
 * Token / secret fields are plaintext on this DTO — the persistence layer
 * encrypts on save via the model's `encrypted` casts.
 */
final class CommerceConnectionDTO
{
    public function __construct(
        public readonly ?int    $id,
        public readonly int     $companyId,
        public readonly string  $providerCode,
        public readonly string  $connectionName,
        public readonly ?string $remoteStoreId,
        public readonly ?string $domain,
        public readonly ?int    $merchantId,
        public readonly ?string $accessToken,
        public readonly ?string $refreshToken,
        public readonly ?string $tokenExpiresAt,        // ISO 8601
        public readonly ?string $apiKey,
        public readonly ?string $apiSecret,
        public readonly ?string $webhookSecret,
        public readonly array   $settings = [],
    ) {}

    public static function fromModel(CommerceConnection $c): self
    {
        return new self(
            id:              $c->id,
            companyId:       (int) $c->company_id,
            providerCode:    $c->provider->code,
            connectionName:  (string) $c->connection_name,
            remoteStoreId:   $c->remote_store_id,
            domain:          $c->domain,
            merchantId:      $c->merchant_id !== null ? (int) $c->merchant_id : null,
            accessToken:     $c->access_token_encrypted,       // 'encrypted' cast returns plaintext
            refreshToken:    $c->refresh_token_encrypted,
            tokenExpiresAt:  optional($c->token_expires_at)->toIso8601String(),
            apiKey:          $c->api_key_encrypted,
            apiSecret:       $c->api_secret_encrypted,
            webhookSecret:   $c->webhook_secret_encrypted,
            settings:        is_array($c->settings) ? $c->settings : [],
        );
    }

    public function withTokens(?string $access, ?string $refresh, ?string $expiresAt): self
    {
        return new self(
            $this->id, $this->companyId, $this->providerCode, $this->connectionName,
            $this->remoteStoreId, $this->domain, $this->merchantId,
            $access, $refresh, $expiresAt,
            $this->apiKey, $this->apiSecret, $this->webhookSecret, $this->settings,
        );
    }

    public function withRemoteStoreId(?string $id): self
    {
        return new self(
            $this->id, $this->companyId, $this->providerCode, $this->connectionName,
            $id, $this->domain, $this->merchantId,
            $this->accessToken, $this->refreshToken, $this->tokenExpiresAt,
            $this->apiKey, $this->apiSecret, $this->webhookSecret, $this->settings,
        );
    }

    public function withSettings(array $settings): self
    {
        return new self(
            $this->id, $this->companyId, $this->providerCode, $this->connectionName,
            $this->remoteStoreId, $this->domain, $this->merchantId,
            $this->accessToken, $this->refreshToken, $this->tokenExpiresAt,
            $this->apiKey, $this->apiSecret, $this->webhookSecret,
            array_merge($this->settings, $settings),
        );
    }

    public function setting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
}
