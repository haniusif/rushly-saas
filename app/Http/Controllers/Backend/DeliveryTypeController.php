<?php

namespace App\Http\Controllers\Backend;

use App\Enums\DeliveryType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryTypeController extends Controller
{
    private const ROWS = [
        ['id' => DeliveryType::SAMEDAY,     'key' => 'same_day'],
        ['id' => DeliveryType::NEXTDAY,     'key' => 'next_day'],
        ['id' => DeliveryType::SUBCITY,     'key' => 'sub_city'],
        ['id' => DeliveryType::OUTSIDECITY, 'key' => 'outside_city'],
    ];

    public function index()
    {
        $rows = collect(self::ROWS)->map(function ($r) {
            $label = trans('deliveryType.' . $r['id']);
            return [
                'id'     => $r['id'],
                'key'    => $r['key'],
                'label'  => $label === 'deliveryType.' . $r['id'] ? str_replace('_', ' ', $r['key']) : $label,
                'active' => settingHelper($r['key']) == Status::ACTIVE,
            ];
        })->values();

        return Inertia::render('Admin/DeliveryType/Index', [
            'rows'        => $rows,
            'permissions' => [
                'status_change' => hasPermission('delivery_type_status_change'),
            ],
            'urls' => [
                'status' => route('delivery-type.status'),
            ],
            't' => [
                'title'  => __('levels.delivery_type') ?: 'Delivery type',
                'list'   => __('levels.list') ?: 'List',
                'name'   => __('levels.title') ?: 'Title',
                'status' => __('levels.status') ?: 'Status',
            ],
        ]);
    }

    public function status(Request $request)
    {
        $deliverytype = Config::where('key', $request->key)->first();
        if ($deliverytype->value == Status::ACTIVE) {
            $deliverytype->value = Status::INACTIVE;
        } else {
            $deliverytype->value = Status::ACTIVE;
        }
        $deliverytype->save();
        return $deliverytype;
    }
}
