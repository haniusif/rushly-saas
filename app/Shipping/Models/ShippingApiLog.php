<?php

namespace App\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingApiLog extends Model
{
    protected $table = 'shipping_api_logs';

    public $timestamps = false;       // Only `created_at`, set via default at DB level

    protected $fillable = [
        'company_id',
        'connection_id',
        'provider_code',
        'endpoint',
        'method',
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'duration_ms',
        'error',
        'created_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'created_at'      => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ShippingConnection::class, 'connection_id');
    }
}
