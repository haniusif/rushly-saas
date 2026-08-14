<?php

namespace App\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingProvider extends Model
{
    protected $table = 'shipping_providers';

    protected $fillable = [
        'code', 'name', 'logo_url', 'status', 'supports',
    ];

    protected $casts = [
        'supports' => 'array',
    ];

    public function connections(): HasMany
    {
        return $this->hasMany(ShippingConnection::class, 'provider_id');
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
