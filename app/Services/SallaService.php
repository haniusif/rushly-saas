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

    private function mapStatus(?string $rushlyStatus): ?string
    {
        return match ($rushlyStatus) {
            ParcelStatus::PENDING->value ?? null               => null,
            'pickup_assign', 'pickup_re_schedule'              => 'picked_up',
            'received_warehouse', 'transfer_to_hub'            => 'in_transit',
            'delivery_man_assign'                              => 'out_for_delivery',
            'delivered', 'partial_delivered'                   => 'delivered',
            'return_to_courier', 'return_assign_to_merchant'   => 'returned',
            'cancel'                                           => 'cancelled',
            default                                            => null,
        };
    }
}
