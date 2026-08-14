<?php

namespace App\Qoyod\Services;

use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Qoyod\Models\Settings;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoiceSync
{
    public function sync(Invoice $invoice): void
    {
        $settings = Settings::forCompany((int) $invoice->company_id);
        if (! $settings->isReady()) {
            throw new RuntimeException("Qoyod settings incomplete for company {$invoice->company_id}");
        }

        $merchant = Merchant::find($invoice->merchant_id);
        if (! $merchant?->qoyod_customer_id) {
            // Sync the merchant first so we have a contact_id.
            (new CustomerSync())->sync($merchant);
            $merchant->refresh();
        }

        $lineItems = $this->buildLineItems($invoice, $settings);
        $reference = (string) ($invoice->invoice_id ?: ('rushly-inv-' . $invoice->id));

        $payload = [
            'invoice' => [
                'contact_id'   => (int) $merchant->qoyod_customer_id,
                'reference'    => $reference,
                'description'  => 'Rushly delivery charges',
                'issue_date'   => optional(Carbon::parse($invoice->invoice_date))->toDateString() ?: Carbon::now()->toDateString(),
                'due_date'     => optional(Carbon::parse($invoice->invoice_date))->toDateString() ?: Carbon::now()->toDateString(),
                'status'       => 'Approved',
                'inventory_id' => (int) $settings->default_inventory_id,
                'line_items'   => $lineItems,
            ],
        ];

        $client = ApiClient::forCompany((int) $invoice->company_id);
        $response = $client->post('invoices', $payload);

        $qoyodInvoiceId = $response['invoice']['id'] ?? $response['id'] ?? null;

        $invoice->forceFill([
            'qoyod_invoice_id'        => $qoyodInvoiceId,
            'qoyod_invoice_reference' => $reference,
            'qoyod_sync_status'       => 'synced',
            'qoyod_synced_at'         => Carbon::now(),
            'qoyod_sync_error'        => null,
        ])->saveQuietly();
    }

    private function buildLineItems(Invoice $invoice, Settings $settings): array
    {
        $vatRate   = (float) $settings->vat_percent;
        $productId = (int) $settings->default_product_id;

        $parcels = InvoiceParcel::where('invoice_id', $invoice->id)->get();

        if ($parcels->isEmpty()) {
            return [[
                'product_id'    => $productId,
                'description'   => 'Delivery charges (invoice ' . ($invoice->invoice_id ?? $invoice->id) . ')',
                'quantity'      => 1,
                'unit_price'    => (float) ($invoice->total_charge ?? 0),
                'discount'      => 0,
                'discount_type' => 'amount',
                'tax_percent'   => $vatRate,
            ]];
        }

        return $parcels->map(function (InvoiceParcel $p) use ($productId, $vatRate) {
            return [
                'product_id'    => $productId,
                'description'   => 'Delivery #' . ($p->parcel?->tracking_id ?? $p->parcel_id),
                'quantity'      => 1,
                'unit_price'    => (float) ($p->total_charge_amount ?? $p->total_delivery_amount ?? 0),
                'discount'      => 0,
                'discount_type' => 'amount',
                'tax_percent'   => $vatRate,
            ];
        })->all();
    }
}
