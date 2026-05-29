<?php

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\ZidOrderLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bridge between rushly-saas parcel events and the standalone rushly-zid app.
 *
 * The standalone Laravel app owns the Zid OAuth tokens and is the only piece
 * that talks to the Zid API directly. From here we just push status changes
 * over to the standalone app, which forwards them to Zid.
 */
class ZidService
{
    public function __construct(
        private string $appUrl,
        private string $writebackToken,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) rtrim(config('services.zid.app_url') ?? '', '/'),
            (string) config('services.zid.writeback_token'),
        );
    }

    public function pushParcelStatus(Parcel $parcel): void
    {
        if ($this->appUrl === '' || $this->writebackToken === '') {
            return;
        }

        $link = ZidOrderLink::where('parcel_id', $parcel->id)->first();
        if (! $link) {
            return;
        }

        $zidStatus = $this->mapStatus($parcel->status);
        if ($zidStatus === null || $link->last_pushed_status === $zidStatus) {
            return;
        }

        $response = Http::withToken($this->writebackToken)
            ->acceptJson()
            ->post($this->appUrl.'/internal/parcel-status', [
                'zid_store_id'    => $link->zid_store_id,
                'zid_order_id'    => $link->zid_order_id,
                'zid_shipment_id' => $link->zid_shipment_id,
                'parcel_id'       => $parcel->id,
                'tracking_id'     => $parcel->tracking_id ?? null,
                'rushly_status'   => $parcel->status,
                'zid_status'      => $zidStatus,
            ]);

        if ($response->successful()) {
            $link->update([
                'last_pushed_status' => $zidStatus,
                'last_pushed_at'     => now(),
            ]);
            return;
        }

        Log::warning('zid.writeback.failed', [
            'parcel' => $parcel->id,
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);
    }

    /**
     * Canonical Zid order statuses (POST /managers/store/orders/{id}/change-order-status):
     *   new, preparing, ready, indelivery, delivered, cancelled
     *
     * Returns must use the reverse-waybill endpoint, not order status — so we
     * return null for return states and let the caller handle them separately.
     */
    private function mapStatus($rushlyStatus): ?string
    {
        $status = (int) $rushlyStatus;
        return match ($status) {
            ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::PICKUP_RE_SCHEDULE                => 'preparing',
            ParcelStatus::RECEIVED_WAREHOUSE,
            ParcelStatus::TRANSFER_TO_HUB,
            ParcelStatus::DELIVERY_MAN_ASSIGN,
            ParcelStatus::DELIVER                           => 'indelivery',
            ParcelStatus::DELIVERED,
            ParcelStatus::PARTIAL_DELIVERED                 => 'delivered',
            ParcelStatus::CANCELLED                         => 'cancelled',
            default                                         => null,
        };
    }
}
