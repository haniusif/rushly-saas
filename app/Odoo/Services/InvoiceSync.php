<?php

namespace App\Odoo\Services;

use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Odoo\Models\Settings;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoiceSync
{
    public function sync(Invoice $invoice): void
    {
        $settings = Settings::forCompany((int) $invoice->company_id);
        if (! $settings->isReady()) {
            throw new RuntimeException("Odoo settings incomplete for company {$invoice->company_id}");
        }

        $merchant = Merchant::find($invoice->merchant_id);
        if (! $merchant?->odoo_partner_id) {
            (new CustomerSync())->sync($merchant);
            $merchant->refresh();
        }

        $client = ApiClient::forCompany((int) $invoice->company_id);

        $lines = $this->buildLines($invoice, $settings);
        $reference = (string) ($invoice->invoice_id ?: ('rushly-inv-' . $invoice->id));

        $values = [
            'move_type'         => 'out_invoice',  // customer invoice
            'partner_id'        => (int) $merchant->odoo_partner_id,
            'invoice_date'      => optional(Carbon::parse($invoice->invoice_date))->toDateString() ?: Carbon::now()->toDateString(),
            'ref'               => $reference,
            'journal_id'        => (int) $settings->default_invoice_journal_id,
            'invoice_line_ids'  => $lines,
        ];

        $odooInvoiceId = $client->create('account.move', $values);

        // Post the invoice (move it from Draft to Posted state).
        try {
            $client->call('account.move', 'action_post', [[$odooInvoiceId]]);
        } catch (\Throwable $e) {
            // Posting failed — leave the invoice as Draft and continue; user can post manually.
            \Illuminate\Support\Facades\Log::warning('odoo.invoice.post_failed', [
                'invoice_id'      => $invoice->id,
                'odoo_invoice_id' => $odooInvoiceId,
                'error'           => $e->getMessage(),
            ]);
        }

        $invoice->forceFill([
            'odoo_invoice_id'        => $odooInvoiceId,
            'odoo_invoice_reference' => $reference,
            'odoo_sync_status'       => 'synced',
            'odoo_synced_at'         => Carbon::now(),
            'odoo_sync_error'        => null,
        ])->saveQuietly();
    }

    private function buildLines(Invoice $invoice, Settings $settings): array
    {
        $productId = (int) $settings->default_product_id;
        $taxId     = $settings->default_tax_id ? (int) $settings->default_tax_id : null;
        $parcels   = InvoiceParcel::where('invoice_id', $invoice->id)->get();

        if ($parcels->isEmpty()) {
            return [
                [0, 0, $this->lineValues($productId, 'Delivery charges (invoice ' . ($invoice->invoice_id ?? $invoice->id) . ')', (float) ($invoice->total_charge ?? 0), $taxId)],
            ];
        }

        return $parcels->map(function (InvoiceParcel $p) use ($productId, $taxId) {
            $name  = 'Delivery #' . ($p->parcel?->tracking_id ?? $p->parcel_id);
            $price = (float) ($p->total_charge_amount ?? $p->total_delivery_amount ?? 0);
            return [0, 0, $this->lineValues($productId, $name, $price, $taxId)];
        })->all();
    }

    private function lineValues(int $productId, string $name, float $price, ?int $taxId): array
    {
        $line = [
            'product_id'  => $productId,
            'name'        => $name,
            'quantity'    => 1,
            'price_unit'  => $price,
        ];
        if ($taxId) {
            // Many2many `tax_ids` is set with a special triplet command: (6, 0, [ids]) replaces with the listed IDs.
            $line['tax_ids'] = [[6, 0, [$taxId]]];
        }
        return $line;
    }
}
