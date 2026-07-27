<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Webhooks\Contracts\Handler;
use Illuminate\Support\Facades\Log;

/**
 * Fires when the tenant's Salla Partner app publishes a version bump. Salla
 * typically follows this with a fresh `app.store.authorize` (new tokens), so
 * there's nothing to persist here — logging is the whole point. Kept as a
 * real handler so the dispatcher doesn't file it under `unhandled`.
 */
class AppUpdatedHandler implements Handler
{
    public function handle(array $event): void
    {
        Log::info('salla.app.updated', [
            'salla_merchant_id' => $event['merchant'] ?? null,
            'app_id'            => $event['data']['app_id'] ?? null,
            'version'           => $event['data']['app_version'] ?? null,
        ]);
    }
}
