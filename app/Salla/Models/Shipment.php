<?php

namespace App\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $table = 'salla_shipments';

    protected $fillable = [
        'salla_order_id',
        'rushly_tracking_number',
        'salla_shipment_id',
        'awb_number',
        'label_url',
        'status',
        'last_rushly_status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'salla_order_id');
    }
}
