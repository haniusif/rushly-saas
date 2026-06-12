<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;

class AdminHubController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request)
    {
        $query = Hub::query()->latest();

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('phone', 'like', "%$q%")
                  ->orWhere('address', 'like', "%$q%");
            });
        }

        $hubs = $query->paginate(max(10, min(100, (int) $request->query('per_page', 25))));

        return $this->responseWithSuccess('admin.hubs', [
            'hubs' => $hubs,
        ], 200);
    }

    public function show($id)
    {
        $hub = Hub::findOrFail($id);

        $totals = [
            'drivers' => DeliveryMan::where('hub_id', $hub->id)->count(),
            'parcels' => Parcel::where('hub_id', $hub->id)->count(),
        ];

        return $this->responseWithSuccess('admin.hub', [
            'hub'    => $hub,
            'totals' => $totals,
        ], 200);
    }
}
