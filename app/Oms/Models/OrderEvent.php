<?php

namespace App\Oms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail per Order. Written by OrderService and by
 * downstream listeners as the order moves through its lifecycle.
 * Never updated after insert — a status change becomes a new row.
 */
class OrderEvent extends Model
{
    protected $table = 'order_events';

    protected $fillable = [
        'order_id',
        'company_id',
        'event_type',
        'payload',
        'user_id',
        'occurred_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];

    // Canonical event types written by the OMS itself. Downstream modules
    // (Phase 6 Fulfillment, Phase 7 Inventory) may add their own.
    const TYPE_CREATED             = 'created';
    const TYPE_UPDATED             = 'updated';
    const TYPE_STATUS_CHANGED      = 'status_changed';
    const TYPE_PAYMENT_STATUS      = 'payment_status_changed';
    const TYPE_FULFILLMENT_STATUS  = 'fulfillment_status_changed';
    const TYPE_PARCEL_LINKED       = 'parcel_linked';
    const TYPE_CANCELLED           = 'cancelled';
    const TYPE_NOTE                = 'note';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
