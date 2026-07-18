<?php

namespace App\Models\Backend\Zatca;

use App\Enums\Zatca\ZatcaMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ZatcaSetting extends Model
{
    protected $table = 'zatca_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_generate' => 'boolean',
        'vat_rate' => 'decimal:2',
        'last_invoice_counter' => 'integer',
    ];

    public function scopeCompanywise(Builder $q): Builder
    {
        // Same fix as ZatcaSettingRepository: settings() has no argument
        // form; use ->id for the current tenant's company_id.
        return $q->where('company_id', settings()->id);
    }

    /**
     * The `mode` column stores a ZatcaMode string ("sandbox" / "production").
     * We can't name this getter `mode()` because Eloquent's magic __get
     * treats a method with the same name as a relationship access and
     * fails with "must return a relationship instance." Renamed to
     * modeEnum() and updated the two internal callers below.
     */
    public function modeEnum(): ZatcaMode
    {
        return ZatcaMode::tryFrom((string) $this->getAttribute('mode')) ?? ZatcaMode::Sandbox;
    }

    public function isProduction(): bool
    {
        return $this->modeEnum() === ZatcaMode::Production;
    }

    public function isReady(): bool
    {
        return $this->enabled
            && filled($this->seller_name_en)
            && filled($this->seller_name_ar)
            && filled($this->vat_number)
            && strlen((string) $this->vat_number) === 15;
    }

    public function nextInvoiceNumber(): string
    {
        $next = ((int) $this->last_invoice_counter) + 1;
        return $this->invoice_prefix . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }
}
