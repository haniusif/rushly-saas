<?php

namespace App\Shipping\Models;

use App\Models\Backend\Parcel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $table = 'shipments';

    protected $fillable = [
        'company_id',
        'parcel_id',
        'connection_id',
        'remote_shipment_id',
        'awb_number',
        'awb_pdf_url',
        'current_status_raw',
        'current_status_local',
        'last_status_at',
        'last_synced_at',
        'last_sync_error',
        'state',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'last_status_at'       => 'datetime',
        'last_synced_at'       => 'datetime',
        'current_status_local' => 'integer',
        'request_payload'      => 'array',
        'response_payload'     => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ShippingConnection::class, 'connection_id');
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class, 'parcel_id');
    }

    public function scopeCompanywise(Builder $query): Builder
    {
        return $query->where('company_id', settings()->id ?? null);
    }

    public function scopeNonTerminal(Builder $query): Builder
    {
        return $query->whereNotIn('state', ['cancelled', 'failed'])
                     ->where(function ($q) {
                         $q->whereNull('current_status_local')
                           ->orWhereNotIn('current_status_local', [
                               \App\Enums\ParcelStatus::DELIVERED,
                               \App\Enums\ParcelStatus::PARTIAL_DELIVERED,
                               \App\Enums\ParcelStatus::CANCELLED,
                               \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
                           ]);
                     });
    }
}
