<?php

namespace App\Daftra\Observers;

use App\Daftra\Jobs\SyncClientJob;
use App\Daftra\Models\Settings;
use App\Models\Backend\Merchant;

class MerchantObserver
{
    public function created(Merchant $merchant): void
    {
        if (! $this->enabledForCompany($merchant->company_id)) return;
        SyncClientJob::dispatch($merchant->id);
    }

    public function updated(Merchant $merchant): void
    {
        if (! $this->enabledForCompany($merchant->company_id)) return;
        $watched = ['business_name', 'title', 'tax_number', 'address', 'reference_name', 'reference_phone'];
        if (! collect($watched)->contains(fn ($f) => $merchant->wasChanged($f))) return;
        SyncClientJob::dispatch($merchant->id);
    }

    private function enabledForCompany(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '' && $s->subdomain;
    }
}
