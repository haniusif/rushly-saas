<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Merchant-facing shipment reports for the mobile app.
 *
 * Feeds the Reports screen with per-driver, per-city, per-status, and
 * daily time-series breakdowns for the merchant's parcels within a
 * date range (defaults to last 30 days).
 */
class MerchantReportsController extends Controller
{
    use ApiReturnFormatTrait;

    public function shipments(Request $request)
    {
        $merchantId = Auth::user()->merchant->id ?? null;
        if (!$merchantId) {
            return $this->responseWithError('No merchant scope', [], 403);
        }

        [$from, $to] = $this->parseRange($request);

        $base = fn () => Parcel::query()
            ->where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$from, $to]);

        $delivered = [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED];

        // Totals
        $totalParcels   = $base()->count();
        $totalDelivered = $base()->whereIn('status', $delivered)->count();
        $totalReturned  = $base()->where('status', ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->count();
        $totalCod       = (float) $base()->whereIn('status', $delivered)->sum('cash_collection');
        $totalPayable   = (float) $base()->whereIn('status', $delivered)->sum('current_payable');

        // By driver (Parcel has no deliveryMan relation, so look them up in
        // a second query keyed by delivery_man_id → user.name).
        $driverAgg = Parcel::query()
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
            ->where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('delivery_man_id')
            ->groupBy('delivery_man_id')
            ->orderByDesc('parcels')
            ->limit(50)
            ->get();
        $driverNames = DeliveryMan::query()
            ->whereIn('id', $driverAgg->pluck('delivery_man_id'))
            ->with('user:id,name')
            ->get()
            ->keyBy('id');
        $byDriver = $driverAgg->map(fn ($r) => [
            'driver_id'   => (int) $r->delivery_man_id,
            'driver_name' => optional(
                optional($driverNames->get($r->delivery_man_id))->user
            )->name ?? '—',
            'parcels'     => (int) $r->parcels,
            'delivered'   => (int) $r->delivered,
            'cod'         => (float) $r->cod,
        ]);

        // By city
        $byCity = Parcel::query()
            ->select(
                'city_id',
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
            ->where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('city_id')
            ->groupBy('city_id')
            ->orderByDesc('parcels')
            ->limit(50)
            ->with('city:id,en_name,name')
            ->get()
            ->map(fn ($r) => [
                'city_id'   => (int) $r->city_id,
                'city_name' => optional($r->city)->en_name ?? optional($r->city)->name ?? '—',
                'parcels'   => (int) $r->parcels,
                'delivered' => (int) $r->delivered,
                'cod'       => (float) $r->cod,
            ]);

        // By status
        $byStatus = Parcel::query()
            ->select('status', DB::raw('COUNT(*) as parcels'))
            ->where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status'  => (int) $r->status,
                'parcels' => (int) $r->parcels,
            ]);

        // Daily timeseries
        $daily = Parcel::query()
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as parcels'),
                DB::raw(sprintf(
                    'SUM(CASE WHEN status IN (%s) THEN 1 ELSE 0 END) as delivered',
                    implode(',', $delivered)
                ))
            )
            ->where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'date'      => (string) $r->day,
                'parcels'   => (int) $r->parcels,
                'delivered' => (int) $r->delivered,
            ]);

        return $this->responseWithSuccess('reports.shipments', [
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
            'totals' => [
                'parcels'   => $totalParcels,
                'delivered' => $totalDelivered,
                'returned'  => $totalReturned,
                'cod'       => $totalCod,
                'payable'   => $totalPayable,
            ],
            'by_driver' => $byDriver,
            'by_city'   => $byCity,
            'by_status' => $byStatus,
            'daily'     => $daily,
        ], 200);
    }

    private function parseRange(Request $request): array
    {
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : Carbon::now()->endOfDay();
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : Carbon::now()->subDays(29)->startOfDay();
        return [$from, $to];
    }
}
