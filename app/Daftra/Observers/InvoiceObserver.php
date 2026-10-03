<?php

namespace App\Daftra\Observers;

use App\Daftra\Jobs\PushInvoiceJob;
use App\Daftra\Jobs\PushInvoicePaymentJob;
use App\Daftra\Models\Settings;
use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        if (! $this->enabledForCompany($invoice->company_id)) return;
        PushInvoiceJob::dispatch($invoice->id);
    }

    public function updated(Invoice $invoice): void
    {
        if (! $this->enabledForCompany($invoice->company_id)) return;
        if ($invoice->wasChanged('status') && (int) $invoice->status === InvoiceStatus::PAID) {
            if ($invoice->daftra_invoice_id) {
                PushInvoicePaymentJob::dispatch($invoice->id);
            } else {
                PushInvoiceJob::dispatch($invoice->id);
            }
        }
    }

    private function enabledForCompany(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '' && $s->subdomain;
    }
}
