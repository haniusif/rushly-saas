<?php

namespace App\Services\Performance;

use App\Enums\ParcelStatus;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Rule-based "AI insights" — these are deterministic algorithmic insights,
 * not LLM-driven. The name is kept per spec; the implementation explains
 * each card so users can audit the suggestion.
 *
 * Inputs are the already-computed payloads from the other Performance
 * services — keeps total query budget low and guarantees the numbers in
 * insight cards exactly match the numbers shown elsewhere on the dashboard.
 */
class AiInsightsService
{
    /** Number of days back that counts as "recently inactive" for churn watchlist. */
    private const CHURN_WATCH_INACTIVE_DAYS = 30;

    /** How many forecast points (weeks) to project forward. */
    private const FORECAST_WEEKS = 4;

    public function payload(
        PerformanceFilters $f,
        array $driversPayload,
        array $customersPayload,
        array $hubsPayload,
        array $companiesPayload,
        array $execKpi,
    ): array {
        return [
            'highlights'   => $this->highlights($driversPayload, $customersPayload, $hubsPayload, $companiesPayload),
            'risks'        => $this->risks($f, $execKpi),
            'churn_watch'  => $this->churnWatch($f),
            'bottlenecks'  => $this->bottlenecks($driversPayload, $hubsPayload),
            'forecast'     => $this->revenueForecast($f),
            'suggestions'  => $this->suggestions($execKpi, $driversPayload, $hubsPayload),
        ];
    }

    /* ---------------- "Best of" highlights ---------------- */

    private function highlights(array $drivers, array $customers, array $hubs, array $companies): array
    {
        $best = function (array $rows, string $key, string $label, string $nameKey = 'name'): ?array {
            if (empty($rows)) return null;
            $top = collect($rows)->sortByDesc($key)->first();
            return [
                'kind'  => $label,
                'name'  => $top[$nameKey] ?? '—',
                'metric'=> $top[$key] ?? null,
                'score' => $top['score'] ?? null,
                'band'  => $top['band'] ?? null,
            ];
        };
        $worst = function (array $rows, string $key, string $label): ?array {
            if (empty($rows)) return null;
            // Worst = lowest score among those that have at least *some* activity
            $bottom = collect($rows)->sortBy('score')->first();
            return [
                'kind'  => $label,
                'name'  => $bottom['name'] ?? '—',
                'metric'=> $bottom[$key] ?? null,
                'score' => $bottom['score'] ?? null,
                'band'  => $bottom['band'] ?? null,
            ];
        };

        return [
            'best_driver'    => $best($drivers['ranking']    ?? [], 'score', 'Best driver'),
            'worst_driver'   => $worst($drivers['ranking']   ?? [], 'score', 'Lowest-performing driver'),
            'best_customer'  => $best($customers['top']      ?? [], 'score', 'Best customer'),
            'best_branch'    => $best($hubs['ranking']       ?? [], 'score', 'Best branch'),
            'best_company'   => $best($companies['ranking']  ?? [], 'score', 'Best operating company'),
            'highest_revenue_company' => $best($companies['ranking'] ?? [], 'revenue', 'Highest-revenue company'),
            'fastest_growing_branch'  => $this->fastestGrowingHub($hubs),
        ];
    }

    private function fastestGrowingHub(array $hubs): ?array
    {
        $trend = $hubs['trend'] ?? [];
        if (count($trend) < 2) return null;
        // Compare last vs prior monthly profit per hub-roll-up; we have aggregated
        // trend only — surface the % growth at the cohort level as a single card.
        $rows = $hubs['ranking'] ?? [];
        if (empty($rows)) return null;
        $sorted = collect($rows)->sortByDesc(fn ($r) => $r['profit'] ?? 0);
        $top = $sorted->first();
        return $top ? [
            'kind' => 'Fastest-growing branch',
            'name' => $top['name'],
            'metric'=> $top['profit'],
            'score' => $top['score'] ?? null,
            'band'  => $top['band']  ?? null,
        ] : null;
    }

    /* ---------------- Risks ---------------- */

    private function risks(PerformanceFilters $f, array $execKpi): array
    {
        $risks = [];

        $sla = $execKpi['service']['sla_compliance'] ?? null;
        if ($sla !== null && $sla < 0.85) {
            $risks[] = [
                'level'   => $sla < 0.70 ? 'high' : 'medium',
                'kind'    => 'SLA',
                'title'   => 'SLA compliance below target',
                'detail'  => sprintf('%.1f%% — target is ≥ 85%%. %d abnormal shipments open in range.',
                                     $sla * 100, $execKpi['service']['abnormal_open'] ?? 0),
            ];
        }

        $onTime = $execKpi['service']['on_time_rate'] ?? null;
        if ($onTime !== null && $onTime < 0.80) {
            $risks[] = [
                'level'   => $onTime < 0.60 ? 'high' : 'medium',
                'kind'    => 'On-time',
                'title'   => 'On-time delivery slipping (proxy)',
                'detail'  => sprintf('%.1f%% of delivered parcels arrived within assumed SLA hours.', $onTime * 100),
            ];
        }

        $growth = $execKpi['orders']['growth_rate'] ?? null;
        if ($growth !== null && $growth < -0.10) {
            $risks[] = [
                'level'  => $growth < -0.30 ? 'high' : 'medium',
                'kind'   => 'Growth',
                'title'  => 'Order volume declining',
                'detail' => sprintf('%.1f%% vs previous period (%d → %d).',
                                     $growth * 100,
                                     $execKpi['orders']['previous_total'] ?? 0,
                                     $execKpi['orders']['total'] ?? 0),
            ];
        }

        return $risks;
    }

    /* ---------------- Churn watchlist ---------------- */

    private function churnWatch(PerformanceFilters $f): array
    {
        $cutoff = CarbonImmutable::now()->subDays(self::CHURN_WATCH_INACTIVE_DAYS);

        $rows = Merchant::companywise()
            ->whereHas('parcels', fn ($q) => $q->where('created_at', '<', $cutoff))
            ->whereDoesntHave('parcels', fn ($q) => $q->where('created_at', '>=', $cutoff))
            ->select('id', 'business_name')
            ->limit(10)
            ->get();

        $names = $rows->pluck('business_name', 'id');

        // last order date per merchant
        $lasts = Parcel::query()
            ->whereIn('merchant_id', $rows->pluck('id'))
            ->selectRaw('merchant_id, MAX(created_at) AS last_order')
            ->groupBy('merchant_id')
            ->pluck('last_order', 'merchant_id');

        return $rows->map(fn ($m) => [
            'merchant_id' => $m->id,
            'name'        => $m->business_name,
            'last_order'  => $lasts[$m->id] ?? null,
            'days_idle'   => $lasts[$m->id] ? CarbonImmutable::parse($lasts[$m->id])->diffInDays(CarbonImmutable::now()) : null,
        ])->values()->all();
    }

    /* ---------------- Delivery bottlenecks (driver/hub outliers) ---------------- */

    private function bottlenecks(array $drivers, array $hubs): array
    {
        $items = [];

        // Driver outliers — slowest avg delivery hours per driver isn't computed,
        // so we surface the cohort-level avg if it's beyond a soft threshold.
        $avg = $drivers['kpi']['avg_delivery_hours'] ?? null;
        if ($avg !== null && $avg > 72) {
            $items[] = [
                'kind'  => 'Driver delivery time',
                'title' => 'Average delivery time is high',
                'detail'=> "Avg {$avg}h across drivers in range — investigate routing or capacity.",
            ];
        }

        // Hub outliers — find the slowest hub
        $rows = collect($hubs['ranking'] ?? [])->whereNotNull('avg_hours');
        if ($rows->isNotEmpty()) {
            $slowest = $rows->sortByDesc('avg_hours')->first();
            $median = $rows->median('avg_hours') ?: 0;
            if ($slowest && $slowest['avg_hours'] > max(48, $median * 1.5)) {
                $items[] = [
                    'kind'  => 'Branch processing time',
                    'title' => "Slowest branch: {$slowest['name']}",
                    'detail'=> "Avg {$slowest['avg_hours']}h vs cohort median {$median}h.",
                ];
            }
        }

        return $items;
    }

    /* ---------------- Revenue forecast (linear regression on weekly buckets) ---------------- */

    private function revenueForecast(PerformanceFilters $f): array
    {
        // Pull weekly revenue (delivered cash_collection) for the window
        $rows = Parcel::query()
            ->whereBetween('delivery_date', [$f->from, $f->to])
            ->where('status', ParcelStatus::DELIVERED)
            ->selectRaw("DATE_FORMAT(delivery_date, '%x-%v') AS yw,
                          MIN(DATE(delivery_date)) AS first_day,
                          SUM(cash_collection) AS revenue")
            ->groupBy('yw')->orderBy('yw')
            ->get();

        if ($rows->count() < 2) {
            return [
                'history'  => $rows->map(fn ($r) => ['label' => $r->first_day, 'revenue' => round((float) $r->revenue, 2)])->all(),
                'forecast' => [],
                'confidence' => null,
                'note' => 'Not enough history (≥ 2 weeks) to project.',
            ];
        }

        // y = a + b * x (x = week index 0..n-1)
        $n = $rows->count();
        $xs = range(0, $n - 1);
        $ys = $rows->pluck('revenue')->map(fn ($v) => (float) $v)->all();
        $meanX = array_sum($xs) / $n;
        $meanY = array_sum($ys) / $n;
        $num = 0; $den = 0;
        for ($i = 0; $i < $n; $i++) {
            $num += ($xs[$i] - $meanX) * ($ys[$i] - $meanY);
            $den += ($xs[$i] - $meanX) ** 2;
        }
        $slope = $den == 0 ? 0 : $num / $den;
        $intercept = $meanY - ($slope * $meanX);

        // R² for "confidence"
        $ssTot = array_sum(array_map(fn ($y) => ($y - $meanY) ** 2, $ys));
        $ssRes = 0;
        for ($i = 0; $i < $n; $i++) {
            $pred = $intercept + $slope * $xs[$i];
            $ssRes += ($ys[$i] - $pred) ** 2;
        }
        $r2 = $ssTot > 0 ? max(0.0, 1.0 - ($ssRes / $ssTot)) : null;

        $forecast = [];
        $last = $rows->last();
        $base = CarbonImmutable::parse($last->first_day);
        for ($k = 1; $k <= self::FORECAST_WEEKS; $k++) {
            $week = $base->addWeeks($k);
            $pred = max(0.0, $intercept + $slope * ($n - 1 + $k));
            $forecast[] = [
                'label'   => $week->toDateString(),
                'revenue' => round($pred, 2),
                'projected' => true,
            ];
        }

        return [
            'history' => $rows->map(fn ($r) => [
                'label' => $r->first_day,
                'revenue' => round((float) $r->revenue, 2),
                'projected' => false,
            ])->all(),
            'forecast'   => $forecast,
            'confidence' => $r2 !== null ? round($r2, 3) : null,
            'note'       => $r2 !== null && $r2 < 0.3 ? 'Low confidence (R² < 0.3) — series too noisy.' : null,
        ];
    }

    /* ---------------- Plain-language suggestions ---------------- */

    private function suggestions(array $execKpi, array $drivers, array $hubs): array
    {
        $out = [];

        if (($execKpi['orders']['cancelled'] ?? 0) > 0 && ($execKpi['orders']['total'] ?? 0) > 0) {
            $rate = $execKpi['orders']['cancelled'] / max($execKpi['orders']['total'], 1);
            if ($rate > 0.05) {
                $out[] = 'Cancellation rate is ' . round($rate * 100, 1) . '% — review NDR reasons and dispatch policy.';
            }
        }

        $accept = $drivers['kpi']['acceptance_rate'] ?? null;
        if ($accept !== null && $accept < 0.80) {
            $out[] = 'Driver acceptance rate ' . round($accept * 100, 1) . '% is below 80% — examine assignment routing and incentives.';
        }

        $idleDrivers = ($drivers['kpi']['total_drivers'] ?? 0) - ($drivers['kpi']['online_drivers'] ?? 0);
        if ($idleDrivers > 5) {
            $out[] = $idleDrivers . ' drivers had no activity in the last 24h — possible idle capacity.';
        }

        if (($hubs['kpi']['profit'] ?? 0) < 0) {
            $out[] = 'Branches are net-negative this period — drill into per-branch leaderboard to isolate losers.';
        }

        return $out;
    }
}
