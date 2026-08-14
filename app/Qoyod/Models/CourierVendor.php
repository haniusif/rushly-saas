<?php

namespace App\Qoyod\Models;

use Illuminate\Database\Eloquent\Model;

class CourierVendor extends Model
{
    protected $table = 'qoyod_courier_vendors';

    protected $fillable = [
        'company_id',
        'courier_key',
        'display_name',
        'qoyod_vendor_id',
        'qoyod_sync_status',
        'qoyod_synced_at',
        'qoyod_sync_error',
    ];

    protected $casts = [
        'qoyod_synced_at' => 'datetime',
    ];

    public static function forCourier(int $companyId, string $courierKey): self
    {
        return self::firstOrNew([
            'company_id'  => $companyId,
            'courier_key' => $courierKey,
        ]);
    }
}
