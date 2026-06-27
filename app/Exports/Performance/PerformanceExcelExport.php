<?php

namespace App\Exports\Performance;

use App\Services\Performance\PerformanceFilters;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PerformanceExcelExport implements WithMultipleSheets
{
    public function __construct(
        private PerformanceFilters $filters,
        private array $kpi,
        private array $drivers,
        private array $customers = [],
        private array $hubs = [],
        private array $companies = [],
    ) {}

    public function sheets(): array
    {
        $sheets = [
            new ExecutiveSheet($this->filters, $this->kpi),
            new DriversSheet($this->filters, $this->drivers),
        ];
        if (! empty($this->customers)) {
            $sheets[] = new CustomersSheet($this->filters, $this->customers);
        }
        if (! empty($this->hubs)) {
            $sheets[] = new HubsSheet($this->filters, $this->hubs);
        }
        if (! empty($this->companies)) {
            $sheets[] = new CompaniesSheet($this->filters, $this->companies);
        }
        return $sheets;
    }
}
