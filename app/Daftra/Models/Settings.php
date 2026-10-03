<?php

namespace App\Daftra\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'daftra_settings';

    protected $fillable = [
        'company_id',
        'enabled',
        'subdomain',
        'api_key',
        'default_payment_method',
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

    public function baseUrl(): ?string
    {
        $sub = trim((string) $this->subdomain);
        if ($sub === '') return null;
        return "https://{$sub}.daftra.com/api2";
    }

    public function isReady(): bool
    {
        return $this->enabled
            && (string) $this->api_key !== ''
            && $this->baseUrl() !== null;
    }
}
