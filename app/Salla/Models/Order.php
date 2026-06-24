<?php

namespace App\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'salla_orders';

    protected $fillable = [
        'salla_merchant_id',
        'salla_order_id',
        'reference_id',
        'status',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'total',
        'currency',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'total'   => 'decimal:2',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'salla_merchant_id');
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class, 'salla_order_id');
    }
}
