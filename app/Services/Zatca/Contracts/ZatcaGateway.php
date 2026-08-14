<?php

namespace App\Services\Zatca\Contracts;

use App\Models\Backend\Zatca\ZatcaInvoice;
use App\Models\Backend\Zatca\ZatcaSetting;

/**
 * Forward-compatibility seam for Phase 2 (Integration).
 *
 * Phase 1 uses NullGateway (no network calls). Phase 2 implementations will
 * hit ZATCA compliance/production endpoints (CSID, clearance, reporting).
 */
interface ZatcaGateway
{
    public function isAvailable(ZatcaSetting $setting): bool;

    /** Phase 2: submit a Standard invoice for clearance. */
    public function clear(ZatcaInvoice $invoice): GatewayResult;

    /** Phase 2: report a Simplified invoice within 24h. */
    public function report(ZatcaInvoice $invoice): GatewayResult;
}
