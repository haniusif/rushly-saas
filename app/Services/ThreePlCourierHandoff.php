<?php

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Backend\ThreePlCourier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Hands a parcel over to the courier record that represents a 3PL carrier.
 *
 * Once a carrier accepts a shipment and returns an AWB, the parcel is out of
 * our warehouse and in their network - but every 3PL path in the app used to
 * leave it sitting at RECEIVED_WAREHOUSE. Two things went wrong as a result:
 * operators could not tell a handed-over parcel from one still on the shelf,
 * and Shipping\Listeners\UpdateParcelStatus never took its repository branch
 * on DELIVERED (balances, notifications) because that branch requires the
 * parcel to have been at DELIVERY_MAN_ASSIGN first.
 *
 * DELIVERY_MAN_ASSIGN is this app's 'out for delivery' - the same status
 * EcoExpress's own OFD code maps to in their StatusMapper - and it is an
 * assignment, so it needs a courier. Tenants nominate one per carrier; there
 * is no default and nothing is invented if they have not.
 *
 * Every failure here is logged and swallowed. The carrier has already
 * accepted the shipment by the time we are called; throwing would fail a
 * bulk action over a bookkeeping step and tempt an operator into re-sending
 * a parcel that is already on a van.
 */
class ThreePlCourierHandoff
{
    /**
     * Statuses from which a 3PL handover must not move the parcel.
     *
     * Anything already out for delivery, finished, returning or cancelled is
     * further along than 'just handed to the carrier'. A late-arriving
     * shipment record (a retried job, a re-run bulk action) must not drag a
     * delivered parcel backwards.
     */
    private const TERMINAL = [
        ParcelStatus::DELIVERY_MAN_ASSIGN,
        ParcelStatus::DELIVERY_RE_SCHEDULE,
        ParcelStatus::DELIVERED,
        ParcelStatus::DELIVER,
        ParcelStatus::PARTIAL_DELIVERED,
        ParcelStatus::RETURN_WAREHOUSE,
        ParcelStatus::RETURN_TO_COURIER,
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT,
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE,
        ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
        ParcelStatus::ASSIGN_MERCHANT,
        ParcelStatus::RETURNED_MERCHANT,
        ParcelStatus::CANCELLED,
    ];

    /**
     * Move the parcel to out-for-delivery under the carrier's courier.
     *
     * @param  string    $carrierCode  'panda' | 'zajel' | 'aramex' | 'jet' | 'logestechs' | 'ecoexpress'
     * @param  int|null  $courierId    Courier nominated by the caller (a shipping
     *                                 connection's own choice). Falls back to the
     *                                 tenant's map for the carrier when null.
     * @param  string|null $awb        Recorded in the event note for traceability.
     * @return bool  Whether the parcel was actually moved.
     */
    public function handoff(Parcel $parcel, string $carrierCode, ?int $courierId = null, ?string $awb = null): bool
    {
        $carrierCode = strtolower(trim($carrierCode));

        try {
            if (in_array((int) $parcel->status, self::TERMINAL, true)) {
                return false;
            }

            $courier = $this->resolveCourier((int) $parcel->company_id, $carrierCode, $courierId);
            if (! $courier) {
                Log::info('3pl.handoff_skipped_no_courier', [
                    'parcel_id' => $parcel->id,
                    'carrier'   => $carrierCode,
                    'company_id'=> $parcel->company_id,
                ]);
                return false;
            }

            // Re-running a bulk action, or a queue retry, must not stack a
            // second assignment event on the same parcel.
            $already = ParcelEvent::where('parcel_id', $parcel->id)
                ->where('parcel_status', ParcelStatus::DELIVERY_MAN_ASSIGN)
                ->where('delivery_man_id', $courier->id)
                ->exists();
            if ($already) {
                return false;
            }

            DB::transaction(function () use ($parcel, $courier, $carrierCode, $awb) {
                $event                  = new ParcelEvent();
                $event->parcel_id       = $parcel->id;
                $event->company_id      = $parcel->company_id;
                $event->delivery_man_id = $courier->id;
                $event->parcel_status   = ParcelStatus::DELIVERY_MAN_ASSIGN;
                $event->delivery_lat    = $courier->delivery_lat;
                $event->delivery_long   = $courier->delivery_long;
                $event->note            = trim('Handed to ' . $carrierCode . ($awb ? ' - AWB ' . $awb : ''));
                // Null in queue / scheduler context, which the column allows.
                $event->created_by      = Auth::id();
                $event->save();

                $parcel->status = ParcelStatus::DELIVERY_MAN_ASSIGN;
                $parcel->save();
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('3pl.handoff_failed', [
                'parcel_id' => $parcel->id ?? null,
                'carrier'   => $carrierCode,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * The courier standing in for this carrier, or null.
     *
     * Order: the id the caller supplied (a shipping connection's own pick),
     * then the tenant's carrier map. Both are checked against the parcel's
     * tenant - a courier id arriving from a connection row is only as
     * trustworthy as whoever set it, and assigning across tenants would leak
     * a parcel into another company's delivery list.
     */
    private function resolveCourier(int $companyId, string $carrierCode, ?int $courierId): ?DeliveryMan
    {
        if (! $courierId) {
            $courierId = ThreePlCourier::where('company_id', $companyId)
                ->where('carrier_code', $carrierCode)
                ->value('delivery_man_id');
        }

        if (! $courierId) {
            return null;
        }

        return DeliveryMan::where('id', $courierId)
            ->where('company_id', $companyId)
            ->first();
    }
}
