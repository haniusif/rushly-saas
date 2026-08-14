<?php

namespace App\Fulfillment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A declarative routing rule. All condition columns are AND'd; nulls
 * mean "don't filter on this column". Priority is ASC (lower = wins
 * ties). FulfillmentRouter evaluates every active route against an
 * Order and returns the first match — no priority ties are broken
 * deterministically (add explicit priority values in production).
 */
class FulfillmentRoute extends Model
{
    protected $table = 'fulfillment_routes';

    protected $fillable = [
        'company_id',
        'name',
        'priority',
        'is_active',
        'merchant_id',
        'source_provider_code',
        'shipping_city_id',
        'shipping_country',
        'min_total',
        'max_total',
        'is_cod',
        'strategy',
        'shipping_connection_id',
        'hub_id',
        'notes',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_cod'     => 'boolean',
        'priority'   => 'integer',
        'min_total'  => 'decimal:2',
        'max_total'  => 'decimal:2',
    ];

    public function scopeCompanywise(Builder $query): Builder
    {
        $id = settings()->id ?? null;
        return $query->where('company_id', $id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
