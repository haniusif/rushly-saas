<?php

namespace App\Services\MerchantApi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notifies the rushly-api façade(s) of a parcel status change so they can fan
 * out merchant webhooks. Fire-and-forget, best-effort: a notification failure
 * must never affect the status change that triggered it. Sends to every
 * configured façade environment (live + test); each façade filters to its own
 * endpoints.
 */
class WebhookNotifier
{
    public function notify(
        int $parcelId,
        int $merchantId,
        int $companyId,
        ?string $trackingNumber,
        ?string $reference,
        int $oldStatus,
        int $newStatus,
    ): void {
        if ($merchantId <= 0 || $companyId <= 0 || ! $trackingNumber) {
            return;
        }

        $eventKey = "p{$parcelId}:{$oldStatus}-{$newStatus}:".now()->timestamp;

        foreach (['live', 'test'] as $env) {
            $cfg = config("merchant_api.$env");
            if (empty($cfg['url']) || empty($cfg['token'])) {
                continue;
            }

            try {
                Http::timeout(5)
                    ->withToken($cfg['token'])
                    ->acceptJson()
                    ->asJson()
                    ->post(rtrim($cfg['url'], '/').'/manage/v1/events', [
                        'merchant_id'     => $merchantId,
                        'company_id'      => $companyId,
                        'tracking_number' => $trackingNumber,
                        'reference'       => $reference,
                        'old_status'      => $oldStatus,
                        'new_status'      => $newStatus,
                        'event_key'       => $eventKey,
                        'occurred_at'     => now()->toIso8601String(),
                    ]);
            } catch (\Throwable $e) {
                Log::warning('webhook.notify.failed', ['env' => $env, 'parcel' => $parcelId, 'error' => $e->getMessage()]);
            }
        }
    }
}
