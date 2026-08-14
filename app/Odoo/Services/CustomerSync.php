<?php

namespace App\Odoo\Services;

use App\Models\Backend\Merchant;
use Illuminate\Support\Carbon;

class CustomerSync
{
    public function sync(Merchant $merchant): void
    {
        $client = ApiClient::forCompany((int) $merchant->company_id);

        $values = [
            'name'         => $merchant->business_name ?: $merchant->title ?: ('Merchant #' . $merchant->id),
            'email'        => (string) optional($merchant->user)->email,
            'phone'        => (string) (optional($merchant->user)->phone ?? $merchant->reference_phone ?? ''),
            'vat'          => (string) ($merchant->tax_number ?? ''),
            'street'       => (string) ($merchant->address ?? ''),
            'customer_rank'=> 1,
            'company_type' => 'company',
        ];

        if ($merchant->odoo_partner_id) {
            $client->write('res.partner', (int) $merchant->odoo_partner_id, $values);
            $partnerId = (int) $merchant->odoo_partner_id;
        } else {
            $partnerId = $client->create('res.partner', $values);
        }

        $merchant->forceFill([
            'odoo_partner_id'   => $partnerId,
            'odoo_sync_status'  => 'synced',
            'odoo_synced_at'    => Carbon::now(),
            'odoo_sync_error'   => null,
        ])->saveQuietly();
    }
}
