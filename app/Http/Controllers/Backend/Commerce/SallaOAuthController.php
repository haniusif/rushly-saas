<?php

namespace App\Http\Controllers\Backend\Commerce;

use App\Commerce\Exceptions\CommerceException;
use App\Commerce\Factory\CommerceProviderFactory;
use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\CommerceProvider;
use App\Commerce\Providers\Salla\SallaProvider;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Phase 3.5 — thin controller wiring Salla's OAuth flow into the
 * Commerce module. Installs land in `commerce_connections` (new
 * pipeline), not in `salla_merchants` (legacy).
 *
 * Flow:
 *   1. Admin clicks "Install via Salla OAuth" on the connection edit
 *      page → GET /admin/commerce/connections/salla/oauth/redirect
 *   2. Controller generates state, stores it in session, redirects to
 *      Salla's authorize URL (provider's `buildAuthorizationUrl`).
 *   3. Salla redirects back → GET /admin/commerce/connections/salla/oauth/callback
 *   4. Controller verifies state (CSRF), delegates to provider's
 *      `handleOAuthCallback` for token exchange + merchant lookup.
 *   5. Upsert `commerce_connections` keyed by `remote_store_id`.
 *   6. Redirect to the connection's edit page for merchant wiring.
 *
 * Feature-flag-gated same as the rest of the Commerce module.
 * Tenant-scoped: `sallaCreds()` reads per-tenant client_id/secret from
 * `integration_settings.meta` — each tenant registers their own Salla
 * Partner app.
 */
class SallaOAuthController extends Controller
{
    private const STATE_SESSION_KEY = 'commerce_salla_oauth_state';

    public function __construct(
        private readonly CommerceProviderFactory $factory,
    ) {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    /**
     * Kick off the OAuth flow. Generates a CSRF state, stores it in
     * the session, and 302s to Salla's authorize URL.
     */
    public function redirect(Request $request)
    {
        /** @var SallaProvider $provider */
        $provider = $this->factory->make('salla');

        $state = Str::random(40);
        $request->session()->put(self::STATE_SESSION_KEY, $state);

        try {
            $authorizeUrl = $provider->buildAuthorizationUrl([
                'state' => $state,
            ]);
        } catch (CommerceException $e) {
            return redirect()->route('commerce.connections.index')
                ->withErrors(['oauth' => $e->getMessage()]);
        }

        Log::info('commerce.salla.oauth.redirect', [
            'state' => $state,
        ]);

        return redirect($authorizeUrl);
    }

    /**
     * Salla redirects here after user authorization. Verifies state,
     * exchanges code for tokens via the provider, upserts a
     * CommerceConnection, and lands the admin on the edit page.
     */
    public function callback(Request $request)
    {
        // CSRF: verify the state we sent on redirect matches what Salla
        // handed back. Missing / mismatched state is a hard fail.
        $expected = $request->session()->pull(self::STATE_SESSION_KEY);
        $received = (string) $request->query('state', '');

        if (! $expected || ! hash_equals($expected, $received)) {
            Log::warning('commerce.salla.oauth.state_mismatch', [
                'expected_present' => $expected !== null,
                'received'         => $received,
            ]);
            abort(403, 'OAuth state mismatch — possible CSRF or expired session.');
        }

        /** @var SallaProvider $provider */
        $provider = $this->factory->make('salla');

        try {
            $dto = $provider->handleOAuthCallback($request);
        } catch (CommerceException $e) {
            Log::error('commerce.salla.oauth.callback_failed', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('commerce.connections.index')
                ->withErrors(['oauth' => 'Salla OAuth failed: ' . $e->getMessage()]);
        }

        // Upsert connection. Match on (provider_code = salla,
        // remote_store_id) so re-installs land on the same row instead
        // of piling up duplicates.
        $providerRow = CommerceProvider::query()->where('code', 'salla')->firstOrFail();
        $companyId   = (int) (settings()->id ?? 0);

        $connection = null;
        if ($dto->remoteStoreId) {
            $connection = CommerceConnection::query()
                ->where('provider_id',     $providerRow->id)
                ->where('remote_store_id', $dto->remoteStoreId)
                ->first();
        }

        if (! $connection) {
            $connection = new CommerceConnection();
            $connection->company_id       = $companyId;
            $connection->provider_id      = $providerRow->id;
            $connection->connection_name  = $dto->connectionName;
            $connection->status           = 'active';
        }

        $connection->remote_store_id           = $dto->remoteStoreId;
        $connection->domain                    = $dto->domain ?? $connection->domain;
        $connection->access_token_encrypted    = $dto->accessToken;
        $connection->refresh_token_encrypted   = $dto->refreshToken;
        $connection->token_expires_at          = $dto->tokenExpiresAt;
        // Only overwrite webhook_secret if we got one from sallaCreds AND
        // the row doesn't already carry one (admin may have set a per-
        // connection override in Phase 2).
        if ($dto->webhookSecret && ! $connection->webhook_secret_encrypted) {
            $connection->webhook_secret_encrypted = $dto->webhookSecret;
        }
        $connection->settings           = array_merge((array) $connection->settings, $dto->settings);
        $connection->status             = 'active';
        $connection->last_tested_at     = now();
        $connection->save();

        Log::info('commerce.salla.oauth.installed', [
            'connection_id'   => $connection->id,
            'remote_store_id' => $connection->remote_store_id,
            'domain'          => $connection->domain,
        ]);

        Toastr::success("Salla store connected — {$connection->connection_name}.", 'Installed');
        return redirect()->route('commerce.connections.edit', $connection->id);
    }
}
