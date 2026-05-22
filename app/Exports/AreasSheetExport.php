<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\NamedRange;

class AreasSheetExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function __construct(private array $rows = []) {}

    public function title(): string { return 'Areas'; }

    public function headings(): array
    {
        // Keep only what we need for dependent validation
        // A=id, B=area_code, C=name (Arabic), D=en_name, E=city_id
        return ['id','area_code','name','en_name','city_id'];
    }

    public function array(): array { return $this->rows ?? []; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $book  = $sheet->getParent();

                $event->sheet->freezePane('A2');
                foreach (range('A','E') as $c) {
                    $event->sheet->getColumnDimension($c)->setAutoSize(true);
                }
                $event->sheet->getStyle('A1:E1')->getFont()->setBold(true);

                // Build named ranges per city_id over C (name)
                // Data starts at row 2
                $count = count($this->rows);
                if ($count === 0) {
                    return;
                }

                // rows are expected pre-sorted by city_id (we'll sort in controller)
                $currentCityId = null;
                $start = null;

                // Helper to create (or replace) a named range
                $makeRange = function(string $name, string $ref) use ($book, $sheet) {
                    // Remove old if exists
                    foreach ($book->getNamedRanges() as $nr) {
                        if ($nr->getName() === $name) {
                            $book->removeNamedRange($name);
                            break;
                        }
                    }
                    $book->addNamedRange(new NamedRange($name, $sheet, $ref));
                };

                // Iterate data rows
                for ($i = 0; $i < $count; $i++) {
                    $excelRow = $i + 2; // header at 1
                    $cityId = (string)($this->rows[$i][4] ?? ''); // column E = city_id

                    if ($currentCityId === null) {
                        // first group
                        $currentCityId = $cityId;
                        $start = $excelRow;
                    } elseif ($cityId !== $currentCityId) {
                        // close previous group
                        if ($start !== null) {
                            $end = $excelRow - 1;
                            if ($end >= $start) {
                                $ref = "C{$start}:C{$end}";
                                $makeRange("Areas_{$currentCityId}", $ref);
                            }
                        }
                        // start new group
                        $currentCityId = $cityId;
                        $start = $excelRow;
                    }
                }
                // close last group
                if ($start !== null && $currentCityId !== null) {
                    $end = $count + 1;
                    if ($end >= $start) {
                        $ref = "C{$start}:C{$end}";
                        $makeRange("Areas_{$currentCityId}", $ref);
                    }
                }
            },
        ];
    }
}
