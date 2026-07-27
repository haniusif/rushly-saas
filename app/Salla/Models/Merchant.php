<?php

namespace App\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Merchant extends Model
{
    protected $table = 'salla_merchants';

    protected $fillable = [
        'salla_merchant_id',
        'store_name',
        'store_domain',
        'owner_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'rushly_merchant_id',
        'rushly_merchant_token',
        'rushly_shop_id',
        'installed',
        'uninstalled_at',
        'scopes',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'uninstalled_at'   => 'datetime',
        'installed'        => 'boolean',
        'scopes'           => 'array',
    ];

    protected $hidden = ['access_token', 'refresh_token', 'rushly_merchant_token'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'salla_merchant_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(Settings::class, 'salla_merchant_id');
    }

    public function tokenIsExpired(): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->isPast();
    }

    /**
     * True when the access token has already expired OR expires within
     * $bufferSeconds. Used by ApiClient to refresh proactively so an
     * in-flight request doesn't race the expiry.
     */
    public function tokenExpiresSoon(int $bufferSeconds = 60): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->subSeconds($bufferSeconds)->isPast();
    }
}
