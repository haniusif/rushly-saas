<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Models\Settings;
use App\Salla\Webhooks\Contracts\Handler;

class AppInstalledHandler implements Handler
{
    public function handle(array $event): void
    {
        $data = $event['data'] ?? [];

        $merchant = Merchant::updateOrCreate(
            ['salla_merchant_id' => $data['app_id'] ?? $event['merchant'] ?? 0],
            [
                'store_name'     => $data['app_name'] ?? null,
                'store_domain'   => $data['app_domain'] ?? null,
                'installed'      => true,
                'uninstalled_at' => null,
                'scopes'         => $data['scopes'] ?? null,
            ],
        );

        Settings::firstOrCreate(['salla_merchant_id' => $merchant->id]);
    }
}
