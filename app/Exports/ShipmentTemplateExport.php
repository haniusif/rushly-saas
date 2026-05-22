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
        return [
            "Pickup point",
            "Pickup phone",
            "Pickup address",
            "COD *",
            "Reference number",
            "Weight *",
            "Customer Name *",
            "Customer Phone *",
            "City *",
            "Area",
            "Customer Address *",
            "Note",
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
                $sheet->getStyle('D:D')->getNumberFormat()->setFormatCode('#,##0.00'); // COD
                $sheet->getStyle('F:F')->getNumberFormat()->setFormatCode('0.00');     // Weight

                // Phones as text (to preserve + and leading zeros)
                $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('@'); // Pickup phone
                $sheet->getStyle('H:H')->getNumberFormat()->setFormatCode('@'); // Customer phone

                // Alignments
                $event->sheet->getStyle('A:L')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Thin borders for visible grid
                $lastRow = max(2, count($this->rows) + 1);
                $event->sheet->getStyle("A1:L{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

                // --- Data Validations ---

                // City (Column I) -> Named Range =CityNames
                // NOTE: If your CityNames points to English names, you're set.
                // If it points to Arabic names, change your source or your UI text accordingly.
                $maxRows = max($lastRow + 500, 2000); // allow plenty of blank rows
                for ($r = 2; $r <= $maxRows; $r++) {
                    $cell = "I{$r}";
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

                // Area (Column J) -> Dependent on City (Column I)
                // Uses Cities!A (id) and Cities!B (name). If you used English names in Cities!C, swap B->C below.
                // Formula per row:
                // =INDIRECT("Areas_" & INDEX(Cities!$A$2:$A$1000, MATCH($I2, Cities!$B$2:$B$1000,0)))
                for ($r = 2; $r <= $maxRows; $r++) {
                    $cell = "J{$r}";
                    $dv = $sheet->getCell($cell)->getDataValidation();
                    $dv->setType(DataValidation::TYPE_LIST);
                    $dv->setAllowBlank(true);
                    $dv->setShowDropDown(true);
                    $dv->setErrorStyle(DataValidation::STYLE_STOP);
                    $dv->setFormula1('=INDIRECT("Areas_"&INDEX(Cities!$A$2:$A$1000, MATCH($I'.$r.', Cities!$B$2:$B$1000, 0)))');
                    $dv->setPromptTitle('Choose an area');
                    $dv->setPrompt('Pick an area that belongs to the selected city.');
                    $dv->setErrorTitle('Invalid value');
                    $dv->setError('Please choose an area from the dropdown list.');
                }
            },
        ];
    }
}
