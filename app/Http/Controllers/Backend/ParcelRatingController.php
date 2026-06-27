<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ParcelStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Public, signed-URL rating capture for delivered parcels.
 *
 * Flow:
 *   1. After a parcel is marked DELIVERED, the caller (admin app or future
 *      SMS hook) generates a signed URL via Parcel::ratingUrl() and shares it
 *      with the customer.
 *   2. The customer clicks the URL → form Blade page.
 *   3. On submit, store/update the rating (idempotent via UNIQUE(parcel_id)).
 *
 * No authentication required — security comes from Laravel's signed URL
 * mechanism plus a 60-day signature expiry.
 */
class ParcelRatingController extends Controller
{
    /** Show the rating form. Route: GET /r/parcel/{id}/rate?signature=… */
    public function show(Request $request, int $id)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link expired or invalid.');
        }

        $parcel = Parcel::withoutGlobalScope('tenant')->find($id);
        if (! $parcel) abort(404);

        // Only allow rating delivered parcels — surfacing a rating prompt for
        // an undelivered shipment would confuse the customer.
        if ((int) $parcel->status !== ParcelStatus::DELIVERED) {
            return view('backend.performance.rating.not_yet', ['parcel' => $parcel]);
        }

        $existing = ParcelRating::where('parcel_id', $parcel->id)->first();

        return view('backend.performance.rating.show', [
            'parcel'   => $parcel,
            'existing' => $existing,
            'submit'   => URL::temporarySignedRoute(
                'parcel.rating.store', now()->addDays(60), ['id' => $parcel->id],
            ),
        ]);
    }

    /** Persist the rating. Route: POST /r/parcel/{id}/rate?signature=… */
    public function store(Request $request, int $id)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link expired or invalid.');
        }
        $data = $request->validate([
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $parcel = Parcel::withoutGlobalScope('tenant')->find($id);
        if (! $parcel) abort(404);
        if ((int) $parcel->status !== ParcelStatus::DELIVERED) {
            abort(422, 'Parcel not yet delivered.');
        }

        // Deliveryman = the one who actually marked the delivered event.
        $deliveryEvent = $parcel->parcelEvent()
            ->where('parcel_status', ParcelStatus::DELIVERED)
            ->orderByDesc('id')
            ->first();

        ParcelRating::updateOrCreate(
            ['parcel_id' => $parcel->id],
            [
                'company_id'     => $parcel->company_id,
                'deliveryman_id' => $deliveryEvent->delivery_man_id ?? null,
                'merchant_id'    => $parcel->merchant_id,
                'customer_phone' => $parcel->customer_phone,
                'rating'         => (int) $data['rating'],
                'comment'        => $data['comment'] ?? null,
                'source'         => 'public',
            ],
        );

        return view('backend.performance.rating.thanks', ['parcel' => $parcel]);
    }
}
