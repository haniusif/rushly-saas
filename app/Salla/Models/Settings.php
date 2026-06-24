<?php

namespace App\Salla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settings extends Model
{
    protected $table = 'salla_settings';

    protected $fillable = [
        'salla_merchant_id',
        'auto_create_parcel',
        'trigger_status',
        'default_rushly_shop_id',
        'default_city_id',
        'default_category_id',
        'default_delivery_type_id',
        'support_email',
    ];

    protected $casts = [
        'auto_create_parcel' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'salla_merchant_id');
    }
}
