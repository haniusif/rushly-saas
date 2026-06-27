<?php

namespace App\Services\Performance;

use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Income;
use App\Models\Backend\ParcelRating;
use App\Models\Backend\Support;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate driver-level performance: KPI block, daily time-series, ranking
 * leaderboard, rating distribution proxy. All queries scoped via
 * companywise() (legacy local scope on DeliveryMan) and the parcel_events
 * timeline for per-driver activity inside the date range.
 */
class DriverPerformanceService
{
    /**
     * @param  int  $limit  ranking leaderboard size
     */
    public function payload(PerformanceFilters $f, int $limit = 20): array
    {
        return [
            'kpi'        => $this->kpiBlock($f),
            'ranking'    => $this->ranking($f, $limit),
            'time_series'=> $this->dailySeries($f),
            'rating_distribution' => $this->ratingDistributionProxy($f),
        ];
    }

    /* ---------------- KPI summary ---------------- */

    private function kpiBlock(PerformanceFilters $f): array
    {
        $total       = DeliveryMan::companywise()->count();
        $activeReg   = DeliveryMan::companywise()->where('status', Status::ACTIVE)->count();

        // "Online" — Phase 4: real metric via delivery_man.last_seen_at within 5 min.
        // Falls back to "has parcel_event in last 24h" if last_seen_at is not yet
        // populated for any driver (early hours after the middleware deploys).
        $online = DeliveryMan::companywise()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->count();
        $onlineIsReal = $online > 0;
        if (! $onlineIsReal) {
            $online = DB::table('parcel_events')
                ->where('created_at', '>=', now()->subDay())
                ->whereNotNull('delivery_man_id')
                ->distinct()
                ->count('delivery_man_id');
        }

        $events = DB::table('parcel_events')
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man_id');
        if ($f->hubId)    $events->where('hub_id', $f->hubId);
        if ($f->driverId) $events->where('delivery_man_id', $f->driverId);

        $completedDeliveries = (clone $events)->where('parcel_status', ParcelStatus::DELIVERED)->count();

        // Driver attempts → cancelled = events landing on a cancel status with a driver attached
        $cancelStatuses = [
            ParcelStatus::DELIVERY_MAN_ASSIGN_CANCEL,
            ParcelStatus::DELIVERY_RE_SCHEDULE_CANCEL,
        ];
        $cancelledDeliveries = (clone $events)->whereIn('parcel_status', $cancelStatuses)->count();

        $assignments = (clone $events)->where('parcel_status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count();
        $acceptanceRate = $assignments > 0
            ? round(($completedDeliveries / max($assignments, 1)), 4)
            : null;
        $rejectionRate = $acceptanceRate !== null ? round(1 - $acceptanceRate, 4) : null;

        // Avg pickup time: created_at → RECEIVED_BY_PICKUP_MAN event
        $avgPickupHours = $this->avgHoursBetween(
            $f, ParcelStatus::PENDING, ParcelStatus::RECEIVED_BY_PICKUP_MAN,
        );
        // Avg delivery time: DELIVERY_MAN_ASSIGN → DELIVERED
        $avgDeliveryHours = $this->avgHoursBetween(
            $f, ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERED,
        );

        // Revenue per driver: income earmarked to deliveryman in window / unique active drivers
        $driverRevenue = (float) Income::query()
            ->whereBetween('date', [$f->from->toDateString(), $f->to->toDateString()])
            ->whereNotNull('delivery_man_id')
            ->sum('amount');
        $activeUniqueDrivers = (int) (clone $events)->distinct()->count('delivery_man_id');
        $revenuePerDriver = $activeUniqueDrivers > 0 ? round($driverRevenue / $activeUniqueDrivers, 2) : 0.0;

        // Complaints — Phase 4b: real metric uses supports.driver_id (after the
        // form starts capturing it). Falls back to "all tickets in window" when
        // no driver-linked tickets exist (so we don't suddenly show 0 on first
        // deploy).
        $driverLinkedTickets = Support::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('driver_id');
        if ($f->driverId) $driverLinkedTickets->where('driver_id', $f->driverId);
        $driverLinkedCount = (clone $driverLinkedTickets)->count();
        $complaintsIsReal = $driverLinkedCount > 0;
        $complaints = $complaintsIsReal
            ? $driverLinkedCount
            : Support::query()->whereBetween('created_at', [$f->from, $f->to])->count();

        // Customer rating — Phase 4b: average of parcel_ratings linked to drivers
        // that handled deliveries in the window.
        $ratingRow = ParcelRating::companywise()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->when($f->driverId, fn ($q, $v) => $q->where('deliveryman_id', $v))
            ->selectRaw('AVG(rating) AS avg_r, COUNT(*) AS n')
            ->first();
        $customerRating = (int) ($ratingRow->n ?? 0) > 0 ? round((float) $ratingRow->avg_r, 2) : null;
        $customerRatingCount = (int) ($ratingRow->n ?? 0);

        // Composite performance score for the cohort: blended from rates
        $cohort = PerformanceScoreCalculator::compute([
            'productivity' => $completedDeliveries > 0 ? min(1.0, $completedDeliveries / max($total, 1) / 20) : 0,
            'completion'   => $assignments > 0 ? $completedDeliveries / $assignments : null,
            'rating'       => null,                          // no rating data
            'on_time'      => $this->onTimeRateForDrivers($f),
            'revenue'      => null,                          // covered per-driver in ranking
            'sla'          => null,                          // covered per-driver in ranking
            'growth'       => null,
        ]);

        // Total distance covered (km) — Phase 4 real metric
        $distanceM = (float) DB::table('parcels')
            ->join('parcel_events', 'parcel_events.parcel_id', '=', 'parcels.id')
            ->whereBetween('parcels.delivery_date', [$f->from, $f->to])
            ->where('parcels.status', ParcelStatus::DELIVERED)
            ->when($f->driverId, fn ($q, $v) => $q->where('parcel_events.delivery_man_id', $v))
            ->whereNotNull('parcels.distance_m')
            ->sum('parcels.distance_m');
        $totalDistanceKm = $distanceM > 0 ? round($distanceM / 1000, 1) : null;

        return [
            'total_drivers'        => $total,
            'active_drivers'       => $activeReg,
            'online_drivers'       => $online,
            'online_is_real'       => $onlineIsReal,
            'completed_deliveries' => $completedDeliveries,
            'cancelled_deliveries' => $cancelledDeliveries,
            'acceptance_rate'      => $acceptanceRate,
            'rejection_rate'       => $rejectionRate,
            'avg_pickup_hours'     => $avgPickupHours,
            'avg_delivery_hours'   => $avgDeliveryHours,
            'distance_km'          => $totalDistanceKm,
            'revenue_per_driver'   => $revenuePerDriver,
            'complaints'           => $complaints,
            'complaints_is_real'   => $complaintsIsReal,
            'customer_rating'      => $customerRating,            // out of 5
            'customer_rating_count'=> $customerRatingCount,
            'cohort_score'         => $cohort['score'],
            'cohort_band'          => $cohort['band'],
            'proxies'              => [
                'online_drivers' => $onlineIsReal
                    ? 'real: drivers with last_seen_at in last 5 min (heartbeat from any web/API request)'
                    : 'fallback: distinct drivers with a parcel_event in the last 24h',
                'complaints'     => $complaintsIsReal
                    ? 'real: support_tickets.driver_id linkage'
                    : 'fallback: all support tickets opened in window (no driver link yet)',
                'customer_rating'=> 'real: AVG(parcel_ratings.rating) — captured via public signed URL',
            ],
        ];
    }

    /* ---------------- Leaderboard ranking ---------------- */

    private function ranking(PerformanceFilters $f, int $limit): array
    {
        // Cohort tops for normalization
        $cohort = DB::table('parcel_events')
            ->join('delivery_man', 'parcel_events.delivery_man_id', '=', 'delivery_man.id')
            ->whereBetween('parcel_events.created_at', [$f->from, $f->to])
            ->selectRaw(
                'delivery_man.id AS id,
                 delivery_man.user_id AS user_id,
                 COUNT(DISTINCT CASE WHEN parcel_status = ? THEN parcel_events.parcel_id END) AS delivered,
                 COUNT(DISTINCT parcel_events.parcel_id) AS handled,
                 COUNT(*) AS events',
                [ParcelStatus::DELIVERED],
            )
            ->groupBy('delivery_man.id', 'delivery_man.user_id')
            ->orderByDesc('delivered')
            ->limit($limit)
            ->get();

        if ($cohort->isEmpty()) return [];

        $topDelivered = max(1, (int) $cohort->max('delivered'));

        // Per-driver SLA on-time count (using SLA proxy)
        $driverIds = $cohort->pluck('id')->all();
        $onTimeMap = $this->perDriverOnTimeMap($f, $driverIds);

        // Per-driver customer rating (Phase 4b)
        $ratingMap = ParcelRating::companywise()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereIn('deliveryman_id', $driverIds)
            ->groupBy('deliveryman_id')
            ->selectRaw('deliveryman_id, AVG(rating) AS avg_r, COUNT(*) AS n')
            ->get()
            ->keyBy('deliveryman_id');

        // Driver revenue
        $revenue = Income::query()
            ->whereBetween('date', [$f->from->toDateString(), $f->to->toDateString()])
            ->whereIn('delivery_man_id', $driverIds)
            ->groupBy('delivery_man_id')
            ->selectRaw('delivery_man_id, SUM(amount) AS r')
            ->pluck('r', 'delivery_man_id')
            ->all();
        $topRevenue = max(1, max($revenue ?: [1]));

        // Pull user names
        $userIds = $cohort->pluck('user_id')->all();
        $users = DB::table('users')->whereIn('id', $userIds)->pluck('name', 'id');

        $out = [];
        foreach ($cohort as $row) {
            $delivered = (int) $row->delivered;
            $handled   = (int) $row->handled;
            $rev       = (float) ($revenue[$row->id] ?? 0);
            $onTime    = $onTimeMap[$row->id] ?? null;
            $rating    = isset($ratingMap[$row->id]) ? (float) $ratingMap[$row->id]->avg_r : null;
            $ratingN   = isset($ratingMap[$row->id]) ? (int)   $ratingMap[$row->id]->n     : 0;

            $score = PerformanceScoreCalculator::compute([
                'productivity' => $delivered / $topDelivered,
                'completion'   => $handled > 0 ? $delivered / $handled : null,
                'rating'       => $rating !== null ? $rating / 5.0 : null,
                'on_time'      => $onTime,
                'revenue'      => $rev / $topRevenue,
                'sla'          => null,
                'growth'       => null,
            ]);

            $out[] = [
                'driver_id'   => (int) $row->id,
                'name'        => $users[$row->user_id] ?? ('Driver #' . $row->id),
                'delivered'   => $delivered,
                'handled'     => $handled,
                'completion_rate' => $handled > 0 ? round($delivered / $handled, 4) : 0,
                'on_time_rate'    => $onTime,
                'customer_rating' => $rating !== null ? round($rating, 2) : null,
                'rating_count'    => $ratingN,
                'revenue'         => round($rev, 2),
                'score'           => $score['score'],
                'band'            => $score['band'],
            ];
        }

        usort($out, fn ($a, $b) => $b['score'] <=> $a['score']);
        return $out;
    }

    /** Map driver_id => on-time fraction (proxy via SLA hours). */
    private function perDriverOnTimeMap(PerformanceFilters $f, array $driverIds): array
    {
        if (empty($driverIds)) return [];

        // For each (delivery_type_id), how many of this driver's DELIVERED parcels arrived on time
        $rows = DB::table('parcels')
            ->join('parcel_events', function ($j) {
                $j->on('parcel_events.parcel_id', '=', 'parcels.id')
                  ->where('parcel_events.parcel_status', '=', ParcelStatus::DELIVERED);
            })
            ->whereIn('parcel_events.delivery_man_id', $driverIds)
            ->whereBetween('parcels.delivery_date', [$f->from, $f->to])
            ->where('parcels.status', ParcelStatus::DELIVERED)
            ->selectRaw('parcel_events.delivery_man_id AS did,
                         parcels.delivery_type_id AS dt,
                         COUNT(*) AS total,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date) <= ? THEN 1 ELSE 0 END) AS sd,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date) <= ? THEN 1 ELSE 0 END) AS nd,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date) <= ? THEN 1 ELSE 0 END) AS sc,
                         SUM(CASE WHEN TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date) <= ? THEN 1 ELSE 0 END) AS oc',
                [SlaProxy::HOURS[1], SlaProxy::HOURS[2], SlaProxy::HOURS[3], SlaProxy::HOURS[4]]
            )
            ->groupBy('parcel_events.delivery_man_id', 'parcels.delivery_type_id')
            ->get();

        $acc = [];
        foreach ($rows as $r) {
            $did = (int) $r->did;
            $acc[$did]['total'] = ($acc[$did]['total'] ?? 0) + (int) $r->total;
            $acc[$did]['ontime'] = ($acc[$did]['ontime'] ?? 0) + match ((int) $r->dt) {
                1 => (int) $r->sd, 2 => (int) $r->nd, 3 => (int) $r->sc, 4 => (int) $r->oc,
                default => (int) $r->sc,
            };
        }

        $out = [];
        foreach ($acc as $did => $a) {
            $out[$did] = $a['total'] > 0 ? round($a['ontime'] / $a['total'], 4) : null;
        }
        return $out;
    }

    /* ---------------- Daily time series for charts ---------------- */

    private function dailySeries(PerformanceFilters $f): array
    {
        $rows = DB::table('parcel_events')
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man_id')
            ->selectRaw('DATE(created_at) AS day,
                         SUM(CASE WHEN parcel_status = ? THEN 1 ELSE 0 END) AS delivered,
                         SUM(CASE WHEN parcel_status = ? THEN 1 ELSE 0 END) AS assigned,
                         COUNT(*) AS total',
                [ParcelStatus::DELIVERED, ParcelStatus::DELIVERY_MAN_ASSIGN]
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        $cursor = $f->from->copy()->startOfDay();
        $end    = $f->to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $r = $rows[$key] ?? null;
            $days[] = [
                'date'      => $key,
                'label'     => $cursor->format('M j'),
                'delivered' => $r ? (int) $r->delivered : 0,
                'assigned'  => $r ? (int) $r->assigned  : 0,
                'total'     => $r ? (int) $r->total     : 0,
            ];
            $cursor->addDay();
        }
        return $days;
    }

    /* ---------------- Rating distribution (proxy: completion-rate buckets) ---------------- */

    private function ratingDistributionProxy(PerformanceFilters $f): array
    {
        $perDriver = DB::table('parcel_events')
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereNotNull('delivery_man_id')
            ->selectRaw('delivery_man_id,
                         SUM(CASE WHEN parcel_status = ? THEN 1 ELSE 0 END) AS delivered,
                         COUNT(DISTINCT parcel_id) AS handled',
                [ParcelStatus::DELIVERED]
            )
            ->groupBy('delivery_man_id')
            ->get();

        $buckets = ['5★' => 0, '4★' => 0, '3★' => 0, '2★' => 0, '1★' => 0];
        foreach ($perDriver as $r) {
            $rate = $r->handled > 0 ? $r->delivered / $r->handled : 0;
            $bucket = match (true) {
                $rate >= 0.95 => '5★',
                $rate >= 0.85 => '4★',
                $rate >= 0.70 => '3★',
                $rate >= 0.50 => '2★',
                default       => '1★',
            };
            $buckets[$bucket]++;
        }
        return collect($buckets)->map(fn ($n, $k) => ['bucket' => $k, 'count' => $n])->values()->all();
    }

    /* ---------------- helpers ---------------- */

    private function avgHoursBetween(PerformanceFilters $f, int $fromStatus, int $toStatus): ?float
    {
        $row = DB::table('parcels AS p')
            ->join('parcel_events AS e_start', function ($j) use ($fromStatus) {
                $j->on('e_start.parcel_id', '=', 'p.id')
                  ->where('e_start.parcel_status', '=', $fromStatus);
            })
            ->join('parcel_events AS e_end', function ($j) use ($toStatus) {
                $j->on('e_end.parcel_id', '=', 'p.id')
                  ->where('e_end.parcel_status', '=', $toStatus);
            })
            ->whereBetween('e_end.created_at', [$f->from, $f->to])
            ->when($f->hubId,    fn ($q, $v) => $q->where('p.hub_id', $v))
            ->when($f->driverId, fn ($q, $v) => $q->where('e_end.delivery_man_id', $v))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, e_start.created_at, e_end.created_at)) AS h')
            ->first();

        return $row && $row->h !== null ? round((float) $row->h, 1) : null;
    }

    private function onTimeRateForDrivers(PerformanceFilters $f): ?float
    {
        // Real metric when expected_delivery_at is set, proxy otherwise — one query.
        $row = DB::table('parcels')
            ->join('parcel_events', function ($j) {
                $j->on('parcel_events.parcel_id', '=', 'parcels.id')
                  ->where('parcel_events.parcel_status', '=', ParcelStatus::DELIVERED);
            })
            ->whereBetween('parcels.delivery_date', [$f->from, $f->to])
            ->where('parcels.status', ParcelStatus::DELIVERED)
            ->selectRaw('COUNT(*) AS total,
                         SUM(CASE
                                WHEN parcels.expected_delivery_at IS NOT NULL
                                     THEN CASE WHEN parcels.delivery_date <= parcels.expected_delivery_at THEN 1 ELSE 0 END
                                ELSE CASE WHEN TIMESTAMPDIFF(HOUR, parcels.created_at, parcels.delivery_date) <=
                                              CASE parcels.delivery_type_id
                                                 WHEN 1 THEN ? WHEN 2 THEN ? WHEN 3 THEN ? WHEN 4 THEN ?
                                                 ELSE ?
                                              END THEN 1 ELSE 0 END
                             END) AS ontime',
                [SlaProxy::HOURS[1], SlaProxy::HOURS[2], SlaProxy::HOURS[3], SlaProxy::HOURS[4], SlaProxy::DEFAULT_HOURS]
            )
            ->first();
        if (! $row || ! $row->total) return null;
        return round((int) $row->ontime / (int) $row->total, 4);
    }
}
