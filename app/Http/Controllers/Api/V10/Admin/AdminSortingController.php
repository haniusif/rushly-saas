<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\ParcelStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Sorting-center mobile surface.
 *
 * The app tracks bags/routes device-side (ephemeral per-shift buckets);
 * this controller only owns the two operations that need server writes:
 *   - GET /admin/sorting/lookup/{tracking}  → resolve a scanned AWB
 *   - POST /admin/sorting/handover           → bulk-transfer parcels
 * plus GET /admin/sorting/hubs for the destination picker.
 */
class AdminSortingController extends Controller
{
    use ApiReturnFormatTrait;

    public function lookup(string $tracking)
    {
        $parcel = Parcel::with(['hub', 'transferHub', 'merchant.user'])
            ->where('tracking_id', $tracking)
            ->first();

        if (!$parcel) {
            return $this->responseWithError('Parcel not found', ['tracking_id' => $tracking], 404);
        }

        return $this->responseWithSuccess('Parcel resolved', [
            'parcel' => [
                'id'                 => $parcel->id,
                'tracking_id'        => $parcel->tracking_id,
                'customer_name'      => $parcel->customer_name,
                'customer_city'      => $parcel->customer_city,
                'customer_area'      => $parcel->customer_area,
                'status'             => $parcel->status,
                'current_hub_id'     => $parcel->hub_id,
                'current_hub_name'   => optional($parcel->hub)->name,
                'destination_hub_id' => $parcel->transfer_hub_id,
                'destination_hub'    => optional($parcel->transferHub)->name,
                'merchant_name'      => optional(optional($parcel->merchant)->user)->name,
                'cash_collection'    => (float) $parcel->cash_collection,
            ],
        ], 200);
    }

    public function hubs(Request $request)
    {
        $hubs = Hub::query()
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return $this->responseWithSuccess('Hubs', [
            'count' => $hubs->count(),
            'hubs'  => $hubs,
        ], 200);
    }

    /**
     * POST /admin/sorting/handover
     * Body: { parcel_ids: [int,...], destination_hub_id: int, note?: string }
     *
     * Flips each parcel to TRANSFER_TO_HUB, sets transfer_hub_id, and writes
     * a ParcelEvent row per parcel. HUB / INCHARGE users may only hand over
     * parcels currently in their own hub.
     */
    public function handover(Request $request)
    {
        $data = $request->validate([
            'parcel_ids'         => ['required', 'array', 'min:1'],
            'parcel_ids.*'       => ['integer'],
            'destination_hub_id' => ['required', 'integer', 'exists:hubs,id'],
            'note'               => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $userType = (string) $user->user_type;
        $hubScoped = in_array($userType, [UserType::HUB, UserType::INCHARGE], true)
            && !empty($user->hub_id);

        $q = Parcel::whereIn('id', $data['parcel_ids']);
        if ($hubScoped) {
            $q->where('hub_id', (int) $user->hub_id);
        }
        $parcels = $q->get();

        if ($parcels->isEmpty()) {
            return $this->responseWithError('No parcels eligible', [
                'requested' => count($data['parcel_ids']),
            ], 422);
        }

        $destHub = (int) $data['destination_hub_id'];
        $note = $data['note'] ?? 'Sorted for hub transfer';

        $updated = 0;
        DB::transaction(function () use ($parcels, $destHub, $note, &$updated) {
            foreach ($parcels as $p) {
                $p->transfer_hub_id = $destHub;
                $p->status          = ParcelStatus::TRANSFER_TO_HUB;
                $p->save();

                ParcelEvent::create([
                    'parcel_id'     => $p->id,
                    'parcel_status' => ParcelStatus::TRANSFER_TO_HUB,
                    'note'          => $note,
                ]);
                $updated++;
            }
        });

        return $this->responseWithSuccess('Handover recorded', [
            'updated'            => $updated,
            'destination_hub_id' => $destHub,
        ], 200);
    }
}
