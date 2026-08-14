<?php

namespace App\Qoyod\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'qoyod_settings';

    protected $fillable = [
        'company_id',
        'enabled',
        'api_key',
        'default_inventory_id',
        'default_product_id',
        'default_account_id',
        'vat_percent',
        'last_synced_at',
    ];

    protected $casts = [
        'enabled'        => 'boolean',
        'vat_percent'    => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['api_key'];

    public static function forCompany(int $companyId): self
    {
        return self::firstOrNew(['company_id' => $companyId]);
    }

    public function isReady(): bool
    {
        return $this->enabled
            && (string) $this->api_key !== ''
            && $this->default_inventory_id
            && $this->default_product_id
            && $this->default_account_id;
    }
}
