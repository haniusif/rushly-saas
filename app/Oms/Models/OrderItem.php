<?php

namespace App\Oms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'sort_order',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'total_price',
        'currency',
        'remote_product_id',
        'remote_variant_id',
        'extra',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity'    => 'integer',
        'sort_order'  => 'integer',
        'extra'       => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
