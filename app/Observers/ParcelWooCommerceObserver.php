<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\WooCommerceService;

class ParcelWooCommerceObserver
{
    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        WooCommerceService::fromConfig()->pushParcelStatus($parcel);
    }
}
