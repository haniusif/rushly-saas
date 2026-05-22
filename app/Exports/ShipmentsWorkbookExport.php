<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ShipmentsWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private array $shipmentRows = [],
        private array $citiesRows = [],
        private array $areasRows = [],
    ) {}

    public function sheets(): array
    {
        return [
            new ShipmentTemplateExport($this->shipmentRows),
           // new CitiesSheetExport($this->citiesRows),  // provides CityNames range
           // new AreasSheetExport($this->areasRows),    // provides Areas_<city_id> ranges
           
        ];
    }
}
