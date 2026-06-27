<?php

namespace App\Exports\Performance;

use App\Services\Performance\PerformanceFilters;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomersSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private PerformanceFilters $filters,
        private array $customers,
    ) {}

    public function title(): string
    {
        return 'Customers';
    }

    public function headings(): array
    {
        return ['#', 'Customer', 'Orders', 'Delivered', 'Completion %', 'Revenue', 'AOV', 'Score', 'Band'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (($this->customers['top'] ?? []) as $i => $r) {
            $rows[] = [
                $i + 1,
                $r['name'],
                $r['orders'],
                $r['delivered'],
                $r['completion_rate'] !== null ? number_format($r['completion_rate'] * 100, 1) : '—',
                $r['revenue'],
                $r['aov'],
                $r['score'],
                $r['band'],
            ];
        }
        return $rows;
    }
}
