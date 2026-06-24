<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Webhooks\Contracts\Handler;

class AppUninstalledHandler implements Handler
{
    public function handle(array $event): void
    {
        $sallaId = $event['merchant'] ?? ($event['data']['app_id'] ?? null);
        if (! $sallaId) {
            return;
        }

        Merchant::where('salla_merchant_id', $sallaId)->update([
            'installed'      => false,
            'uninstalled_at' => now(),
            'access_token'   => null,
            'refresh_token'  => null,
        ]);
    }
}
