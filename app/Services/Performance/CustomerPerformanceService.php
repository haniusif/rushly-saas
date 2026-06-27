<?php

namespace App\Services\Performance;

use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Support;
use Illuminate\Support\Facades\DB;

/**
 * Customer (merchant) performance aggregations.
 *
 * Reuses the Parcel global companywise() scope and the legacy
 * Merchant::scopeCompanywise(). All money figures come from
 * `parcels.cash_collection` (already used by existing merchant dashboard).
 *
 * "Lost" / churn — merchants that placed orders BEFORE the current window
 * but had ZERO orders inside it. "Returning" — merchants with ≥ 2 orders in
 * the window. Retention = current_active / prior_active.
 */
class CustomerPerformanceService
{
    private const CANCEL_STATUSES = [
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

    public function payload(PerformanceFilters $f, int $limit = 20): array
    {
        return [
            'kpi'        => $this->kpiBlock($f),
            'top'        => $this->topCustomers($f, $limit),
            'segments'   => $this->segments($f),
            'growth'     => $this->growthSeries($f),
            'churn'      => $this->churnSnapshot($f),
        ];
    }

    /* ---------------- KPI block ---------------- */

    private function kpiBlock(PerformanceFilters $f): array
    {
        $totalCustomers = Merchant::companywise()->count();

        // Active = distinct merchants with parcels in window
        $activeCurrent = (int) Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->distinct('merchant_id')->count('merchant_id');

        // Previous-period active for retention math
        $prev = $f->previousPeriod();
        $activePrev = (int) Parcel::query()
            ->whereBetween('created_at', [$prev->from, $prev->to])
            ->distinct('merchant_id')->count('merchant_id');

        // New = merchants that signed up inside the window
        $new = Merchant::companywise()
            ->whereBetween('created_at', [$f->from, $f->to])->count();

        // Returning = merchants with ≥ 2 orders in window
        $returning = (int) Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->groupBy('merchant_id')
            ->selectRaw('merchant_id, COUNT(*) AS n')
            ->havingRaw('n >= 2')
            ->get()->count();

        // Lost / churn — had any order before window-start but none in window
        $lost = (int) Parcel::query()
            ->where('created_at', '<', $f->from)
            ->whereNotIn('merchant_id', function ($q) use ($f) {
                $q->select('merchant_id')->from('parcels')
                  ->whereBetween('created_at', [$f->from, $f->to]);
            })
            ->distinct('merchant_id')->count('merchant_id');

        // Window orders + revenue (cash_collection of DELIVERED parcels in window)
        $windowOrders = Parcel::query()->whereBetween('created_at', [$f->from, $f->to])->count();
        $windowRevenue = (float) Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED)
            ->sum('cash_collection');

        $aov = $windowOrders > 0 ? round($windowRevenue / $windowOrders, 2) : 0.0;
        $orderFreq = ($activeCurrent > 0 && $f->days() > 0)
            ? round($windowOrders / max($activeCurrent, 1) / max($f->days(), 1), 3)
            : 0.0;

        $cancelled = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->whereIn('status', self::CANCEL_STATUSES)->count();
        $cancellationRate = $windowOrders > 0 ? round($cancelled / $windowOrders, 4) : 0.0;

        // Retention = active_now / active_prev (proxy)
        $retention = $activePrev > 0 ? round($activeCurrent / $activePrev, 4) : null;

        // LTV = avg of all-time cash_collection of DELIVERED parcels per merchant
        $ltv = (float) Parcel::query()
            ->where('status', ParcelStatus::DELIVERED)
            ->sum('cash_collection');
        $avgLtv = $totalCustomers > 0 ? round($ltv / $totalCustomers, 2) : 0.0;

        // Customer satisfaction PROXY: 1 - tickets/orders (same as exec view)
        $tickets = Support::query()->whereBetween('created_at', [$f->from, $f->to])->count();
        $satisfaction = $windowOrders > 0
            ? max(0.0, min(1.0, 1.0 - ($tickets / max($windowOrders, 1))))
            : null;

        // Cohort performance score: blend retention + completion + satisfaction + growth
        $delivered = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED)->count();
        $completion = $windowOrders > 0 ? $delivered / $windowOrders : null;
        $growth = $activePrev > 0 ? max(0.0, min(1.0, ($activeCurrent - $activePrev) / max($activePrev, 1))) : null;

        $cohort = PerformanceScoreCalculator::compute([
            'productivity' => $totalCustomers > 0 ? min(1.0, $activeCurrent / max($totalCustomers, 1)) : 0,
            'completion'   => $completion,
            'rating'       => $satisfaction,
            'on_time'      => null,
            'revenue'      => null,
            'sla'          => null,
            'growth'       => $growth,
        ]);

        return [
            'total_customers'    => $totalCustomers,
            'active_customers'   => $activeCurrent,
            'new_customers'      => $new,
            'returning_customers'=> $returning,
            'lost_customers'     => $lost,
            'lifetime_value'     => $avgLtv,
            'avg_order_value'    => $aov,
            'total_spending'     => round($windowRevenue, 2),
            'order_frequency'    => $orderFreq,             // orders / customer / day
            'cancellation_rate'  => $cancellationRate,
            'retention_rate'     => $retention,
            'satisfaction'       => $satisfaction !== null ? round($satisfaction, 4) : null,
            'cohort_score'       => $cohort['score'],
            'cohort_band'        => $cohort['band'],
            'proxies'            => [
                'satisfaction'  => '1 − support_tickets / orders',
                'retention'     => 'active_in_window / active_in_previous_window',
                'lifetime_value'=> 'all-time delivered cash_collection ÷ total customers',
            ],
        ];
    }

    /* ---------------- Top customers leaderboard ---------------- */

    private function topCustomers(PerformanceFilters $f, int $limit): array
    {
        $rows = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->selectRaw(
                'merchant_id,
                 COUNT(*) AS orders,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS delivered,
                 SUM(CASE WHEN status = ? THEN cash_collection ELSE 0 END) AS revenue',
                [ParcelStatus::DELIVERED, ParcelStatus::DELIVERED]
            )
            ->groupBy('merchant_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) return [];

        $topRevenue = max(1, (float) $rows->max('revenue'));
        $topOrders  = max(1, (int) $rows->max('orders'));

        $names = Merchant::query()
            ->whereIn('id', $rows->pluck('merchant_id'))
            ->pluck('business_name', 'id');

        $out = [];
        foreach ($rows as $r) {
            $orders    = (int) $r->orders;
            $delivered = (int) $r->delivered;
            $revenue   = (float) $r->revenue;
            $completion = $orders > 0 ? $delivered / $orders : null;

            $score = PerformanceScoreCalculator::compute([
                'productivity' => $orders / $topOrders,
                'completion'   => $completion,
                'rating'       => null,
                'on_time'      => null,
                'revenue'      => $revenue / $topRevenue,
                'sla'          => null,
                'growth'       => null,
            ]);

            $out[] = [
                'merchant_id'   => (int) $r->merchant_id,
                'name'          => $names[$r->merchant_id] ?? ('Customer #' . $r->merchant_id),
                'orders'        => $orders,
                'delivered'     => $delivered,
                'completion_rate'=> $completion !== null ? round($completion, 4) : null,
                'revenue'       => round($revenue, 2),
                'aov'           => $orders > 0 ? round($revenue / $orders, 2) : 0,
                'score'         => $score['score'],
                'band'          => $score['band'],
            ];
        }
        return $out;
    }

    /* ---------------- Segments (by spend tier) ---------------- */

    private function segments(PerformanceFilters $f): array
    {
        $rows = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED)
            ->selectRaw('merchant_id, SUM(cash_collection) AS spend')
            ->groupBy('merchant_id')->get();

        $buckets = [
            ['label' => 'VIP (>$10k)',    'count' => 0, 'min' => 10000,  'max' => INF],
            ['label' => 'High ($2k–10k)', 'count' => 0, 'min' => 2000,   'max' => 10000],
            ['label' => 'Mid ($500–2k)',  'count' => 0, 'min' => 500,    'max' => 2000],
            ['label' => 'Low (<$500)',    'count' => 0, 'min' => 0,      'max' => 500],
        ];

        foreach ($rows as $r) {
            $s = (float) $r->spend;
            foreach ($buckets as &$b) {
                if ($s >= $b['min'] && $s < $b['max']) { $b['count']++; break; }
            }
        }
        unset($b);

        return collect($buckets)->map(fn ($b) => ['label' => $b['label'], 'count' => $b['count']])->values()->all();
    }

    /* ---------------- Growth time-series (new vs returning per day) ---------------- */

    private function growthSeries(PerformanceFilters $f): array
    {
        $signups = Merchant::companywise()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS n')
            ->groupBy('day')->pluck('n', 'day');

        $orderCustomers = Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->selectRaw('DATE(created_at) AS day, COUNT(DISTINCT merchant_id) AS n')
            ->groupBy('day')->pluck('n', 'day');

        $days = [];
        $cursor = $f->from->copy()->startOfDay();
        $end    = $f->to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date'      => $key,
                'label'     => $cursor->format('M j'),
                'new'       => (int) ($signups[$key] ?? 0),
                'active'    => (int) ($orderCustomers[$key] ?? 0),
            ];
            $cursor->addDay();
        }
        return $days;
    }

    /* ---------------- Churn snapshot (counts only) ---------------- */

    private function churnSnapshot(PerformanceFilters $f): array
    {
        $priorAny = (int) Parcel::query()
            ->where('created_at', '<', $f->from)
            ->distinct('merchant_id')->count('merchant_id');
        $activeNow = (int) Parcel::query()
            ->whereBetween('created_at', [$f->from, $f->to])
            ->distinct('merchant_id')->count('merchant_id');
        $churned = max(0, $priorAny - $activeNow);

        return [
            'prior_customers' => $priorAny,
            'active_now'      => $activeNow,
            'churned'         => $churned,
            'churn_rate'      => $priorAny > 0 ? round($churned / $priorAny, 4) : null,
        ];
    }
}
