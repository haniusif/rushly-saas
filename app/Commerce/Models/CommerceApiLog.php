<?php

namespace App\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommerceApiLog extends Model
{
    protected $table = 'commerce_api_logs';

    public $timestamps = false;       // Only `created_at`, set via column default

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
        return $this->belongsTo(CommerceConnection::class, 'connection_id');
    }
}
