<?php

namespace App\Logestechs\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'logestechs_settings';

    protected $fillable = [
        'company_id',
        'enabled',
        'base_url',
        'integration_source',
        'default_target_company_id',
        'default_email',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function forCompany(?int $companyId): self
    {
        return self::firstOrNew(['company_id' => $companyId]);
    }

    /**
     * Logestechs only needs base_url to be reachable — auth happens per
     * shipment via the `company-id` header + per-call email/password.
     * The DB row's base_url overrides config('services.logestechs.base_url');
     * if both are blank, the integration isn't usable.
     */
    public function isReady(): bool
    {
        if (! $this->enabled) {
            return false;
        }
        $base = (string) ($this->base_url ?: config('services.logestechs.base_url'));
        return $base !== '';
    }
}
