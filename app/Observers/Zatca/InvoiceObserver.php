<?php

namespace App\Observers\Zatca;

use App\Jobs\Zatca\GenerateZatcaInvoiceJob;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Backend\Zatca\ZatcaSetting;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $companyId = $invoice->company_id;
        if (! $companyId) {
            return;
        }

        $setting = ZatcaSetting::query()
            ->where('company_id', $companyId)
            ->first();

        if (! $setting || ! $setting->enabled || ! $setting->auto_generate) {
            return;
        }
        if (! $setting->isReady()) {
            return;
        }

        GenerateZatcaInvoiceJob::dispatch($invoice->id)->onQueue('zatca');
    }
}
