<?php

namespace App\Commerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceConnection extends Model
{
    protected $table = 'commerce_connections';

    protected $fillable = [
        'company_id',
        'provider_id',
        'connection_name',
        'remote_store_id',
        'domain',
        'merchant_id',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'token_expires_at',
        'api_key_encrypted',
        'api_secret_encrypted',
        'webhook_secret_encrypted',
        'settings',
        'status',
        'is_default',
        'last_tested_at',
        'last_sync_at',
        'last_event_at',
    ];

    protected $casts = [
        'settings'                 => 'array',
        'is_default'               => 'boolean',
        'token_expires_at'         => 'datetime',
        'last_tested_at'           => 'datetime',
        'last_sync_at'             => 'datetime',
        'last_event_at'            => 'datetime',
        // Encrypted casts: ciphertext at rest, plaintext on read. Survives
        // APP_KEY rotation via `php artisan key:rotate`.
        'access_token_encrypted'   => 'encrypted',
        'refresh_token_encrypted'  => 'encrypted',
        'api_key_encrypted'        => 'encrypted',
        'api_secret_encrypted'     => 'encrypted',
        'webhook_secret_encrypted' => 'encrypted',
    ];

    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
        'api_key_encrypted',
        'api_secret_encrypted',
        'webhook_secret_encrypted',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(CommerceProvider::class, 'provider_id');
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(CommerceApiLog::class, 'connection_id');
    }

    /**
     * Tenant scope — matches the codebase-wide `companywise` convention.
     * Pulls company_id from settings()->id rather than auth() so it works
     * in queue / scheduler context where there's no user. Same approach
     * as ShippingConnection::scopeCompanywise().
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
}
