<?php

namespace App\Daftra\Services;

use App\Daftra\Models\Settings;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoicePaymentSync
{
    public function sync(Invoice $invoice): void
    {
        if (! $invoice->daftra_invoice_id) {
            throw new RuntimeException("Invoice {$invoice->id} has no daftra_invoice_id yet");
        }

        $settings = Settings::forCompany((int) $invoice->company_id);
        if (! $settings->isReady()) {
            throw new RuntimeException("Daftra settings incomplete for company {$invoice->company_id}");
        }

        $amount = (float) ($invoice->total_charge - ($invoice->current_payable ?? 0));
        if ($amount <= 0) {
            return;
        }

        $payload = [
            'InvoicePayment' => [
                'invoice_id'     => (int) $invoice->daftra_invoice_id,
                'amount'         => number_format($amount, 2, '.', ''),
                'payment_method' => $settings->default_payment_method ?: 'cash',
                'date'           => Carbon::now()->toDateString(),
                'notes'          => 'rushly-pay-' . $invoice->id,
            ],
        ];

        $client = ApiClient::forCompany((int) $invoice->company_id);
        $response = $client->post('invoice_payments', $payload);

        $paymentId = $response['id']
            ?? $response['data']['InvoicePayment']['id']
            ?? $response['InvoicePayment']['id']
            ?? null;

        $invoice->forceFill([
            'daftra_payment_id'  => $paymentId,
            'daftra_sync_status' => 'paid',
            'daftra_synced_at'   => Carbon::now(),
            'daftra_sync_error'  => null,
        ])->saveQuietly();
    }
}
