<?php
namespace App\Enums\Wms;

interface GrnStatus
{
    const DRAFT       = 'draft';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED   = 'completed';
    const DISCREPANCY = 'discrepancy';
}
