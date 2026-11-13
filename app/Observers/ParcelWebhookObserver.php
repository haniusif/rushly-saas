<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\MerchantApi\WebhookNotifier;
use Illuminate\Support\Facades\Log;

/**
 * On a parcel status change, notify the rushly-api façade(s) so merchant
 * webhooks fire. Deferred to after the response so it never adds latency to the
 * status update, and fully best-effort.
 */
class ParcelWebhookObserver
{
    public function created(Parcel $parcel): void
    {
        $parcelId   = (int) $parcel->id;
        $merchantId = (int) $parcel->merchant_id;
        $companyId  = (int) $parcel->company_id;
        $reference  = $parcel->reference_number;

        // tracking_id is assigned on a follow-up save; re-resolve after response.
        try {
            dispatch(function () use ($parcelId, $merchantId, $companyId, $reference) {
                $p = Parcel::withoutGlobalScopes()->find($parcelId);
                if ($p && $p->tracking_id) {
                    app(WebhookNotifier::class)->notify($parcelId, $merchantId, $companyId, $p->tracking_id, $reference, 0, (int) $p->status);
                }
            })->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('webhook.observer.created_failed', ['parcel' => $parcelId, 'error' => $e->getMessage()]);
        }
    }

    public function updated(Parcel $parcel): void
    {
        if (! $parcel->wasChanged('status')) {
            return;
        }

        $parcelId   = (int) $parcel->id;
        $merchantId = (int) $parcel->merchant_id;
        $companyId  = (int) $parcel->company_id;
        $tracking   = $parcel->tracking_id;
        $reference  = $parcel->reference_number;
        $old        = (int) $parcel->getOriginal('status');
        $new        = (int) $parcel->status;

        try {
            dispatch(function () use ($parcelId, $merchantId, $companyId, $tracking, $reference, $old, $new) {
                app(WebhookNotifier::class)->notify($parcelId, $merchantId, $companyId, $tracking, $reference, $old, $new);
            })->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('webhook.observer.failed', ['parcel' => $parcelId, 'error' => $e->getMessage()]);
        }
    }
}
