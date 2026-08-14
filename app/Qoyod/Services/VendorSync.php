<?php

namespace App\Qoyod\Services;

use App\Qoyod\Models\CourierVendor;
use Illuminate\Support\Carbon;

class VendorSync
{
    public function sync(CourierVendor $vendor): void
    {
        $client = ApiClient::forCompany((int) $vendor->company_id);

        $payload = [
            'contact' => [
                'name'         => $vendor->display_name ?: ucfirst($vendor->courier_key),
                'organization' => $vendor->display_name ?: '',
                'phone_number' => '',
                'email'        => '',
                'tax_number'   => '',
            ],
        ];

        if ($vendor->qoyod_vendor_id) {
            $response = $client->put("vendors/{$vendor->qoyod_vendor_id}", $payload);
        } else {
            $response = $client->post('vendors', $payload);
        }

        $vendorId = $response['contact']['id']
            ?? $response['vendor']['id']
            ?? $response['id']
            ?? null;

        $vendor->forceFill([
            'qoyod_vendor_id'   => $vendorId,
            'qoyod_sync_status' => 'synced',
            'qoyod_synced_at'   => Carbon::now(),
            'qoyod_sync_error'  => null,
        ])->save();
    }
}
