<?php

namespace App\Qoyod\Services;

use App\Qoyod\Models\CourierVendor;
use App\Qoyod\Models\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pushes a courier_statements row (3PL bill) as a Qoyod Bill, after ensuring
 * the courier exists as a Qoyod vendor.
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
            // Only EXPENSE (=2) rows become AP bills. Income rows are not bills.
            return;
        }

        $settings = Settings::forCompany((int) $row->company_id);
        if (! $settings->isReady()) {
            throw new RuntimeException("Qoyod settings incomplete for company {$row->company_id}");
        }

        $courierKey = $this->resolveCourierKey((int) $row->parcel_id);
        if (! $courierKey) {
            throw new RuntimeException("Could not resolve courier for courier_statements row {$courierStatementId}");
        }

        $vendor = CourierVendor::forCourier((int) $row->company_id, $courierKey);
        if (! $vendor->display_name) {
            $vendor->display_name = ucfirst($courierKey);
        }
        if (! $vendor->qoyod_vendor_id) {
            (new VendorSync())->sync($vendor);
            $vendor->refresh();
        }

        $payload = [
            'bill' => [
                'contact_id'   => (int) $vendor->qoyod_vendor_id,
                'status'       => 'Approved',
                'issue_date'   => Carbon::parse($row->date ?: $row->created_at)->toDateString(),
                'due_date'     => Carbon::parse($row->date ?: $row->created_at)->toDateString(),
                'reference'    => 'rushly-cs-' . $row->id,
                'inventory_id' => (int) $settings->default_inventory_id,
                'line_items'   => [[
                    'product_id'  => (int) $settings->default_product_id,
                    'description' => $row->note ?: ('Courier charge for parcel ' . $row->parcel_id),
                    'quantity'    => 1,
                    'unit_price'  => (float) $row->amount,
                    'discount'    => '0',
                    'tax_percent' => (string) $settings->vat_percent,
                ]],
            ],
        ];

        $client = ApiClient::forCompany((int) $row->company_id);
        $response = $client->post('bills', $payload);

        $billId = $response['bill']['id'] ?? $response['id'] ?? null;

        DB::table('courier_statements')->where('id', $courierStatementId)->update([
            'qoyod_bill_id'     => $billId,
            'qoyod_sync_status' => 'synced',
            'qoyod_synced_at'   => Carbon::now(),
            'qoyod_sync_error'  => null,
            'updated_at'        => Carbon::now(),
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
