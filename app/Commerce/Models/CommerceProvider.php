<?php

namespace App\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommerceProvider extends Model
{
    protected $table = 'commerce_providers';

    protected $fillable = [
        'code', 'name', 'logo_url', 'status', 'supports',
    ];

    protected $casts = [
        'supports' => 'array',
    ];

    public function connections(): HasMany
    {
        return $this->hasMany(CommerceConnection::class, 'provider_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function supports(string $capability): bool
    {
        return is_array($this->supports) && in_array($capability, $this->supports, true);
    }
}
