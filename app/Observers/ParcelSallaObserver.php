<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\SallaService;
use Illuminate\Support\Facades\Log;

class ParcelSallaObserver
{
    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        // Writeback to Salla is a non-critical side effect: it must never fail
        // the parcel status change that triggered it.
        try {
            SallaService::fromConfig()->pushParcelStatus($parcel);
        } catch (\Throwable $e) {
            Log::warning('salla.writeback.observer_failed', ['parcel' => $parcel->id, 'error' => $e->getMessage()]);
        }
    }
}
