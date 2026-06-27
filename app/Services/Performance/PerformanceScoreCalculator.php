<?php

namespace App\Services\Performance;

/**
 * Weighted Performance Score (0–100) shared by Driver / Customer / Hub / Company perf views.
 *
 * Formula per spec:
 *   20% Productivity      (volume normalized against the cohort top)
 *   20% Completion Rate   (delivered / total)
 *   15% Customer Rating   (proxy: 1 - support tickets per order, clamped)
 *   15% On-Time           (delivered within delivery-type SLA proxy)
 *   15% Revenue           (revenue normalized against the cohort top)
 *   10% SLA Compliance    (1 - abnormal_open / total, clamped)
 *    5% Growth            (current-period orders vs previous-period; clipped to [0,1])
 *
 * Each component is a fraction in [0..1]. If a component is null (no data), it's
 * skipped and the remaining weights are renormalized — so a row with no rating
 * data isn't penalized harder than one with all components present.
 */
class PerformanceScoreCalculator
{
    public const WEIGHTS = [
        'productivity' => 0.20,
        'completion'   => 0.20,
        'rating'       => 0.15,
        'on_time'      => 0.15,
        'revenue'      => 0.15,
        'sla'          => 0.10,
        'growth'       => 0.05,
    ];

    /**
     * @param  array<string,float|null>  $components  fractions in [0..1] (null = missing)
     * @return array{score:int,band:string,components:array<string,float|null>}
     */
    public static function compute(array $components): array
    {
        $weightTotal = 0.0;
        $weightedSum = 0.0;

        foreach (self::WEIGHTS as $key => $weight) {
            $value = $components[$key] ?? null;
            if ($value === null) continue;
            $clamped = max(0.0, min(1.0, (float) $value));
            $weightedSum += $clamped * $weight;
            $weightTotal += $weight;
        }

        $score = $weightTotal > 0 ? (int) round(($weightedSum / $weightTotal) * 100) : 0;

        return [
            'score'      => $score,
            'band'       => self::band($score),
            'components' => $components,
        ];
    }

    public static function band(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 80 => 'very_good',
            $score >= 70 => 'good',
            $score >= 60 => 'needs_improvement',
            default      => 'critical',
        };
    }
}
