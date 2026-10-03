<?php

namespace App\Services\Zatca\Gateways;

use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Models\Backend\Zatca\ZatcaSetting;
use App\Services\Zatca\Contracts\GatewayResult;
use App\Services\Zatca\Contracts\ZatcaGateway;

final class NullGateway implements ZatcaGateway
{
    public function isAvailable(ZatcaSetting $setting): bool
    {
        return false;
    }

    public function clear(ZatcaInvoice $invoice): GatewayResult
    {
        return GatewayResult::noop();
    }

    public function report(ZatcaInvoice $invoice): GatewayResult
    {
        return GatewayResult::noop();
    }
}
