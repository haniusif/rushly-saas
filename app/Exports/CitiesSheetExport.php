<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\NamedRange;

class CitiesSheetExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function __construct(private array $rows = []) {}

    public function title(): string { return 'Cities'; }

    public function headings(): array
    {
        // Only these four columns
        return ['id', 'name', 'en_name', 'city_code'];
    }

    public function array(): array { return $this->rows ?? []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $book  = $sheet->getParent();

                $event->sheet->freezePane('A2');
                foreach (range('A', 'D') as $c) {
                    $event->sheet->getColumnDimension($c)->setAutoSize(true);
                }
                $event->sheet->getStyle('A1:D1')->getFont()->setBold(true);

                // Build a Named Range over column B (Arabic "name")
                $count   = max(0, count($this->rows));
                $lastRow = $count > 0 ? 1 + $count : 1000; // allow future additions
                $citiesSheet = $book->getSheetByName('Cities');
                $range = "B2:B{$lastRow}";

                // Remove old one (if any), then add fresh Named Range
                foreach ($book->getNamedRanges() as $nr) {
                    if ($nr->getName() === 'CityNames') {
                        $book->removeNamedRange('CityNames');
                        break;
                    }
                }
                $book->addNamedRange(new NamedRange('CityNames', $citiesSheet, $range));
            },
        ];
    }
}
