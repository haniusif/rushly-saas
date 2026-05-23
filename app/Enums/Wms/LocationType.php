<?php
namespace App\Enums\Wms;

interface LocationType
{
    const STANDARD = 'standard';
    const BULK     = 'bulk';
    const COLD     = 'cold';
    const HAZMAT   = 'hazmat';
}
