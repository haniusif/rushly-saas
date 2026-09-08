<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ShipmentTemplateExport implements FromArray, WithHeadings, WithEvents, WithTitle
{
    /**
     * Pass rows like:
     * [
     *   ["HQ","+966...","Riyadh - HQ",150,"REF-1001",2.5,"Hani","+9665...","Dubai","Hind City 1","Address","Note"],
     *   ...
     * ]
     */
    public function __construct(private array $rows = [])
    {
    }

    public function title(): string
    {
        return 'Shipments';
    }

    public function headings(): array
    {
        // Column ORDER is the merchant's, from the reordered sheet they sent
        // back: what identifies the shipment first, then where it is collected,
        // then who receives it, then what it weighs and what to collect.
        // Required columns (the trailing star) are unchanged.
        //
        // Anything keyed off a column letter below - number formats, the two
        // phone columns, the City/Area validations - has to move with it.
        return [
            "Reference number",   // A
            "Pickup point",       // B
            "Pickup phone",       // C
            "Pickup address",     // D
            "Customer Name *",    // E
            "Customer Phone *",   // F
            "City *",             // G
            "Area",               // H
            "Customer Address *", // I
            "Weight *",           // J
            "COD *",              // K
            "Note",               // L
        ];
    }

    public function array(): array
    {
        return $this->rows ?? [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Freeze header
                $event->sheet->freezePane('A2');

                // Autosize A..L (12 columns)
                foreach (range('A', 'L') as $col) {
                    $event->sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Header styling
                $event->sheet->getStyle('A1:L1')->getFont()->setBold(true);
                $event->sheet->getStyle('A1:L1')->getAlignment()->setWrapText(true);

                // Number formats
                $sheet->getStyle('K:K')->getNumberFormat()->setFormatCode('#,##0.00'); // COD
                $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode('0.00');     // Weight

                // Phones as text (to preserve + and leading zeros)
                $sheet->getStyle('C:C')->getNumberFormat()->setFormatCode('@'); // Pickup phone
                $sheet->getStyle('F:F')->getNumberFormat()->setFormatCode('@'); // Customer phone

                // Alignments
                $event->sheet->getStyle('A:L')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Thin borders for visible grid
                $lastRow = max(2, count($this->rows) + 1);
                $event->sheet->getStyle("A1:L{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

                // --- Data Validations ---

                // City (Column G) -> Named Range =CityNames
                // NOTE: If your CityNames points to English names, you're set.
                // If it points to Arabic names, change your source or your UI text accordingly.
                $maxRows = max($lastRow + 500, 2000); // allow plenty of blank rows
                for ($r = 2; $r <= $maxRows; $r++) {
                    $cell = "G{$r}";
                    $dv = $sheet->getCell($cell)->getDataValidation();
                    $dv->setType(DataValidation::TYPE_LIST);
                    $dv->setAllowBlank(true);
                    $dv->setShowDropDown(true);
                    $dv->setErrorStyle(DataValidation::STYLE_STOP);
                    $dv->setFormula1('=CityNames');
                    $dv->setPromptTitle('Choose a city');
                    $dv->setPrompt('Pick a city from the Cities sheet.');
                    $dv->setErrorTitle('Invalid value');
                    $dv->setError('Please choose a city from the dropdown list.');
                }

                // Area (Column H) -> Dependent on City (Column G)
                // Uses Cities!A (id) and Cities!B (name). If you used English names in Cities!C, swap B->C below.
                // Formula per row:
                // =INDIRECT("Areas_" & INDEX(Cities!$A$2:$A$1000, MATCH($G2, Cities!$B$2:$B$1000,0)))
                for ($r = 2; $r <= $maxRows; $r++) {
                    $cell = "H{$r}";
                    $dv = $sheet->getCell($cell)->getDataValidation();
                    $dv->setType(DataValidation::TYPE_LIST);
                    $dv->setAllowBlank(true);
                    $dv->setShowDropDown(true);
                    $dv->setErrorStyle(DataValidation::STYLE_STOP);
                    // IFERROR -> NoAreas: a city with no areas has no
                    // Areas_<id> range, and the bare INDIRECT would resolve to
                    // #REF! and block the cell entirely.
                    $dv->setFormula1('=IFERROR(INDIRECT("Areas_"&INDEX(Cities!$A$2:$A$1000, MATCH($G'.$r.', Cities!$B$2:$B$1000, 0))), NoAreas)');
                    $dv->setPromptTitle('Choose an area');
                    $dv->setPrompt('Pick an area that belongs to the selected city.');
                    $dv->setErrorTitle('Invalid value');
                    $dv->setError('Please choose an area from the dropdown list.');
                }
            },
        ];
    }
}
