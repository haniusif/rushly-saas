<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\ZidService;

class ParcelZidObserver
{
    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        ZidService::fromConfig()->pushParcelStatus($parcel);
    }
}
