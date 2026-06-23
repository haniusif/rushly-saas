<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantPanel\PickupRequest\PickupRequestInterface;
use Inertia\Inertia;

class PickupRequestController extends Controller
{
    protected $repo;
    public function __construct(PickupRequestInterface $repo)
    {
        $this->repo = $repo;
    }

    public function regular()
    {
        $paginator = $this->repo->getRegular();

        $rows = collect($paginator->items())->map(fn ($r) => [
            'id'              => $r->id,
            'merchant_name'   => optional(optional($r->merchant)->user)->name,
            'merchant_email'  => optional(optional($r->merchant)->user)->email,
            'merchant_phone'  => optional(optional($r->merchant)->user)->mobile,
            'merchant_image'  => optional(optional($r->merchant)->user)->image,
            'address'         => (string) $r->address,
            'parcel_quantity' => $r->parcel_quantity,
            'note'            => (string) $r->note,
        ])->values();

        return Inertia::render('Admin/PickupRequest/Regular', $this->payload($paginator, $rows, 'regular'));
    }

    public function express()
    {
        $paginator = $this->repo->getExpress();

        $rows = collect($paginator->items())->map(fn ($r) => [
            'id'              => $r->id,
            'merchant_name'   => optional(optional($r->merchant)->user)->name,
            'merchant_email'  => optional(optional($r->merchant)->user)->email,
            'merchant_phone'  => optional(optional($r->merchant)->user)->mobile,
            'merchant_image'  => optional(optional($r->merchant)->user)->image,
            'name'            => (string) $r->name,
            'phone'           => (string) $r->phone,
            'address'         => (string) $r->address,
            'cod_amount'      => $r->cod_amount,
            'invoice'         => $r->invoice,
            'weight'          => $r->weight,
            'exchange'        => (int) $r->exchange === 1,
            'note'            => (string) $r->note,
        ])->values();

        return Inertia::render('Admin/PickupRequest/Express', $this->payload($paginator, $rows, 'express'));
    }

    private function payload($p, $rows, string $mode): array
    {
        return [
            'rows' => $rows,
            'pagination' => [
                'current_page' => $p->currentPage(),
                'last_page'    => $p->lastPage(),
                'from'         => $p->firstItem(),
                'to'           => $p->lastItem(),
                'total'        => $p->total(),
                'prev_url'     => $p->previousPageUrl(),
                'next_url'     => $p->nextPageUrl(),
            ],
            'currency' => settings()->currency,
            't' => [
                'title'      => $mode === 'regular'
                    ? trim((__('pickupRequest.regular') ?: 'Regular') . ' ' . (__('pickupRequest.pickup_request') ?: 'Pickup request'))
                    : trim((__('pickupRequest.express') ?: 'Express') . ' ' . (__('pickupRequest.pickup_request') ?: 'Pickup request')),
                'list'       => __('levels.list') ?: 'List',
                'user'       => __('levels.user') ?: 'Merchant',
                'address'    => __('levels.address') ?: 'Address',
                'name'       => __('levels.name') ?: 'Name',
                'phone'      => __('levels.phone') ?: 'Phone',
                'estimetad'  => __('levels.estimetad_parcel') ?: 'Estimated parcels',
                'cod_amount' => __('pickupRequest.cod_amount') ?: 'COD amount',
                'invoice'    => __('pickupRequest.invoice') ?: 'Invoice',
                'weight'     => __('pickupRequest.weight') ?: 'Weight',
                'exchange'   => __('pickupRequest.exchange_parcel') ?: 'Exchange parcel',
                'note'       => __('levels.note') ?: 'Note',
                'yes'        => __('delete.yes') ?: 'Yes',
                'no'         => __('delete.no') ?: 'No',
                'no_rows'    => 'No requests yet.',
                'prev'       => 'Prev',
                'next'       => 'Next',
                'showing_results' => 'Showing :from – :to of :total',
            ],
        ];
    }
}
