<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\ZidService;
use Illuminate\Support\Facades\Log;

class ParcelZidObserver
{
    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        // Writeback to Zid is a non-critical side effect: it must never fail
        // the parcel status change that triggered it.
        try {
            ZidService::fromConfig()->pushParcelStatus($parcel);
        } catch (\Throwable $e) {
            Log::warning('zid.writeback.observer_failed', ['parcel' => $parcel->id, 'error' => $e->getMessage()]);
        }
    }
}
