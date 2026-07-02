<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Support\ParcelStatusHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only public tracking endpoint. Authenticated by a per-tenant
 * PublicTrackingApiKey (see VerifyPublicTrackingApiKey middleware).
 *
 * Returns a limited projection — no customer PII, no financials — so
 * a merchant's storefront can render a status timeline without
 * exposing operational data.
 */
class PublicTrackingController extends Controller
{
    public function show(string $trackingId, Request $request): JsonResponse
    {
        $key = $request->attributes->get('publicTrackingApiKey');
        // The middleware guarantees this is set — but be defensive.
        if (! $key) {
            return response()->json(['success' => false, 'error' => 'unauthenticated'], 401);
        }

        $parcel = Parcel::withoutGlobalScopes()
            ->where('company_id', $key->company_id)
            ->where('tracking_id', $trackingId)
            ->select(['id', 'tracking_id', 'status', 'created_at', 'expected_delivery_at'])
            ->first();

        if (! $parcel) {
            return response()->json([
                'success' => false,
                'error'   => 'not_found',
                'message' => 'No parcel found with the provided tracking id.',
            ], 404);
        }

        $events = ParcelEvent::query()
            ->where('parcel_id', $parcel->id)
            ->orderBy('created_at', 'asc')
            ->get(['parcel_status', 'note', 'created_at'])
            ->map(fn ($e) => [
                'status'       => (int) $e->parcel_status,
                'status_label' => ParcelStatusHelper::label((int) $e->parcel_status),
                'note'         => $e->note,
                'occurred_at'  => optional($e->created_at)->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'tracking_id'          => $parcel->tracking_id,
                'status'               => (int) $parcel->status,
                'status_label'         => ParcelStatusHelper::label((int) $parcel->status),
                'created_at'           => optional($parcel->created_at)->toIso8601String(),
                'expected_delivery_at' => optional($parcel->expected_delivery_at)->toIso8601String(),
                'events'               => $events,
            ],
        ])->withHeaders([
            // Permissive CORS — the whole point of this endpoint is to
            // be embeddable on third-party storefronts. Origin-level
            // gating happens in the middleware when allowed_origins is
            // configured on the key.
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'X-API-Key, Content-Type',
        ]);
    }
}
