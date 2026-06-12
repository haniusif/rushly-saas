<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Resources\v10\ParcelLogsResource;
use App\Http\Resources\v10\ParcelResource;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Parcel;
use App\Repositories\Parcel\ParcelInterface;
use App\Support\ParcelStatusHelper;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminParcelController extends Controller
{
    use ApiReturnFormatTrait;

    protected ParcelInterface $parcels;

    public function __construct(ParcelInterface $parcels)
    {
        $this->parcels = $parcels;
    }

    public function index(Request $request)
    {
        $query = Parcel::query()
            ->with(['merchant.user', 'hub'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', (int) $status);
        }
        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('tracking_id', 'like', "%$q%")
                  ->orWhere('customer_phone', 'like', "%$q%")
                  ->orWhere('customer_name', 'like', "%$q%");
            });
        }
        if ($merchantId = $request->query('merchant_id')) {
            $query->where('merchant_id', (int) $merchantId);
        }
        if ($hubId = $request->query('hub_id')) {
            $query->where('hub_id', (int) $hubId);
        }
        if ($driverId = $request->query('driver_id')) {
            $query->where('delivery_man_id', (int) $driverId);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $this->clampToHub($query, $request->user());

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $parcels = $query->paginate($per);

        return $this->responseWithSuccess('admin.parcels', [
            'parcels' => ParcelResource::collection($parcels)->response()->getData(true),
        ], 200);
    }

    public function show($id, Request $request)
    {
        $parcel = Parcel::with(['merchant.user', 'hub'])->findOrFail($id);
        $this->ensureHubMatch($parcel, $request->user());

        return $this->responseWithSuccess('admin.parcel', [
            'parcel' => new ParcelResource($parcel),
        ], 200);
    }

    public function logs($id, Request $request)
    {
        $parcel = Parcel::findOrFail($id);
        $this->ensureHubMatch($parcel, $request->user());

        $events = $this->parcels->parcelEvents($id);

        return $this->responseWithSuccess('admin.parcel.logs', [
            'parcel' => new ParcelResource($parcel),
            'events' => ParcelLogsResource::collection($events),
        ], 200);
    }

    public function assignDriver($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer|exists:delivery_men,id',
            'note'      => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.parcel.assign', ['message' => $validator->errors()], 422);
        }

        $parcel = Parcel::findOrFail($id);
        $this->ensureHubMatch($parcel, $request->user());

        $driver = DeliveryMan::with('hub')->findOrFail($request->driver_id);

        $parcel->delivery_man_id = $driver->id;
        if ($driver->hub_id) {
            $parcel->hub_id = $driver->hub_id;
        }
        $parcel->save();

        // Append a parcel event row using the existing repo helper, which
        // also flips the parcel status when appropriate.
        try {
            $this->parcels->deliverymanAssign($parcel->id, $request->merge([
                'note' => $request->note ?: 'Assigned by admin',
            ]));
        } catch (\Throwable $e) {
            // Repo signature varies between branches; fall back to a manual event log.
            \App\Models\Backend\ParcelEvent::create([
                'parcel_id'       => $parcel->id,
                'delivery_man_id' => $driver->id,
                'hub_id'          => $driver->hub_id,
                'parcel_status'   => \App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN,
                'note'            => $request->note ?: 'Assigned by admin',
            ]);
        }

        return $this->responseWithSuccess('admin.parcel.assigned', [
            'parcel_id' => $parcel->id,
            'driver_id' => $driver->id,
        ], 200);
    }

    public function forceStatus($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer',
            'note'   => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.parcel.status', ['message' => $validator->errors()], 422);
        }

        $parcel = Parcel::findOrFail($id);
        $this->ensureHubMatch($parcel, $request->user());

        $next = (int) $request->status;
        if (class_exists(ParcelStatusHelper::class)) {
            try {
                ParcelStatusHelper::guardTransition($parcel->status, $next);
            } catch (\Throwable $e) {
                return $this->responseWithError('admin.parcel.status_invalid', ['reason' => $e->getMessage()], 422);
            }
        }

        $parcel->status = $next;
        $parcel->save();

        \App\Models\Backend\ParcelEvent::create([
            'parcel_id'     => $parcel->id,
            'parcel_status' => $next,
            'note'          => $request->note ?: 'Status forced by admin',
        ]);

        return $this->responseWithSuccess('admin.parcel.status_updated', [
            'parcel_id' => $parcel->id,
            'status'    => $next,
        ], 200);
    }

    private function clampToHub($query, $user): void
    {
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE) && $user->hub_id) {
            $query->where('hub_id', (int) $user->hub_id);
        }
    }

    private function ensureHubMatch(Parcel $parcel, $user): void
    {
        $type = (int) $user->user_type;
        if (($type === UserType::HUB || $type === UserType::INCHARGE)
            && $user->hub_id
            && (int) $parcel->hub_id !== (int) $user->hub_id) {
            abort(403, 'Hub mismatch');
        }
    }
}
