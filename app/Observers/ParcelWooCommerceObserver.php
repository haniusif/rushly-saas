<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\WooCommerceService;
use Illuminate\Support\Facades\Log;

class ParcelWooCommerceObserver
{
    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        // Writeback to WooCommerce is a non-critical side effect: it must never
        // fail the parcel status change that triggered it.
        try {
            WooCommerceService::fromConfig()->pushParcelStatus($parcel);
        } catch (\Throwable $e) {
            Log::warning('woocommerce.writeback.observer_failed', ['parcel' => $parcel->id, 'error' => $e->getMessage()]);
        }
    }
}
