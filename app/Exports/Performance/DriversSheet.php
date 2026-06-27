<?php

namespace App\Exports\Performance;

use App\Services\Performance\PerformanceFilters;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DriversSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private PerformanceFilters $filters,
        private array $drivers,
    ) {}

    public function title(): string
    {
        return 'Drivers';
    }

    public function headings(): array
    {
        return ['#', 'Driver', 'Delivered', 'Handled', 'Completion %', 'On-time %', 'Revenue', 'Score', 'Band'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (($this->drivers['ranking'] ?? []) as $i => $r) {
            $rows[] = [
                $i + 1,
                $r['name'],
                $r['delivered'],
                $r['handled'],
                number_format(($r['completion_rate'] ?? 0) * 100, 1),
                $r['on_time_rate'] !== null ? number_format($r['on_time_rate'] * 100, 1) : '—',
                $r['revenue'],
                $r['score'],
                $r['band'],
            ];
        }
        return $rows;
    }
}
