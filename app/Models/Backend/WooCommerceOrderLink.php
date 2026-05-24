<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WooCommerceOrderLink extends Model
{
    protected $table = 'woocommerce_orders';

    protected $fillable = [
        'company_id',
        'merchant_id',
        'parcel_id',
        'site_url',
        'wc_order_id',
        'site_token',
        'wc_awb_number',
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
