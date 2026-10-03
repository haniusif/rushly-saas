<?php

namespace App\Qoyod\Services;

use App\Models\Backend\Merchant;
use Illuminate\Support\Carbon;

class CustomerSync
{
    public function sync(Merchant $merchant): void
    {
        $client = ApiClient::forCompany((int) $merchant->company_id);

        $payload = [
            'contact' => [
                'name'             => $merchant->business_name ?: $merchant->title ?: ('Merchant #' . $merchant->id),
                'organization'     => $merchant->business_name ?: '',
                'email'            => optional($merchant->user)->email ?: '',
                'phone_number'     => optional($merchant->user)->phone ?: '',
                'tax_number'       => $merchant->tax_number ?: '',
                'status'           => 'Active',
                'billing_address'  => [
                    'billing_address' => (string) ($merchant->address ?: ''),
                    'billing_country' => 'Saudi Arabia',
                ],
            ],
        ];

        if ($merchant->qoyod_customer_id) {
            $response = $client->put("customers/{$merchant->qoyod_customer_id}", $payload);
        } else {
            $response = $client->post('customers', $payload);
        }

        $contactId = $response['contact']['id']
            ?? $response['customer']['id']
            ?? $response['id']
            ?? null;

        $merchant->forceFill([
            'qoyod_customer_id' => $contactId,
            'qoyod_sync_status' => 'synced',
            'qoyod_synced_at'   => Carbon::now(),
            'qoyod_sync_error'  => null,
        ])->saveQuietly();
    }
}
