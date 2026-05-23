<?php
namespace App\Enums\Wms;

interface AdjustmentReason
{
    const DAMAGE           = 'damage';
    const COUNT_CORRECTION = 'count_correction';
    const EXPIRY           = 'expiry';
    const THEFT            = 'theft';
    const SYSTEM_ERROR     = 'system_error';
    const OTHER            = 'other';
}
