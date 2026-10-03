<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-side operational reports, primarily consumed by the supervisor
 * mobile app. Complements the merchant-scoped MerchantReportsController
 * with tenant-wide + per-driver rollups.
 */
class AdminReportsController extends Controller
{
    use ApiReturnFormatTrait;

    /**
     * GET /admin/reports/drivers?from=&to=&hub_id=
     *
     * Per-driver aggregates for a date range (defaults to last 7 days):
     * parcels, delivered, cod collected. Hub-clamped for HUB/INCHARGE.
     */
    public function drivers(Request $request)
    {
        [$from, $to] = $this->parseRange($request);
        $delivered = [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED];

        // Driver assignment lives on parcel_events, not parcels, so aggregate
        // through the latest driver-bearing event per parcel. Driving this off
        // ParcelEvent keeps the company_id global scope applied; hub_id exists
        // on BOTH tables, so every column here must be qualified.
        $q = ParcelEvent::query()
            ->join('parcels', 'parcels.id', '=', 'parcel_events.parcel_id')
            ->select(
                'parcel_events.delivery_man_id',
                DB::raw('COUNT(*) as parcels'),
                DB::raw(sprintf(
                    'SUM(CASE WHEN parcels.status IN (%s) THEN 1 ELSE 0 END) as delivered',
                    implode(',', $delivered)
                )),
                DB::raw(sprintf(
                    'COALESCE(SUM(CASE WHEN parcels.status IN (%s) THEN parcels.cash_collection ELSE 0 END),0) as cod',
                    implode(',', $delivered)
                ))
            )
            ->whereBetween('parcels.created_at', [$from, $to])
            ->whereRaw('parcel_events.id = (
                select max(pe2.id) from parcel_events pe2
                 where pe2.parcel_id = parcel_events.parcel_id
                   and pe2.delivery_man_id is not null
            )');

        if ($hubId = $request->query('hub_id')) {
            $q->where('parcels.hub_id', (int) $hubId);
        }
        $this->applyHubScope($q, $request, 'parcels.hub_id');

        $agg = $q->groupBy('parcel_events.delivery_man_id')
            ->orderByDesc('parcels')
            ->limit(200)
            ->get();

        $driverNames = DeliveryMan::query()
            ->whereIn('id', $agg->pluck('delivery_man_id'))
            ->with('user:id,name,mobile')
            ->get()
            ->keyBy('id');

        return $this->responseWithSuccess('admin.reports.drivers', [
            'from'    => $from->toDateString(),
            'to'      => $to->toDateString(),
            'drivers' => $agg->map(function ($r) use ($driverNames) {
                $d = $driverNames->get($r->delivery_man_id);
                return [
                    'driver_id'   => (int) $r->delivery_man_id,
                    'driver_name' => optional(optional($d)->user)->name ?? '—',
                    'phone'       => (string) optional(optional($d)->user)->mobile,
                    'parcels'     => (int) $r->parcels,
                    'delivered'   => (int) $r->delivered,
                    'cod'         => (float) $r->cod,
                    'delivery_rate' => $r->parcels > 0
                        ? round($r->delivered / $r->parcels * 100, 1)
                        : 0.0,
                ];
            })->values(),
        ], 200);
    }

    private function parseRange(Request $request): array
    {
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : Carbon::now()->endOfDay();
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();
        return [$from, $to];
    }

    /**
     * @param string $column Qualify as `parcels.hub_id` on joined queries —
     *                       parcel_events also has a hub_id, so an unqualified
     *                       name is ambiguous and MySQL will reject it.
     */
    private function applyHubScope($query, Request $request, string $column = 'hub_id'): void
    {
        $user = $request->user();
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where($column, (int) $user->hub_id);
        }
    }
}
