<?php

namespace App\Odoo\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'odoo_settings';

    protected $fillable = [
        'company_id',
        'enabled',
        'host_url',
        'database',
        'username',
        'api_key',
        'cached_uid',
        'default_invoice_journal_id',
        'default_bill_journal_id',
        'default_payment_journal_id',
        'default_product_id',
        'default_tax_id',
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
            && (string) $this->host_url !== ''
            && (string) $this->database !== ''
            && (string) $this->username !== ''
            && (string) $this->api_key !== ''
            && $this->default_invoice_journal_id
            && $this->default_product_id;
    }
}
