<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZidOrderLink extends Model
{
    protected $table = 'zid_orders';

    protected $fillable = [
        'company_id',
        'merchant_id',
        'parcel_id',
        'zid_store_id',
        'zid_order_id',
        'zid_shipment_id',
        'zid_awb_number',
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
