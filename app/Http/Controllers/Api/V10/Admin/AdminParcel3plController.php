<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Http\Controllers\Backend\ParcelController as WebParcelController;
use App\Http\Controllers\Controller;
use App\Models\Backend\Parcel;
use App\Models\Backend\Parcels_3pl;
use App\Services\AramexService;
use App\Services\JetService;
use App\Services\ZajelService;
use App\Shipping\Models\ShippingConnection;
use App\Shipping\Models\ShippingProvider;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin-side 3PL controller for the mobile app.
 *
 * The write path (assign) delegates straight to
 * `Backend\ParcelController::ThirdPartyLogistics` so the mobile flow is
 * guaranteed to match what the web panel does — same payload shape,
 * same provider services, same `Parcels_3pl` rows. We just wrap it
 * with an admin-only guard and expose a companion GET endpoint to list
 * available providers + past assignments.
 */
class AdminParcel3plController extends Controller
{
    use ApiReturnFormatTrait;

    private const PROVIDERS = ['panda', 'zajel', 'aramex', 'jet', 'logestechs'];

    public function status($id, Request $request)
    {
        $parcel = Parcel::findOrFail($id);

        $past = Parcels_3pl::companywise()
            ->where('parcel_id', $parcel->id)
            ->latest('id')
            ->limit(20)
            ->get(['id', 'parcel_3pl_name', 'awb_number', 'awb_pdf', 'current_status', 'status_datetime', 'created_at'])
            ->map(fn (Parcels_3pl $p) => [
                'id'              => $p->id,
                'provider'        => $p->parcel_3pl_name,
                'awb_number'      => $p->awb_number,
                'awb_pdf'         => $p->awb_pdf,
                'current_status'  => $p->current_status,
                'status_datetime' => optional($p->status_datetime)->toIso8601String(),
                'created_at'      => optional($p->created_at)->toIso8601String(),
            ]);

        return $this->responseWithSuccess('admin.parcel.3pl', [
            'parcel_id' => $parcel->id,
            'providers' => $this->providerStatus(),
            'past'      => $past,
        ], 200);
    }

    public function assign($id, Request $request, WebParcelController $web)
    {
        $validator = Validator::make($request->all(), [
            'company'       => 'required|string|in:' . implode(',', self::PROVIDERS),
            'connection_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.parcel.3pl.validation',
                ['message' => $validator->errors()], 422);
        }

        Parcel::findOrFail($id); // 404 gate before delegating

        // ParcelController::ThirdPartyLogistics reads `$request->company` +
        // `$request->input('connection_id')` and returns a JsonResponse.
        // Delegating gives us provider parity for free.
        return $web->ThirdPartyLogistics($id, $request);
    }

    private function providerStatus(): array
    {
        $status = [];
        foreach (self::PROVIDERS as $p) {
            $status[] = [
                'name'       => $p,
                'configured' => $this->isConfigured($p),
            ];
        }
        return $status;
    }

    private function isConfigured(string $provider): bool
    {
        return match ($provider) {
            'aramex'     => app(AramexService::class)->isConfigured(),
            'jet'        => app(JetService::class)->isConfigured(),
            'zajel'      => app(ZajelService::class)->isConfigured(),
            'logestechs' => $this->logestechsConfigured(),
            'panda'      => true, // Panda has no gating helper; treat as always available.
            default      => false,
        };
    }

    private function logestechsConfigured(): bool
    {
        // A connection is enough — either explicitly default, or any active one
        // for a logestechs provider row.
        $provider = ShippingProvider::query()->where('code', 'logestechs')->first();
        if (!$provider) return false;
        return ShippingConnection::companywise()
            ->where('provider_id', $provider->id)
            ->where('status', 'active')
            ->exists();
    }
}
