<?php

namespace App\Odoo\Observers;

use App\Models\Backend\Merchant;
use App\Odoo\Jobs\SyncMerchantJob;
use App\Odoo\Models\Settings;

class MerchantObserver
{
    public function created(Merchant $merchant): void
    {
        if (! $this->ready($merchant->company_id)) return;
        SyncMerchantJob::dispatch($merchant->id);
    }

    public function updated(Merchant $merchant): void
    {
        if (! $this->ready($merchant->company_id)) return;
        $watched = ['business_name', 'title', 'tax_number', 'address', 'reference_phone'];
        if (! collect($watched)->contains(fn ($f) => $merchant->wasChanged($f))) return;
        SyncMerchantJob::dispatch($merchant->id);
    }

    private function ready(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '' && $s->host_url;
    }
}
