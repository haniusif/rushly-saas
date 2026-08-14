<?php

namespace App\Daftra\Services;

use App\Models\Backend\Merchant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClientSync
{
    public function sync(Merchant $merchant): void
    {
        $client = ApiClient::forCompany((int) $merchant->company_id);

        $payload = ['Client' => $this->buildPayload($merchant)];

        if ($merchant->daftra_client_id) {
            $response = $client->put("clients/{$merchant->daftra_client_id}", $payload);
        } else {
            $response = $client->post('clients', $payload);
        }

        $clientId = $response['id']
            ?? $response['data']['Client']['id']
            ?? $response['Client']['id']
            ?? null;

        $merchant->forceFill([
            'daftra_client_id'   => $clientId,
            'daftra_sync_status' => 'synced',
            'daftra_synced_at'   => Carbon::now(),
            'daftra_sync_error'  => null,
        ])->saveQuietly();
    }

    private function buildPayload(Merchant $merchant): array
    {
        $businessName = (string) ($merchant->business_name ?: $merchant->title ?: ('Merchant #' . $merchant->id));
        $contact      = trim((string) ($merchant->reference_name ?? ''));
        $nameParts    = $contact !== '' ? explode(' ', $contact, 2) : [$businessName, ''];

        return [
            'first_name'    => $nameParts[0] ?? '',
            'last_name'     => $nameParts[1] ?? '',
            'business_name' => $businessName,
            'email'         => (string) optional($merchant->user)->email,
            'phone1'        => (string) (optional($merchant->user)->phone ?? $merchant->reference_phone ?? ''),
            // 2 = individual, 3 = business — matches the open-source client lib's convention.
            'type'          => 3,
            'category'      => 'customers',
        ];
    }
}
