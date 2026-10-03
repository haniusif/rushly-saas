<?php

namespace App\Fulfillment\Models;

use App\Oms\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recorded decision to fulfill an OMS Order via a specific strategy.
 * Sits above `shipments` (courier hand-off) and `wms_fulfillments`
 * (warehouse ops) — it's the strategy-level bookkeeping that connects
 * an Order to whichever downstream path was chosen.
 *
 * Phase 6 MVP: exactly one Fulfillment per Order. Retries create new
 * rows so the audit trail stays clear ("attempt 1 failed with X on Mon,
 * attempt 2 succeeded on Tue"). Phase 6.5+ may add split fulfillment
 * (one order → two fulfillments, each covering a subset of items).
 */
class Fulfillment extends Model
{
    protected $table = 'fulfillments';

    protected $fillable = [
        'company_id',
        'order_id',
        'strategy',
        'route_id',
        'status',
        'shipping_connection_id',
        'wms_fulfillment_id',
        'parcel_id',
        'hub_id',
        'external_reference',
        'payload',
        'last_error',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload'       => 'array',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'failed_at'     => 'datetime',
    ];

    const STATUS_PENDING     = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_FAILED      = 'failed';
    const STATUS_CANCELLED   = 'cancelled';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(FulfillmentRoute::class, 'route_id');
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Backend\Parcel::class, 'parcel_id');
    }

    public function scopeCompanywise(Builder $query): Builder
    {
        $id = settings()->id ?? null;
        return $query->where('company_id', $id);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED,
        ], true);
    }
}
