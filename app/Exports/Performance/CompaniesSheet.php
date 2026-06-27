<?php

namespace App\Exports\Performance;

use App\Services\Performance\PerformanceFilters;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CompaniesSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private PerformanceFilters $filters,
        private array $companies,
    ) {}

    public function title(): string
    {
        return 'Operating Companies';
    }

    public function headings(): array
    {
        return ['#', 'Company', 'Fleet', 'Handled', 'Delivered', 'Success %', 'Revenue', 'Expense', 'Profit', 'Avg. h', 'Score', 'Band'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (($this->companies['ranking'] ?? []) as $i => $r) {
            $rows[] = [
                $i + 1,
                $r['name'],
                $r['fleet_size'],
                $r['handled'],
                $r['delivered'],
                $r['success_rate'] !== null ? number_format($r['success_rate'] * 100, 1) : '—',
                $r['revenue'],
                $r['expense'],
                $r['profit'],
                $r['avg_hours'] ?? '—',
                $r['score'],
                $r['band'],
            ];
        }
        return $rows;
    }
}
