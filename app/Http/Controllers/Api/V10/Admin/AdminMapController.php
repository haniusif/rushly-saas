<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Feeds the driver-assignment map in the admin mobile app.
 *
 * Two lightweight geo endpoints; both auto-clamp to the caller's hub when
 * the user is a HUB or INCHARGE role (mirrors the pattern in the other
 * admin controllers).
 */
class AdminMapController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * Parcels awaiting assignment that have usable customer coordinates.
     * "Awaiting" = no delivery_man_id yet AND status is still open (not
     * delivered / returned / cancelled).
     */
    public function parcels(Request $request)
    {
        $openStatuses = [
            ParcelStatus::PENDING,
            ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::PICKUP_RE_SCHEDULE,
            ParcelStatus::RECEIVED_BY_PICKUP_MAN,
            ParcelStatus::RECEIVED_WAREHOUSE,
            ParcelStatus::TRANSFER_TO_HUB,
            ParcelStatus::RECEIVED_BY_HUB,
            ParcelStatus::DELIVERY_MAN_ASSIGN,
            ParcelStatus::DELIVERY_RE_SCHEDULE,
        ];

        $query = Parcel::query()
            ->with(['merchant:id,business_name'])
            ->hasDriverAssigned(false)
            ->whereIn('status', $openStatuses)
            ->whereNotNull('customer_lat')
            ->whereNotNull('customer_long');

        if ($hubId = $request->query('hub_id')) {
            $query->where('hub_id', (int) $hubId);
        }
        $this->clampToHub($query, $request->user());

        $limit = max(20, min(500, (int) $request->query('limit', 200)));
        $rows = $query->latest()->limit($limit)->get();

        return $this->responseWithSuccess('admin.map.parcels', [
            'parcels' => $rows->map(fn (Parcel $p) => [
                'id'             => $p->id,
                'tracking_id'    => $p->tracking_id,
                'status'         => (int) $p->status,
                'lat'            => (float) $p->customer_lat,
                'lng'            => (float) $p->customer_long,
                'customer_name'  => $p->customer_name,
                'customer_phone' => $p->customer_phone,
                'hub_id'         => $p->hub_id,
                'merchant_name'  => optional($p->merchant)->business_name,
                'cod'            => (float) ($p->cash_collection ?? 0),
            ])->values(),
        ], 200);
    }

    /**
     * Active drivers with their last-known location (from their most
     * recent parcel event, since there's no dedicated driver_locations
     * table yet).
     */
    public function drivers(Request $request)
    {
        $query = DeliveryMan::query()
            ->with(['user:id,name,mobile,status', 'hub:id,name']);

        if ($hubId = $request->query('hub_id')) {
            $query->where('hub_id', (int) $hubId);
        }
        $this->clampToHub($query, $request->user());

        $drivers = $query->limit(500)->get();
        if ($drivers->isEmpty()) {
            return $this->responseWithSuccess('admin.map.drivers', ['drivers' => []], 200);
        }

        $ids = $drivers->pluck('id')->all();

        // For each driver, the last event we know their coords from.
        $latestEventIds = ParcelEvent::query()
            ->select(DB::raw('MAX(id) as id'))
            ->whereIn('delivery_man_id', $ids)
            ->whereNotNull('delivery_lat')
            ->whereNotNull('delivery_long')
            ->groupBy('delivery_man_id')
            ->pluck('id');

        $lastEvents = ParcelEvent::whereIn('id', $latestEventIds)
            ->get(['id', 'delivery_man_id', 'delivery_lat', 'delivery_long', 'updated_at'])
            ->keyBy('delivery_man_id');

        // Current load = parcels currently assigned but not delivered.
        // Driver assignment lives on parcel_events, not parcels, so count
        // through the latest driver-bearing event per parcel. Driving this
        // off ParcelEvent keeps the company_id global scope applied.
        $loadCounts = ParcelEvent::query()
            ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
            ->whereIn('parcel_events.delivery_man_id', $ids)
            ->whereRaw('parcel_events.id = (
                select max(pe2.id) from parcel_events pe2
                 where pe2.parcel_id = parcel_events.parcel_id
                   and pe2.delivery_man_id is not null
            )')
            ->whereNotIn('parcels.status', [
                ParcelStatus::DELIVERED,
                ParcelStatus::RETURNED_MERCHANT,
                ParcelStatus::RETURN_RECEIVED_BY_MERCHANT,
            ])
            ->select('parcel_events.delivery_man_id', DB::raw('count(*) as c'))
            ->groupBy('parcel_events.delivery_man_id')
            ->pluck('c', 'delivery_man_id');

        return $this->responseWithSuccess('admin.map.drivers', [
            'drivers' => $drivers->map(function (DeliveryMan $d) use ($lastEvents, $loadCounts) {
                $ev = $lastEvents->get($d->id);
                return [
                    'id'        => $d->id,
                    'name'      => optional($d->user)->name,
                    'phone'     => (string) optional($d->user)->mobile,
                    'status'    => (int) optional($d->user)->status,
                    'hub_id'    => $d->hub_id,
                    'hub_name'  => optional($d->hub)->name,
                    'load'      => (int) ($loadCounts[$d->id] ?? 0),
                    'lat'       => $ev ? (float) $ev->delivery_lat : null,
                    'lng'       => $ev ? (float) $ev->delivery_long : null,
                    'seen_at'   => $ev ? optional($ev->updated_at)->toIso8601String() : null,
                ];
            })->values(),
        ], 200);
    }

    private function clampToHub($query, $user): void
    {
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where('hub_id', (int) $user->hub_id);
        }
    }
}
