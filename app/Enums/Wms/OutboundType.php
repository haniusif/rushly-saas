<?php
namespace App\Enums\Wms;

interface OutboundType
{
    const FULFILLMENT        = 'fulfillment';
    const MANUAL             = 'manual';
    const TRANSFER           = 'transfer';
    const RETURN_TO_MERCHANT = 'return_to_merchant';
}
