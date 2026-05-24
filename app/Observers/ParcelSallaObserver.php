<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\SallaService;

class ParcelSallaObserver
{
    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        SallaService::fromConfig()->pushParcelStatus($parcel);
    }
}
