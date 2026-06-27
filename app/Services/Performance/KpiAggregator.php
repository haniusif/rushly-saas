<?php

namespace App\Services\Performance;

use App\Enums\ParcelStatus;
use App\Models\Backend\AbnormalShipment;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Expense;
use App\Models\Backend\Hub;
use App\Models\Backend\Income;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelRating;
use App\Models\Backend\Support;
use App\Models\Backend\SupplierCompany;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Executive KPI grid for the Performance Dashboard.
 *
 * Every method applies the existing companywise() global scope (set on the
 * Parcel model and the legacy scopeCompanywise() on others), and respects the
 * filters DTO. KPIs that the data layer can't express today are returned with
 * `proxy: true` and a `note` explaining the substitute.
 */
class KpiAggregator
{
    /** Cancelled / cancellation-bucket statuses. */
    private const CANCELLED_STATUSES = [
        ParcelStatus::PICKUP_ASSIGN_CANCEL,
        ParcelStatus::RECEIVED_BY_PICKUP_MAN_CANCEL,
        ParcelStatus::RECEIVED_WAREHOUSE_CANCEL,
        ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
        ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL,
        ParcelStatus::TRANSFER_TO_HUB_CANCEL,
        ParcelStatus::RECEIVED_BY_HUB_CANCEL,
        ParcelStatus::DELIVERED_CANCEL,
        ParcelStatus::PICKUP_RE_SCHEDULE_CANCEL,
        ParcelStatus::RETURN_TO_COURIER_CANCEL,
        ParcelStatus::RETURN_MERCHANT_RE_SCHEDULE_CANCEL,
        ParcelStatus::RETURN_ASSIGN_TO_MERCHANT_CANCEL,
    ];

    public function all(PerformanceFilters $f): array
    {
        return [
            'meta'    => [
                'from'        => $f->from->toDateString(),
                'to'          => $f->to->toDateString(),
                'days'        => $f->days(),
                'generated_at'=> CarbonImmutable::now()->toIso8601String(),
            ],
            'orders'  => $this->orders($f),
            'financial' => $this->financial($f),
            'activity'  => $this->activity($f),
            'service'   => $this->service($f),
        ];
    }

    /* ---------------- Orders ---------------- */

    private function orders(PerformanceFilters $f): array
    {
        $base = Parcel::query()->whereBetween('created_at', [$f->from, $f->to]);
        $base = $this->applyParcelFilters($base, $f);

        $total      = (clone $base)->count();
        $completed  = (clone $base)->where('status', ParcelStatus::DELIVERED)->count();
        $cancelled  = (clone $base)->whereIn('status', self::CANCELLED_STATUSES)->count();
        $pending    = max(0, $total - $completed - $cancelled);

        // Growth vs previous period (same length)
        $prev = $f->previousPeriod();
        $prevTotal = $this->applyParcelFilters(
                Parcel::query()->whereBetween('created_at', [$prev->from, $prev->to]),
                $prev
            )->count();
        $growthRate = $prevTotal > 0 ? ($total - $prevTotal) / $prevTotal : null;

        return [
            'total'       => $total,
            'completed'   => $completed,
            'pending'     => $pending,
            'cancelled'   => $cancelled,
            'completion_rate' => $total > 0 ? round($completed / $total, 4) : 0,
            'growth_rate' => $growthRate !== null ? round($growthRate, 4) : null,
            'previous_total' => $prevTotal,
        ];
    }

    /* ---------------- Financial ---------------- */

    private function financial(PerformanceFilters $f): array
    {
        $revenue = (float) Income::query()
            ->whereBetween('date', [$f->from->toDateString(), $f->to->toDateString()])
            ->sum('amount');
        $expense = (float) Expense::query()
            ->whereBetween('date', [$f->from->toDateString(), $f->to->toDateString()])
            ->sum('amount');

        return [
            'revenue'      => round($revenue, 2),
            'expenses'     => round($expense, 2),
            'profit'       => round($revenue - $expense, 2),
            'currency'     => settings()->currency,
        ];
    }

    /* ---------------- Activity (active drivers, customers, branches) ---------------- */

    private function activity(PerformanceFilters $f): array
    {
        // "Active drivers" = drivers that appear in parcel_events in the window.
        // parcel_events has no company_id column; filter via the parcel_id
        // subquery so we don't pull events from sibling tenants.
        $activeDrivers = DB::table('parcel_events')
            ->whereIn('parcel_id', DB::table('parcels')->select('id')->where('company_id', settings()->id))
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man_id')
            ->distinct()
            ->count('delivery_man_id');

        // "Active customers" = merchants that created at least one parcel in window
        $activeCustomers = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->distinct('merchant_id')
            ->count('merchant_id');

        $activeBranches = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('hub_id')
            ->distinct('hub_id')
            ->count('hub_id');

        $totalDrivers    = DeliveryMan::companywise()->count();
        $totalCustomers  = Merchant::companywise()->count();
        $totalBranches   = Hub::companywise()->count();
        $operatingCompanies = SupplierCompany::query()
            ->where('company_id', settings()->id)
            ->count(); // "Operating companies" → 3PL partners (in tenant scope)

        return [
            'active_drivers'      => $activeDrivers,
            'total_drivers'       => $totalDrivers,
            'active_customers'    => $activeCustomers,
            'total_customers'     => $totalCustomers,
            'active_branches'     => $activeBranches,
            'total_branches'      => $totalBranches,
            'active_companies'    => $operatingCompanies,  // tenant-scoped 3PL count
        ];
    }

    /* ---------------- Service quality (SLA, satisfaction proxy, delivery time) ---------------- */

    private function service(PerformanceFilters $f): array
    {
        // Avg delivery time: created_at → delivery_date for DELIVERED parcels in window
        $delivered = Parcel::query()
            ->whereBetween('delivery_date', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED);
        $delivered = $this->applyParcelFilters($delivered, $f);

        $avgHours = (float) (clone $delivered)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivery_date)) AS h')
            ->value('h');

        // On-time rate (proxy via SlaProxy hours per delivery_type)
        $onTimeRate = $this->onTimeRateProxy($f);

        // SLA compliance: 1 - (open abnormal in window) / total parcels in window
        $abnormalOpen = AbnormalShipment::query()
            ->whereBetween('detected_at', [$f->from, $f->to])
            ->whereNotIn('status', ['resolved', 'closed_lost'])
            ->count();
        $totalParcels = Parcel::query()->whereBetween('created_at', [$f->from, $f->to])->count();
        $slaCompliance = $totalParcels > 0
            ? max(0.0, 1.0 - ($abnormalOpen / max($totalParcels, 1)))
            : null;

        // Customer satisfaction.
        // Phase 4b: real metric when parcel_ratings has entries in the window —
        //   avg(rating) / 5. Falls back to the support-ticket proxy when no
        //   ratings exist yet (early hours after deploy, or for old ranges).
        $ratingsRow = ParcelRating::companywise()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->selectRaw('AVG(rating) AS avg_r, COUNT(*) AS n')
            ->first();
        $tickets = Support::query()->whereBetween('created_at', [$f->from, $f->to])->count();

        $satisfactionIsReal = (int) ($ratingsRow->n ?? 0) > 0;
        if ($satisfactionIsReal) {
            $satisfaction = round(((float) $ratingsRow->avg_r) / 5.0, 4);
        } else {
            $satisfaction = $totalParcels > 0
                ? max(0.0, min(1.0, 1.0 - ($tickets / max($totalParcels, 1))))
                : null;
        }

        // Avg distance (km) of delivered parcels — Phase 4 column
        $avgDistanceM = (float) (clone $delivered)
            ->whereNotNull('distance_m')
            ->avg('distance_m');
        $avgDistanceKm = $avgDistanceM > 0 ? round($avgDistanceM / 1000, 2) : null;

        $onTimeIsReal = $this->hasRealOnTimeData($f);

        return [
            'avg_delivery_hours' => $avgHours > 0 ? round($avgHours, 1) : null,
            'avg_distance_km'    => $avgDistanceKm,
            'on_time_rate'       => $onTimeRate,
            'on_time_is_real'    => $onTimeIsReal,        // false ⇒ proxy fallback used
            'sla_compliance'     => $slaCompliance !== null ? round($slaCompliance, 4) : null,
            'satisfaction'       => $satisfaction !== null ? round($satisfaction, 4) : null,
            'satisfaction_is_real' => $satisfactionIsReal,
            'ratings_count'      => (int) ($ratingsRow->n ?? 0),
            'abnormal_open'      => $abnormalOpen,
            'support_tickets'    => $tickets,
            'proxies'            => [
                'on_time_rate' => $onTimeIsReal
                    ? 'real: delivery_date ≤ expected_delivery_at (proxy fallback for legacy rows)'
                    : 'derived from delivery_type assumed SLA hours (24/48/72/120)',
                'satisfaction' => $satisfactionIsReal
                    ? 'real: AVG(parcel_ratings.rating) / 5'
                    : '1 − support_tickets / orders',
            ],
        ];
    }

    /**
     * On-time rate.
     *
     * **Real metric when `expected_delivery_at` is populated** (Phase 4):
     *   on-time = delivery_date <= expected_delivery_at
     *
     * Falls back to the SLA-proxy formula for rows where expected_delivery_at
     * is NULL (historical parcels that haven't been backfilled yet). The
     * returned rate is the weighted average across both buckets.
     */
    private function onTimeRateProxy(PerformanceFilters $f): ?float
    {
        $base = Parcel::query()
            ->whereBetween('delivery_date', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED);
        $base = $this->applyParcelFilters($base, $f);

        // ---- Real metric: rows with expected_delivery_at ----
        $real = (clone $base)
            ->whereNotNull('expected_delivery_at')
            ->selectRaw('COUNT(*) AS total,
                         SUM(CASE WHEN delivery_date <= expected_delivery_at THEN 1 ELSE 0 END) AS ontime')
            ->first();

        // ---- Proxy: rows without expected_delivery_at, by delivery_type ----
        $rows = (clone $base)->whereNull('expected_delivery_at')
            ->selectRaw('delivery_type_id, COUNT(*) AS total,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, delivery_date) <= ? THEN 1 ELSE 0 END) AS on_time_sd,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, delivery_date) <= ? THEN 1 ELSE 0 END) AS on_time_nd,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, delivery_date) <= ? THEN 1 ELSE 0 END) AS on_time_sc,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, delivery_date) <= ? THEN 1 ELSE 0 END) AS on_time_oc',
                [SlaProxy::HOURS[1], SlaProxy::HOURS[2], SlaProxy::HOURS[3], SlaProxy::HOURS[4]]
            )
            ->groupBy('delivery_type_id')
            ->get();

        $totalDelivered = (int) ($real->total ?? 0);
        $totalOnTime    = (int) ($real->ontime ?? 0);
        foreach ($rows as $row) {
            $totalDelivered += (int) $row->total;
            $totalOnTime    += match ((int) $row->delivery_type_id) {
                1 => (int) $row->on_time_sd,
                2 => (int) $row->on_time_nd,
                3 => (int) $row->on_time_sc,
                4 => (int) $row->on_time_oc,
                default => (int) $row->on_time_sc,
            };
        }

        return $totalDelivered > 0 ? round($totalOnTime / $totalDelivered, 4) : null;
    }

    /** Public flag: did this aggregator use the real on-time metric? */
    public function hasRealOnTimeData(PerformanceFilters $f): bool
    {
        return Parcel::query()
            ->whereBetween('delivery_date', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED)
            ->whereNotNull('expected_delivery_at')
            ->exists();
    }

    /* ---------------- helpers ---------------- */

    /** Apply driver/hub/merchant/delivery-type filters to a Parcel-query builder. */
    private function applyParcelFilters($query, PerformanceFilters $f)
    {
        if ($f->hubId)          $query->where('hub_id', $f->hubId);
        if ($f->merchantId)     $query->where('merchant_id', $f->merchantId);
        if ($f->deliveryTypeId) $query->where('delivery_type_id', $f->deliveryTypeId);
        if ($f->driverId) {
            // Driver matches via parcel_events delivery_man_id
            $query->whereIn('id', function ($sub) use ($f) {
                $sub->from('parcel_events')->select('parcel_id')->where('delivery_man_id', $f->driverId);
            });
        }
        return $query;
    }
}
