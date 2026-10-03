<?php

namespace App\Qoyod\Observers;

use App\Models\Backend\Merchant;
use App\Qoyod\Jobs\SyncMerchantJob;
use App\Qoyod\Models\Settings;

class MerchantObserver
{
    public function created(Merchant $merchant): void
    {
        if (! $this->enabledForCompany($merchant->company_id)) return;
        SyncMerchantJob::dispatch($merchant->id);
    }

    public function updated(Merchant $merchant): void
    {
        if (! $this->enabledForCompany($merchant->company_id)) return;

        // Only resync on attribute changes that matter to Qoyod.
        $watched = ['business_name', 'title', 'tax_number', 'address'];
        if (! collect($watched)->contains(fn ($f) => $merchant->wasChanged($f))) return;

        SyncMerchantJob::dispatch($merchant->id);
    }

    private function enabledForCompany(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '';
    }
}
