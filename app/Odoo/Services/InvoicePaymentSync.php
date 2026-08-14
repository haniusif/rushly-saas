<?php

namespace App\Odoo\Services;

use App\Models\Backend\Merchantpanel\Invoice;
use App\Odoo\Models\Settings;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoicePaymentSync
{
    public function sync(Invoice $invoice): void
    {
        if (! $invoice->odoo_invoice_id) {
            throw new RuntimeException("Invoice {$invoice->id} has no odoo_invoice_id yet");
        }

        $settings = Settings::forCompany((int) $invoice->company_id);
        if (! $settings->isReady() || ! $settings->default_payment_journal_id) {
            throw new RuntimeException("Odoo settings incomplete for company {$invoice->company_id} (need default_payment_journal_id)");
        }

        $amount = (float) ($invoice->total_charge - ($invoice->current_payable ?? 0));
        if ($amount <= 0) {
            return;
        }

        $client = ApiClient::forCompany((int) $invoice->company_id);

        // Fetch partner_id from the move so we link the payment correctly.
        $records = $client->read('account.move', [(int) $invoice->odoo_invoice_id], ['partner_id']);
        $partnerId = $records[0]['partner_id'][0] ?? null;
        if (! $partnerId) {
            throw new RuntimeException('Could not read partner_id from Odoo invoice ' . $invoice->odoo_invoice_id);
        }

        $values = [
            'payment_type' => 'inbound',
            'partner_type' => 'customer',
            'partner_id'   => $partnerId,
            'amount'       => $amount,
            'date'         => Carbon::now()->toDateString(),
            'journal_id'   => (int) $settings->default_payment_journal_id,
            'ref'          => 'rushly-pay-' . $invoice->id,
            'reconciled_invoice_ids' => [[6, 0, [(int) $invoice->odoo_invoice_id]]],
        ];

        $paymentId = $client->create('account.payment', $values);

        try {
            $client->call('account.payment', 'action_post', [[$paymentId]]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('odoo.payment.post_failed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
        }

        $invoice->forceFill([
            'odoo_payment_id'  => $paymentId,
            'odoo_sync_status' => 'paid',
            'odoo_synced_at'   => Carbon::now(),
            'odoo_sync_error'  => null,
        ])->saveQuietly();
    }
}
