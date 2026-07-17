<?php

namespace App\Http\Controllers\Api\V10;

use App\Commerce\Models\CommerceConnection;
use App\Http\Controllers\Controller;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only feed of the merchant's connected storefront platforms
 * (Salla / Zid / WooCommerce / Shopify), rendered on the merchant
 * mobile app's "Store connections" screen.
 *
 * Secrets are hidden by the CommerceConnection model's `$hidden`, so
 * the payload we ship is safe to render as-is.
 */
class MerchantStoreConnectionsController extends Controller
{
    use ApiReturnFormatTrait;

    public function index()
    {
        $merchantId = Auth::user()->merchant->id ?? null;
        if (!$merchantId) {
            return $this->responseWithError('No merchant scope', [], 403);
        }

        $rows = CommerceConnection::companywise()
            ->where('merchant_id', $merchantId)
            ->with('provider:id,code,name')
            ->latest('id')
            ->get();

        return $this->responseWithSuccess('Store connections', [
            'connections' => $rows->map(fn (CommerceConnection $c) => [
                'id'              => $c->id,
                'provider_code'   => optional($c->provider)->code,
                'provider_name'   => optional($c->provider)->name,
                'connection_name' => $c->connection_name,
                'domain'          => $c->domain,
                'status'          => $c->status,
                'is_default'      => (bool) $c->is_default,
                'last_tested_at'  => optional($c->last_tested_at)->toIso8601String(),
                'last_sync_at'    => optional($c->last_sync_at)->toIso8601String(),
                'last_event_at'   => optional($c->last_event_at)->toIso8601String(),
            ])->values(),
        ], 200);
    }
}
