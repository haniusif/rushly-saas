<?php

namespace App\Exports;

use App\Enums\ParcelStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DriverRunsheetExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    public function __construct(private Collection $shipments, private ?string $sheetTitle = null) {}

    public function title(): string
    {
        // Excel sheet names are limited to 31 chars and can't contain : \ / ? * [ ]
        $name = $this->sheetTitle ?: 'Runsheet';
        $name = preg_replace('/[:\\\\\/\?\*\[\]]/', '', $name);
        return mb_substr($name, 0, 31);
    }

    public function headings(): array
    {
        return ['#', 'Tracking ID', 'Merchant', 'Customer', 'Address', 'COD', 'Shipment Status'];
    }

    public function array(): array
    {
        if ($this->shipments instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->shipments->loadMissing('parcel.merchant');
        }

        $rows           = [];
        $index          = 1;
        $totalCash      = 0.0;
        $totalDelivered = 0;
        $codDelivered   = 0.0;

        foreach ($this->shipments as $item) {
            $parcel = $item->parcel ?? null;
            if (! $parcel) {
                continue;
            }

            $cod = (float) ($parcel->cash_collection ?? 0);
            $totalCash += $cod;

            if (in_array($parcel->status, [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])) {
                $totalDelivered++;
                $codDelivered += $cod;
            }

            $rows[] = [
                $index++,
                $parcel->tracking_id ?? ('#' . $parcel->id),
                $parcel->merchant->business_name ?? $parcel->merchant_id ?? '-',
                $parcel->customer_name ?? '-',
                $parcel->customer_address ?? '-',
                round($cod, 2),
                trans('parcelStatus.' . $parcel->status),
            ];
        }

        // Summary rows
        $rows[] = [];
        $rows[] = ['', '', '', '', 'Total Cash Collection', round($totalCash, 2), ''];
        $rows[] = ['', '', '', '', 'Total Delivered', $totalDelivered, ''];
        $rows[] = ['', '', '', '', 'COD Collection (Delivered)', round($codDelivered, 2), ''];

        return $rows;
    }
}
