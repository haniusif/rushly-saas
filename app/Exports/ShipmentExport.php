<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Http\Request;
use App\Repositories\Parcel\ParcelInterface;

class ShipmentExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;
    protected $repo;
    private $index = 1;  

    public function __construct(Request $request, ParcelInterface $repo)
    {
        $this->request = $request;
        $this->repo = $repo;
    }

    /**
     * Get the collection of parcels with their relationships
     */
    public function collection()
    {
        return $this->repo->filter($this->request, 10000)
            ->load([
                'merchant',
                'city',
                'area',
                'lastDeliveryMan.deliveryMan.user', // Load last delivery man
                'lastPickupMan.pickupman.user',     // (optional) Load last pickup man too
            ]);
    }

    /**
     * Excel headings
     */
    public function headings(): array
    {
        return [
            'Serial',          
            'Tracking ID',
            'Merchant',
            'Customer Name',
            'Customer Phone',
            'Customer Address',
            'Customer City',
            'Customer Area',
            'Pickup Date',
            'Delivery Date',
            'Status',
            'COD Amount',
            // 'Last Pickup Man',
            'Last Delivery Man',
        ];
    }

    /**
     * Map each parcel row for Excel
     */
    public function map($parcel): array
    {
        return [
            $this->index++,  
            $parcel->tracking_id,
            optional($parcel->merchant)->business_name,
            $parcel->customer_name,
            $parcel->customer_phone,
            $parcel->customer_address,
            optional($parcel->city)->en_name,
            optional($parcel->area)->en_name,
            $parcel->pickup_date,
            $parcel->delivery_date,
            getParcelStatusLabel($parcel->status),
            $parcel->cash_collection,
            // ✅ Last Pickup Man
            // optional(optional($parcel->lastPickupMan)->pickupman->user)->name,
            // ✅ Last Delivery Man
            $parcel->lastDeliveryMan->deliveryMan->user->name ?? '-'
            // optional(optional($parcel->lastDeliveryMan)->deliveryMan->user)->name,
        ];
    }
}
