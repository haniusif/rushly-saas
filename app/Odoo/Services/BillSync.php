<?php

namespace App\Odoo\Services;

use App\Odoo\Models\CourierPartner;
use App\Odoo\Models\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pushes a courier_statements row as an Odoo vendor bill (account.move with
 * move_type='in_invoice'), after ensuring the courier exists as a vendor
 * partner.
 */
class BillSync
{
    public function sync(int $courierStatementId): void
    {
        $row = DB::table('courier_statements')->where('id', $courierStatementId)->first();
        if (! $row) {
            throw new RuntimeException("courier_statements row {$courierStatementId} not found");
        }
        if ((int) $row->type !== 2) {
            return; // EXPENSE only
        }

        $settings = Settings::forCompany((int) $row->company_id);
        if (! $settings->isReady() || ! $settings->default_bill_journal_id) {
            throw new RuntimeException("Odoo settings incomplete (need default_bill_journal_id) for company {$row->company_id}");
        }

        $courierKey = $this->resolveCourierKey((int) $row->parcel_id);
        if (! $courierKey) {
            throw new RuntimeException("Could not resolve courier for courier_statements row {$courierStatementId}");
        }

        $vendor = CourierPartner::forCourier((int) $row->company_id, $courierKey);
        if (! $vendor->display_name) {
            $vendor->display_name = ucfirst($courierKey);
        }
        if (! $vendor->odoo_partner_id) {
            (new VendorSync())->sync($vendor);
            $vendor->refresh();
        }

        $client = ApiClient::forCompany((int) $row->company_id);

        $line = [
            'product_id' => (int) $settings->default_product_id,
            'name'       => $row->note ?: ('Courier charge for parcel ' . $row->parcel_id),
            'quantity'   => 1,
            'price_unit' => (float) $row->amount,
        ];
        if ($settings->default_tax_id) {
            $line['tax_ids'] = [[6, 0, [(int) $settings->default_tax_id]]];
        }

        $values = [
            'move_type'         => 'in_invoice',
            'partner_id'        => (int) $vendor->odoo_partner_id,
            'invoice_date'      => Carbon::parse($row->date ?: $row->created_at)->toDateString(),
            'ref'               => 'rushly-cs-' . $row->id,
            'journal_id'        => (int) $settings->default_bill_journal_id,
            'invoice_line_ids'  => [[0, 0, $line]],
        ];

        $billId = $client->create('account.move', $values);

        try {
            $client->call('account.move', 'action_post', [[$billId]]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('odoo.bill.post_failed', [
                'courier_statement_id' => $courierStatementId,
                'odoo_bill_id'         => $billId,
                'error'                => $e->getMessage(),
            ]);
        }

        DB::table('courier_statements')->where('id', $courierStatementId)->update([
            'odoo_bill_id'     => $billId,
            'odoo_sync_status' => 'synced',
            'odoo_synced_at'   => Carbon::now(),
            'odoo_sync_error'  => null,
            'updated_at'       => Carbon::now(),
        ]);
    }

    private function resolveCourierKey(int $parcelId): ?string
    {
        $key = DB::table('parcels_3pl')
            ->where('parcel_id', $parcelId)
            ->latest('id')
            ->value('parcel_3pl_name');

        return $key ? strtolower(trim($key)) : null;
    }
}
