<?php

namespace App\Qoyod\Observers;

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Qoyod\Jobs\PushInvoiceJob;
use App\Qoyod\Jobs\PushInvoicePaymentJob;
use App\Qoyod\Models\Settings;

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

        // Flipped to PAID — push the payment to Qoyod.
        if ($invoice->wasChanged('status') && (int) $invoice->status === InvoiceStatus::PAID) {
            if ($invoice->qoyod_invoice_id) {
                PushInvoicePaymentJob::dispatch($invoice->id);
            } else {
                // Invoice was never synced — push it now; the payment will
                // be retried after a manual resync or via a follow-up update.
                PushInvoiceJob::dispatch($invoice->id);
            }
        }
    }

    private function enabledForCompany(?int $companyId): bool
    {
        if (! $companyId) return false;
        $s = Settings::where('company_id', $companyId)->first();
        return $s && $s->enabled && (string) $s->api_key !== '';
    }
}
