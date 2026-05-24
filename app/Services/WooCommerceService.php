<?php

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\WooCommerceOrderLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Writes parcel status changes back to a merchant's WordPress / WooCommerce
 * site. Unlike Salla and Zid — which talk to a single shared bridge app — the
 * WordPress integration ships as a plugin that lives on the merchant's own WP
 * install, so each link row knows its own site_url + per-site bearer token.
 *
 * Falls back to config('services.woocommerce.app_url') / writeback_token when
 * the link row doesn't carry per-store values (e.g. single-tenant install).
 */
class WooCommerceService
{
    public function __construct(
        private string $fallbackUrl,
        private string $fallbackToken,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) rtrim(config('services.woocommerce.app_url') ?? '', '/'),
            (string) config('services.woocommerce.writeback_token'),
        );
    }

    public function pushParcelStatus(Parcel $parcel): void
    {
        $link = WooCommerceOrderLink::where('parcel_id', $parcel->id)->first();
        if (! $link) {
            return;
        }

        $siteUrl = rtrim($link->site_url ?: $this->fallbackUrl, '/');
        $token   = $link->site_token ?: $this->fallbackToken;

        if ($siteUrl === '' || $token === '') {
            return;
        }

        $wcStatus = $this->mapStatus($parcel->status);
        if ($wcStatus === null || $link->last_pushed_status === $wcStatus) {
            return;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($siteUrl.'/wp-json/rushly/v1/parcel-status', [
                'wc_order_id'   => $link->wc_order_id,
                'site_url'      => $link->site_url,
                'parcel_id'     => $parcel->id,
                'tracking_id'   => $parcel->tracking_id ?? null,
                'rushly_status' => $parcel->status,
                'wc_status'     => $wcStatus,
            ]);

        if ($response->successful()) {
            $link->update([
                'last_pushed_status' => $wcStatus,
                'last_pushed_at'     => now(),
            ]);
            return;
        }

        Log::warning('woocommerce.writeback.failed', [
            'parcel'   => $parcel->id,
            'site_url' => $link->site_url,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);
    }

    /**
     * Maps Rushly's 34-state parcel lifecycle onto WooCommerce's order
     * statuses (excluding the wc- prefix WP stores internally).
     *
     * WooCommerce ships with: pending, processing, on-hold, completed,
     * cancelled, refunded, failed. Plugins/themes often add custom statuses
     * like "shipped" and "out-for-delivery" — the WP plugin maps these to
     * a custom "rushly_*" status namespace so it doesn't clobber existing
     * shop conventions.
     */
    private function mapStatus(?string $rushlyStatus): ?string
    {
        return match ($rushlyStatus) {
            ParcelStatus::PENDING->value ?? null               => null,
            'pickup_assign', 'pickup_re_schedule'              => 'rushly-picked-up',
            'received_warehouse', 'transfer_to_hub'            => 'rushly-in-transit',
            'delivery_man_assign'                              => 'rushly-out-for-delivery',
            'delivered', 'partial_delivered'                   => 'completed',
            'return_to_courier', 'return_assign_to_merchant'   => 'rushly-returned',
            'cancel'                                           => 'cancelled',
            default                                            => null,
        };
    }
}
