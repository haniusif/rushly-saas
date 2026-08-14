<?php

namespace App\Shipping\DTOs;

use App\Shipping\Models\ShippingConnection;

/**
 * Connection state passed into provider calls. Built either from an Eloquent
 * ShippingConnection row (most code paths) or directly during a pre-save test.
 *
 * Read-only by convention: `withX()` methods return new instances. Avoids
 * accidental mutation while the same DTO is passed through multiple layers.
 */
final class ConnectionDTO
{
    public function __construct(
        public readonly ?int    $id,
        public readonly int     $companyId,
        public readonly string  $providerCode,
        public readonly string  $connectionName,
        public readonly ?string $remoteCompanyId,
        public readonly ?string $domain,
        public readonly ?string $email,
        public readonly ?string $password,           // plaintext — supplied by caller, never persisted as-is
        public readonly array   $settings = [],
    ) {}

    public static function fromModel(ShippingConnection $c): self
    {
        return new self(
            id:              $c->id,
            companyId:       (int) $c->company_id,
            providerCode:    $c->provider->code,
            connectionName:  (string) $c->connection_name,
            remoteCompanyId: $c->remote_company_id,
            domain:          $c->domain,
            email:           $c->email,
            password:        $c->password_encrypted, // 'encrypted' cast returns plaintext
            settings:        is_array($c->settings) ? $c->settings : [],
        );
    }

    public function withRemoteCompanyId(?string $id): self
    {
        return new self(
            $this->id, $this->companyId, $this->providerCode, $this->connectionName,
            $id, $this->domain, $this->email, $this->password, $this->settings,
        );
    }

    public function withCredentials(?string $email, ?string $password): self
    {
        return new self(
            $this->id, $this->companyId, $this->providerCode, $this->connectionName,
            $this->remoteCompanyId, $this->domain, $email, $password, $this->settings,
        );
    }

    public function setting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
}
