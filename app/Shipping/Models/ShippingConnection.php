<?php

namespace App\Shipping\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingConnection extends Model
{
    protected $table = 'shipping_connections';

    protected $fillable = [
        'company_id',
        'provider_id',
        'connection_name',
        'remote_company_id',
        'domain',
        'email',
        'password_encrypted',
        'settings',
        'status',
        'is_default',
        'last_tested_at',
        'last_sync_at',
    ];

    protected $casts = [
        'settings'           => 'array',
        'is_default'         => 'boolean',
        'last_tested_at'     => 'datetime',
        'last_sync_at'       => 'datetime',
        // Laravel's `encrypted` cast handles read/write transparently. The
        // ciphertext sits in the column; decrypted plaintext is exposed via
        // the attribute. Survives APP_KEY rotation via `php artisan key:rotate`.
        'password_encrypted' => 'encrypted',
    ];

    protected $hidden = ['password_encrypted'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'provider_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'connection_id');
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(ShippingApiLog::class, 'connection_id');
    }

    /**
     * Tenant scope — matches the codebase-wide `companywise` convention.
     * Pulls company_id from settings()->id rather than auth() so it works in
     * queue / scheduler context where there's no user.
     */
    public function scopeCompanywise(Builder $query): Builder
    {
        $id = settings()->id ?? null;
        return $query->where('company_id', $id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Is this connection usable for shipping?
     *
     * Driven by the provider's field spec in config/shipping.php — the same
     * declaration the connection form and its validation are built from — so
     * "required to save" and "ready to ship" cannot drift apart.
     *
     * It used to hardcode remote_company_id + email + password, which is
     * Logestechs' credential shape. EcoExpress authenticates with an OAuth
     * client id/secret and an account number and has none of those three, so
     * it was permanently "not ready" and every assign failed with
     * "Connection is not ready (status / credentials / remote_company_id
     * missing)" — a message describing fields that provider does not use.
     */
    public function isReady(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $code = $this->provider?->code;
        $spec = $code ? config('shipping.providers.' . $code . '.form') : null;

        // No spec: fall back to the original check rather than declaring a
        // provider ready on no evidence.
        if (! is_array($spec) || $spec === []) {
            return filled($this->remote_company_id)
                && filled($this->email)
                && filled($this->password_encrypted);
        }

        foreach ($spec as $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }

            $name  = (string) ($field['name'] ?? '');
            $value = str_starts_with($name, 'settings.')
                ? (is_array($this->settings) ? ($this->settings[substr($name, 9)] ?? null) : null)
                : ($name === 'password' ? $this->password_encrypted : $this->{$name} ?? null);

            if (blank($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Accessor for the decrypted password. Use sparingly — only in the
     * provider's HTTP layer immediately before the outbound call.
     */
    public function getPasswordAttribute(): ?string
    {
        return $this->password_encrypted;
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password_encrypted'] = $value;
    }
}
