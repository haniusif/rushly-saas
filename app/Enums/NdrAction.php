<?php
namespace App\Enums;

interface NdrAction
{
    const RESCHEDULE          = 'reschedule';
    const RETURN_TO_MERCHANT  = 'return_to_merchant';
    const TRANSFER_HUB        = 'transfer_hub';
    const ESCALATE            = 'escalate';
}
