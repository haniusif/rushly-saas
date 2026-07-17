<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
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

        $q = Parcel::query()
            ->select(
                'delivery_man_id',
                DB::raw('COUNT(*) as parcels'),
                DB::raw(sprintf(
                    'SUM(CASE WHEN status IN (%s) THEN 1 ELSE 0 END) as delivered',
                    implode(',', $delivered)
                )),
                DB::raw(sprintf(
                    'COALESCE(SUM(CASE WHEN status IN (%s) THEN cash_collection ELSE 0 END),0) as cod',
                    implode(',', $delivered)
                ))
            )
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('delivery_man_id');

        if ($hubId = $request->query('hub_id')) {
            $q->where('hub_id', (int) $hubId);
        }
        $this->applyHubScope($q, $request);

        $agg = $q->groupBy('delivery_man_id')
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

    private function applyHubScope($query, Request $request): void
    {
        $user = $request->user();
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where('hub_id', (int) $user->hub_id);
        }
    }
}
