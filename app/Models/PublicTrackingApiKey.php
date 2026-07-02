<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Per-tenant API key that grants read-only access to the public tracking
 * endpoint. Plaintext is shown to the operator once at creation time; only
 * the sha256 hash is persisted. Verification hashes the presented value
 * and looks it up by `key_hash` UNIQUE index.
 */
class PublicTrackingApiKey extends Model
{
    protected $table = 'public_tracking_api_keys';

    protected $fillable = [
        'company_id',
        'name',
        'key_hash',
        'key_prefix',
        'allowed_origins',
        'is_active',
        'last_used_at',
        'request_count',
        'created_by',
    ];

    protected $casts = [
        'allowed_origins' => 'array',
        'is_active'       => 'boolean',
        'last_used_at'    => 'datetime',
        'request_count'   => 'integer',
    ];

    protected $hidden = ['key_hash'];

    /**
     * Generate a new plaintext key + its storage form. Returns
     * [plaintext, hash, prefix]. Caller stores hash+prefix, shows
     * plaintext to the user exactly once.
     *
     * Format: rxk_<32-hex> — the "rxk_" prefix makes leaked keys
     * grep-able and unmistakable in logs.
     */
    public static function generate(): array
    {
        $plaintext = 'rxk_' . bin2hex(random_bytes(16)); // 4 + 32 = 36 chars
        $hash      = hash('sha256', $plaintext);
        $prefix    = substr($plaintext, 0, 12);
        return [$plaintext, $hash, $prefix];
    }

    /**
     * Look up an active key by its plaintext form. Returns null if
     * no active key matches. Timing-safe by comparing the sha256
     * hash (all keys hash to the same length).
     */
    public static function findByPlaintext(string $plaintext): ?self
    {
        if ($plaintext === '' || ! Str::startsWith($plaintext, 'rxk_')) {
            return null;
        }
        $hash = hash('sha256', $plaintext);
        return static::query()
            ->where('key_hash', $hash)
            ->where('is_active', true)
            ->first();
    }

    public function scopeCompanywise($query)
    {
        $companyId = optional(settings())->id;
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return $query;
    }
}
