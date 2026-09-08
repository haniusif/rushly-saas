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
            // Shipments stays first so it is the sheet Excel opens on - it is
            // the only one a merchant fills in. The two lookup sheets behind
            // it are what make the City and Area dropdowns work: Cities
            // publishes the CityNames named range, Areas publishes one
            // Areas_<city_id> range per city, and the validations on the
            // Shipments sheet point at both by name.
            //
            // They were commented out while the validations that depend on
            // them were left in place, so every City/Area cell carried a rule
            // pointing at a range the workbook did not contain.
            new ShipmentTemplateExport($this->shipmentRows),
            new CitiesSheetExport($this->citiesRows),
            new AreasSheetExport($this->areasRows),
        ];
    }
}
