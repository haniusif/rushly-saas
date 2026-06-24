<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SallaOrderLink extends Model
{
    protected $table = 'salla_order_links';

    protected $fillable = [
        'company_id',
        'merchant_id',
        'parcel_id',
        'salla_merchant_id',
        'salla_order_id',
        'salla_shipment_id',
        'salla_awb_number',
        'last_pushed_status',
        'last_pushed_at',
        'meta',
    ];

    protected $casts = [
        'meta'           => 'array',
        'last_pushed_at' => 'datetime',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function scopeCompanywise($query)
    {
        return $query->where('company_id', auth()->user()->company_id ?? null);
    }
}
