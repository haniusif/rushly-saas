<?php

namespace App\Daftra\Services;

use App\Daftra\Models\Settings;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchant;
use App\Models\Backend\Merchantpanel\Invoice;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoiceSync
{
    public function sync(Invoice $invoice): void
    {
        $settings = Settings::forCompany((int) $invoice->company_id);
        if (! $settings->isReady()) {
            throw new RuntimeException("Daftra settings incomplete for company {$invoice->company_id}");
        }

        $merchant = Merchant::find($invoice->merchant_id);
        if (! $merchant?->daftra_client_id) {
            (new ClientSync())->sync($merchant);
            $merchant->refresh();
        }

        $items = $this->buildItems($invoice, $settings);
        $reference = (string) ($invoice->invoice_id ?: ('rushly-inv-' . $invoice->id));

        $payload = [
            'Invoice' => [
                'client_id'        => (int) $merchant->daftra_client_id,
                'date'             => optional(Carbon::parse($invoice->invoice_date))->toDateString() ?: Carbon::now()->toDateString(),
                'due_date'         => optional(Carbon::parse($invoice->invoice_date))->toDateString() ?: Carbon::now()->toDateString(),
                'invoice_number'   => $reference,
                'no'               => $reference,
                'notes'            => 'Rushly delivery charges',
            ],
            'InvoiceItem' => $items,
        ];

        $client = ApiClient::forCompany((int) $invoice->company_id);
        $response = $client->post('invoices', $payload);

        $daftraInvoiceId = $response['id']
            ?? $response['data']['Invoice']['id']
            ?? $response['Invoice']['id']
            ?? null;

        $invoice->forceFill([
            'daftra_invoice_id'        => $daftraInvoiceId,
            'daftra_invoice_reference' => $reference,
            'daftra_sync_status'       => 'synced',
            'daftra_synced_at'         => Carbon::now(),
            'daftra_sync_error'        => null,
        ])->saveQuietly();
    }

    private function buildItems(Invoice $invoice, Settings $settings): array
    {
        $vatRate = (float) $settings->vat_percent;
        $parcels = InvoiceParcel::where('invoice_id', $invoice->id)->get();

        if ($parcels->isEmpty()) {
            return [[
                'item'        => 'Delivery charges (invoice ' . ($invoice->invoice_id ?? $invoice->id) . ')',
                'description' => 'Delivery charges',
                'quantity'    => 1,
                'unit_price'  => (float) ($invoice->total_charge ?? 0),
                'tax1_rate'   => $vatRate,
            ]];
        }

        return $parcels->map(function (InvoiceParcel $p) use ($vatRate) {
            return [
                'item'        => 'Delivery #' . ($p->parcel?->tracking_id ?? $p->parcel_id),
                'description' => 'Delivery #' . ($p->parcel?->tracking_id ?? $p->parcel_id),
                'quantity'    => 1,
                'unit_price'  => (float) ($p->total_charge_amount ?? $p->total_delivery_amount ?? 0),
                'tax1_rate'   => $vatRate,
            ];
        })->all();
    }
}
