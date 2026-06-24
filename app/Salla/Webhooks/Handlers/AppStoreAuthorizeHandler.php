<?php

namespace App\Salla\Webhooks\Handlers;

use App\Salla\Models\Merchant;
use App\Salla\Webhooks\Contracts\Handler;
use Carbon\Carbon;

class AppStoreAuthorizeHandler implements Handler
{
    public function handle(array $event): void
    {
        $data = $event['data'] ?? [];

        Merchant::updateOrCreate(
            ['salla_merchant_id' => $event['merchant'] ?? $data['merchant'] ?? 0],
            [
                'access_token'     => $data['access_token'] ?? null,
                'refresh_token'    => $data['refresh_token'] ?? null,
                'token_expires_at' => isset($data['expires'])
                    ? Carbon::createFromTimestamp($data['expires'])
                    : null,
                'scopes'           => isset($data['scope']) ? explode(' ', $data['scope']) : null,
                'installed'        => true,
            ],
        );
    }
}
