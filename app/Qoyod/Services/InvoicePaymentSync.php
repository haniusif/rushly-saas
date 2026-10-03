<?php

namespace App\Qoyod\Services;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Qoyod\Models\Settings;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoicePaymentSync
{
    public function sync(Invoice $invoice): void
    {
        if (! $invoice->qoyod_invoice_id) {
            throw new RuntimeException("Invoice {$invoice->id} has no qoyod_invoice_id yet");
        }

        $settings = Settings::forCompany((int) $invoice->company_id);
        if (! $settings->isReady()) {
            throw new RuntimeException("Qoyod settings incomplete for company {$invoice->company_id}");
        }

        $amount = (float) ($invoice->total_charge - ($invoice->current_payable ?? 0));
        if ($amount <= 0) {
            // Nothing collected yet, or fully balanced already.
            return;
        }

        $payload = [
            'invoice_payment' => [
                'reference'  => 'rushly-pay-' . $invoice->id,
                'invoice_id' => (string) $invoice->qoyod_invoice_id,
                'account_id' => (string) $settings->default_account_id,
                'date'       => Carbon::now()->toDateString(),
                'amount'     => number_format($amount, 2, '.', ''),
            ],
        ];

        $client = ApiClient::forCompany((int) $invoice->company_id);
        $response = $client->post('invoice_payments', $payload);

        $paymentId = $response['invoice_payment']['id'] ?? $response['id'] ?? null;

        $invoice->forceFill([
            'qoyod_payment_id'  => $paymentId,
            'qoyod_sync_status' => 'paid',
            'qoyod_synced_at'   => Carbon::now(),
            'qoyod_sync_error'  => null,
        ])->saveQuietly();
    }
}
