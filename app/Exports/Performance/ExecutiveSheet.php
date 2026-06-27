<?php

namespace App\Exports\Performance;

use App\Services\Performance\PerformanceFilters;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExecutiveSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private PerformanceFilters $filters,
        private array $kpi,
    ) {}

    public function title(): string
    {
        return 'Executive';
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function array(): array
    {
        $o = $this->kpi['orders']    ?? [];
        $f = $this->kpi['financial'] ?? [];
        $a = $this->kpi['activity']  ?? [];
        $s = $this->kpi['service']   ?? [];
        $pct = fn ($v) => $v === null ? '—' : number_format($v * 100, 2) . '%';

        return [
            ['Range', $this->filters->from->toDateString() . ' → ' . $this->filters->to->toDateString()],
            [],
            ['ORDERS', ''],
            ['Total',          $o['total']           ?? 0],
            ['Completed',      $o['completed']       ?? 0],
            ['Pending',        $o['pending']         ?? 0],
            ['Cancelled',      $o['cancelled']       ?? 0],
            ['Completion rate',$pct($o['completion_rate'] ?? null)],
            ['Growth rate',    $pct($o['growth_rate']     ?? null)],
            [],
            ['FINANCIAL ('.($f['currency'] ?? '').')', ''],
            ['Revenue',  $f['revenue']  ?? 0],
            ['Expenses', $f['expenses'] ?? 0],
            ['Profit',   $f['profit']   ?? 0],
            [],
            ['ACTIVITY', ''],
            ['Active drivers',   ($a['active_drivers']   ?? 0) . ' / ' . ($a['total_drivers']   ?? 0)],
            ['Active customers', ($a['active_customers'] ?? 0) . ' / ' . ($a['total_customers'] ?? 0)],
            ['Active branches',  ($a['active_branches']  ?? 0) . ' / ' . ($a['total_branches']  ?? 0)],
            ['Active companies (3PL)', $a['active_companies'] ?? 0],
            [],
            ['SERVICE QUALITY', ''],
            ['Avg. delivery hours', $s['avg_delivery_hours'] ?? '—'],
            ['On-time rate (proxy)',$pct($s['on_time_rate']   ?? null)],
            ['SLA compliance',     $pct($s['sla_compliance'] ?? null)],
            ['Satisfaction (proxy)',$pct($s['satisfaction']   ?? null)],
            ['Open abnormal',       $s['abnormal_open']      ?? 0],
            ['Support tickets',     $s['support_tickets']    ?? 0],
        ];
    }
}
