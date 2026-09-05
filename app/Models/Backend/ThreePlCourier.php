<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Which delivery_man row stands in for a 3PL carrier, per tenant.
 *
 * See the create migration for why the legacy carriers need this at all: they
 * have no per-tenant connection row to carry it.
 */
class ThreePlCourier extends Model
{
    protected $table = 'three_pl_couriers';

    protected $fillable = [
        'company_id',
        'carrier_code',
        'delivery_man_id',
    ];

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    /**
     * Tenant scope - matches the codebase-wide 'companywise' convention.
     * Uses settings()->id so it works in HTTP and queue/scheduler contexts.
     */
    public function scopeCompanywise(Builder $query): Builder
    {
        return $query->where('company_id', settings()->id ?? null);
    }
}
