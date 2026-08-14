<?php

namespace App\Odoo\Observers;

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Odoo\Jobs\PushInvoiceJob;
use App\Odoo\Jobs\PushInvoicePaymentJob;
use App\Odoo\Models\Settings;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        if (! $this->ready($invoice->company_id)) return;
        PushInvoiceJob::dispatch($invoice->id);
    }

    public function updated(Invoice $invoice): void
    {
        if (! $this->ready($invoice->company_id)) return;
        if ($invoice->wasChanged('status') && (int) $invoice->status === InvoiceStatus::PAID) {
            if ($invoice->odoo_invoice_id) {
                PushInvoicePaymentJob::dispatch($invoice->id);
            } else {
                PushInvoiceJob::dispatch($invoice->id);
            }
        }
    }

    private function ready(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '' && $s->host_url;
    }
}
