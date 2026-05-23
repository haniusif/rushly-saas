<?php

namespace App\Http\Controllers\Api\V10;

use App\Enums\NdrFailureReason;
use App\Http\Controllers\Controller;
use App\Models\Backend\Ndr;
use App\Models\Backend\Parcel;
use App\Repositories\NdrRepositoryInterface;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Used by the deliveryman mobile app to submit/track NDRs in the field.
 * All endpoints require auth:sanctum + CheckApiKey (applied in api.php).
 */
class NdrApiController extends Controller
{
    use ApiReturnFormatTrait;

    protected NdrRepositoryInterface $repo;

    public function __construct(NdrRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        try {
            $ndrs = $this->repo->all($request);
            return $this->responseWithSuccess('NDR list', ['ndrs' => $ndrs], 200);
        } catch (\Throwable $e) {
            return $this->responseWithError('Failed to load NDRs', ['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $ndr = $this->repo->find($id);
        if (!$ndr) {
            return $this->responseWithError('NDR not found', [], 404);
        }
        return $this->responseWithSuccess('NDR details', ['ndr' => $ndr], 200);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'parcel_id'         => ['required', 'integer', 'exists:parcels,id'],
            'failure_reason'    => ['required', 'string'],
            'driver_notes'      => ['nullable', 'string'],
            'driver_photo'      => ['nullable', 'image', 'max:5120'],
            'next_attempt_date' => ['nullable', 'date'],
        ]);
        if ($v->fails()) {
            return $this->responseWithError('Validation failed', ['errors' => $v->errors()], 422);
        }

        if ($this->repo->todayOpenForParcel((int) $request->parcel_id)) {
            return $this->responseWithError('An open NDR already exists for this parcel today.', [], 409);
        }

        $data = $v->validated();
        $data['attempt_number'] = Ndr::companywise()->where('parcel_id', $data['parcel_id'])->count() + 1;
        $data['created_by']     = Auth::id();
        $data['deliveryman_id'] = Auth::id();

        if ($request->hasFile('driver_photo')) {
            $f = $request->file('driver_photo');
            $name = date('YmdHis') . uniqid() . '.' . $f->getClientOriginalExtension();
            $f->move(public_path('uploads/ndr/'), $name);
            $data['driver_photo'] = 'uploads/ndr/' . $name;
        }

        try {
            $ndr = $this->repo->create($data);
            return $this->responseWithSuccess('NDR recorded', ['ndr' => $ndr], 201);
        } catch (\Throwable $e) {
            return $this->responseWithError('Could not record NDR', ['error' => $e->getMessage()], 500);
        }
    }

    public function notifyCustomer(int $id)
    {
        $ndr = $this->repo->find($id);
        if (!$ndr) {
            return $this->responseWithError('NDR not found', [], 404);
        }

        // Stub for Phase 6 — wires into SmsSendSettingHelper / FCM.
        // Marks the NDR as notified so the deliveryman app can show "SMS sent" state.
        $ndr->customer_notified = true;
        $ndr->save();

        return $this->responseWithSuccess('Customer notified', ['ndr_id' => $ndr->id], 200);
    }

    public function byParcel(int $parcelId)
    {
        $parcel = Parcel::companywise()->find($parcelId);
        if (!$parcel) {
            return $this->responseWithError('Parcel not found', [], 404);
        }

        $ndrs = Ndr::companywise()
            ->where('parcel_id', $parcelId)
            ->with(['deliveryman', 'createdBy', 'resolvedBy'])
            ->orderBy('attempt_number')
            ->get();

        return $this->responseWithSuccess('NDRs for parcel', [
            'parcel_id'      => $parcelId,
            'tracking_id'    => $parcel->tracking_id,
            'attempts_used'  => $ndrs->count(),
            'attempts_left'  => max(0, 3 - $ndrs->count()),
            'ndrs'           => $ndrs,
        ], 200);
    }

    public function stats()
    {
        try {
            return $this->responseWithSuccess('NDR stats', $this->repo->stats(), 200);
        } catch (\Throwable $e) {
            return $this->responseWithError('Could not load stats', ['error' => $e->getMessage()], 500);
        }
    }
}
