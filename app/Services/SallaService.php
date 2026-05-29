<?php

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Models\Backend\Parcel;
use App\Models\Backend\SallaOrderLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bridge between rushly-saas parcel events and the standalone rushly-salla app.
 *
 * The standalone Laravel app owns the Salla access tokens and is the only piece
 * that talks to the Salla API directly. From here we just push status changes
 * over to the standalone app, which forwards them to Salla.
 */
class SallaService
{
    public function __construct(
        private string $appUrl,
        private string $writebackToken,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) rtrim(config('services.salla.app_url') ?? '', '/'),
            (string) config('services.salla.writeback_token'),
        );
    }

    public function pushParcelStatus(Parcel $parcel): void
    {
        if ($this->appUrl === '' || $this->writebackToken === '') {
            return;
        }

        $link = SallaOrderLink::where('parcel_id', $parcel->id)->first();
        if (! $link) {
            return;
        }

        $sallaStatus = $this->mapStatus($parcel->status);
        if ($sallaStatus === null || $link->last_pushed_status === $sallaStatus) {
            return;
        }

        $response = Http::withToken($this->writebackToken)
            ->acceptJson()
            ->post($this->appUrl.'/internal/parcel-status', [
                'salla_merchant_id' => $link->salla_merchant_id,
                'salla_order_id'    => $link->salla_order_id,
                'salla_shipment_id' => $link->salla_shipment_id,
                'parcel_id'         => $parcel->id,
                'tracking_id'       => $parcel->tracking_id ?? null,
                'rushly_status'     => $parcel->status,
                'salla_status'      => $sallaStatus,
            ]);

        if ($response->successful()) {
            $link->update([
                'last_pushed_status' => $sallaStatus,
                'last_pushed_at'     => now(),
            ]);
            return;
        }

        Log::warning('salla.writeback.failed', [
            'parcel'  => $parcel->id,
            'status'  => $response->status(),
            'body'    => $response->json(),
        ]);
    }

    /**
     * Canonical Salla statuses (Update Shipment endpoint, 2025-01-20):
     *   created, in_progress, in_transit, received_at_final_hub, to_be_reattempted,
     *   reattempted, unable_to_deliver, delivering, delivered, partially_delivered,
     *   shipped, cancelled, lost, damaged, return_to_origin, return_in_progress
     */
    private function mapStatus($rushlyStatus): ?string
    {
        $status = (int) $rushlyStatus;
        return match ($status) {
            ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::PICKUP_RE_SCHEDULE                => 'in_progress',
            ParcelStatus::RECEIVED_WAREHOUSE,
            ParcelStatus::TRANSFER_TO_HUB                   => 'in_transit',
            ParcelStatus::DELIVERY_MAN_ASSIGN,
            ParcelStatus::DELIVER                           => 'delivering',
            ParcelStatus::DELIVERED                         => 'delivered',
            ParcelStatus::PARTIAL_DELIVERED                 => 'partially_delivered',
            ParcelStatus::RETURN_TO_COURIER                 => 'return_in_progress',
            ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
            ParcelStatus::RETURN_RECEIVED_BY_MERCHANT       => 'return_to_origin',
            ParcelStatus::CANCELLED                         => 'cancelled',
            default                                         => null,
        };
    }
}
