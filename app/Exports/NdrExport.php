<?php

namespace App\Exports;

use App\Models\Backend\Ndr;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NdrExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private int $index = 1;

    public function __construct(private Request $request) {}

    public function collection()
    {
        $q = Ndr::companywise()->with(['parcel', 'deliveryman', 'createdBy', 'resolvedBy']);

        if ($this->request->filled('status'))         $q->where('status', $this->request->input('status'));
        if ($this->request->filled('failure_reason')) $q->where('failure_reason', $this->request->input('failure_reason'));
        if ($this->request->filled('deliveryman_id')) $q->where('deliveryman_id', $this->request->input('deliveryman_id'));
        if ($this->request->filled('date_from'))      $q->whereDate('created_at', '>=', $this->request->input('date_from'));
        if ($this->request->filled('date_to'))        $q->whereDate('created_at', '<=', $this->request->input('date_to'));

        return $q->latest('id')->get();
    }

    public function headings(): array
    {
        return [
            'Serial',
            'NDR ID',
            'Tracking',
            'Attempt',
            'Failure Reason',
            'Driver Notes',
            'Action Taken',
            'Next Attempt Date',
            'Status',
            'Deliveryman',
            'Created By',
            'Resolved By',
            'Resolved At',
            'Created At',
        ];
    }

    public function map($ndr): array
    {
        return [
            $this->index++,
            $ndr->id,
            optional($ndr->parcel)->tracking_id ?? ('#'.$ndr->parcel_id),
            $ndr->attempt_number.'/3',
            ucwords(str_replace('_', ' ', (string) $ndr->failure_reason)),
            $ndr->driver_notes,
            $ndr->action_taken ? ucwords(str_replace('_', ' ', (string) $ndr->action_taken)) : '',
            optional($ndr->next_attempt_date)->format('Y-m-d'),
            ucwords(str_replace('_', ' ', (string) $ndr->status)),
            optional($ndr->deliveryman)->name,
            optional($ndr->createdBy)->name,
            optional($ndr->resolvedBy)->name,
            optional($ndr->resolved_at)->format('Y-m-d H:i'),
            optional($ndr->created_at)->format('Y-m-d H:i'),
        ];
    }
}
