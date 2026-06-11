<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BulkDriverRunsheetExport implements WithMultipleSheets
{
    /**
     * @param array<int, array{name: string, shipments: Collection}> $drivers
     */
    public function __construct(private array $drivers) {}

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->drivers as $driver) {
            $sheets[] = new DriverRunsheetExport(
                $driver['shipments'] ?? collect(),
                $driver['name'] ?? null
            );
        }
        return $sheets;
    }
}
