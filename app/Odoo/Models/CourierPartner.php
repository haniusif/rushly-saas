<?php

namespace App\Odoo\Models;

use Illuminate\Database\Eloquent\Model;

class CourierPartner extends Model
{
    protected $table = 'odoo_courier_partners';

    protected $fillable = [
        'company_id',
        'courier_key',
        'display_name',
        'odoo_partner_id',
        'odoo_sync_status',
        'odoo_synced_at',
        'odoo_sync_error',
    ];

    protected $casts = [
        'odoo_synced_at' => 'datetime',
    ];

    public static function forCourier(int $companyId, string $courierKey): self
    {
        return self::firstOrNew([
            'company_id'  => $companyId,
            'courier_key' => $courierKey,
        ]);
    }
}
