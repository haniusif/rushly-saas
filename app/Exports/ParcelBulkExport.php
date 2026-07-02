<?php

namespace App\Exports;

use App\Enums\ParcelStatus;
use App\Support\ParcelStatusHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export the parcels selected on /admin/bulk_action to a single .xlsx.
 * Caller passes an Eloquent collection (already filtered + companywise-safe);
 * this class only knows how to project rows.
 */
class ParcelBulkExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $parcels) {}

    public function headings(): array
    {
        return [
            'Tracking ID', 'Invoice #', 'Merchant',
            'Customer Name', 'Customer Phone', 'Customer Address',
            'City', 'Area', 'Status', 'Delivery Type',
            'COD', 'Delivery Charge', 'Total Payable',
            'Boxes', 'Weight (kg)', 'Description',
            'Note', 'Created At',
        ];
    }

    public function collection(): Collection
    {
        return $this->parcels;
    }

    public function map($p): array
    {
        return [
            $p->tracking_id ?? ('#' . $p->id),
            $p->invoice_no,
            optional($p->merchant)->business_name,
            $p->customer_name,
            $p->customer_phone,
            $p->customer_address,
            optional($p->city)->name ?? optional($p->city)->en_name,
            optional($p->area)->name ?? optional($p->area)->en_name,
            ParcelStatusHelper::label((int) $p->status),
            $p->delivery_type,
            (float) $p->cash_collection,
            (float) $p->delivery_charge,
            (float) $p->current_payable,
            (int) ($p->number_of_boxes ?? 1),
            (float) ($p->weight ?? 0),
            $p->package_description,
            $p->note,
            optional($p->created_at)->toDateTimeString(),
        ];
    }
}
