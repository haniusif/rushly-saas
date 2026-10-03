<?php

namespace App\Odoo\Services;

use App\Odoo\Models\CourierPartner;
use Illuminate\Support\Carbon;

class VendorSync
{
    public function sync(CourierPartner $vendor): void
    {
        $client = ApiClient::forCompany((int) $vendor->company_id);

        $values = [
            'name'        => $vendor->display_name ?: ucfirst($vendor->courier_key),
            'supplier_rank' => 1,
            'company_type'  => 'company',
        ];

        if ($vendor->odoo_partner_id) {
            $client->write('res.partner', (int) $vendor->odoo_partner_id, $values);
            $partnerId = (int) $vendor->odoo_partner_id;
        } else {
            $partnerId = $client->create('res.partner', $values);
        }

        $vendor->forceFill([
            'odoo_partner_id'   => $partnerId,
            'odoo_sync_status'  => 'synced',
            'odoo_synced_at'    => Carbon::now(),
            'odoo_sync_error'   => null,
        ])->save();
    }
}
