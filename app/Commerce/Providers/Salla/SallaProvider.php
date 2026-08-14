<?php

namespace App\Commerce\Providers\Salla;

use App\Commerce\Contracts\SupportsInventorySync;
use App\Commerce\Contracts\SupportsOAuth;
use App\Commerce\Contracts\SupportsOrderWriteback;
use App\Commerce\Contracts\SupportsWebhooks;
use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\DTOs\RawOrderDTO;
use App\Commerce\DTOs\TestResultDTO;
use App\Commerce\DTOs\WebhookEventDTO;
use App\Commerce\Exceptions\ProviderRejectedRequestException;
use App\Commerce\Exceptions\ProviderUnavailableException;
use App\Commerce\Providers\AbstractCommerceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2 minimal Salla provider.
 *
 * What's wired:
 *   - testConnection() — probes Salla's /merchant/info with the stored
 *     access_token. Real success/failure feedback in the admin UI.
 *   - fetchOrder()     — GETs /orders/{id}, returns RawOrderDTO with the
 *     untouched provider payload.
 *   - verifyWebhook()  — reproduces the HMAC/Token strategy from the
 *     existing app/Salla/Http/Middleware/VerifyWebhook so inbound events
 *     can be authenticated against the per-connection webhook_secret.
 *   - parseWebhookEvent() — extracts event + merchant id + id and composes
 *     a stable idempotency key for the Phase 3 webhook_events table.
 *
 * What's stubbed (throws ProviderUnavailableException with a clear "Phase
 * N" reason):
 *   - pushOrderUpdate    — full status writeback lives in Phase 3
 *   - authenticate (refresh) — needs the Salla SDK refresh path wired in;
 *     for Phase 2 we trust the stored access_token until it's known-expired
 *   - OAuth install flow — Phase 3. The legacy app/Salla/Http/Controllers/
 *     OAuthController stays live for installs; this provider is for
 *     manually-pasted creds in Phase 2.
 *
 * Why not delegate to the existing app/Salla/Services/ApiClient?
 * ApiClient takes a Merchant model. Rather than coupling the new module to
 * the legacy schema (which we're explicitly trying to NOT touch in Phase 1
 * + 2), we re-implement the half-dozen calls via $this->http() so they
 * route through commerce_api_logs and the AbstractCommerceProvider's
 * error normalization for free.
 */
class SallaProvider extends AbstractCommerceProvider implements SupportsOAuth, SupportsWebhooks, SupportsOrderWriteback, SupportsInventorySync
{
    public function code(): string
    {
        return 'salla';
    }

    public function testConnection(CommerceConnectionDTO $connection): TestResultDTO
    {
        if (! $connection->accessToken) {
            return TestResultDTO::fail('Access token is not set. Paste an access token (or use the OAuth install flow when it ships in Phase 3).');
        }

        try {
            $resp = $this->http('GET', '/merchant/info', $connection, function ($r) use ($connection) {
                return $r->withToken($connection->accessToken);
            });
        } catch (ProviderUnavailableException $e) {
            return TestResultDTO::fail($e->getMessage());
        } catch (\Throwable $e) {
            return TestResultDTO::fail('Transport error: ' . $e->getMessage());
        }

        if ($resp->status() === 401 || $resp->status() === 403) {
            return TestResultDTO::fail("Salla rejected the access token (HTTP {$resp->status()}). Refresh / re-install required.");
        }
        if (! $resp->successful()) {
            return TestResultDTO::fail("Salla returned HTTP {$resp->status()}.", ['body' => $resp->json() ?: $resp->body()]);
        }

        $data = (array) ($resp->json('data') ?? []);
        $name = (string) ($data['name'] ?? $data['username'] ?? 'merchant');
        $id   = $data['id'] ?? null;
        return TestResultDTO::ok(
            "Connected to Salla merchant '{$name}'" . ($id ? " (id {$id})" : ''),
            ['merchant' => $data],
        );
    }

    /**
     * Phase 2 stub. Returns the DTO unchanged unless the token is known to
     * be expired — in which case we fail loudly so the caller can decide
     * whether to mark the connection `reauth_required`. Real refresh lives
     * in Phase 3 alongside the OAuth callback wiring.
     */
    public function authenticate(CommerceConnectionDTO $connection): CommerceConnectionDTO
    {
        if (! $connection->tokenExpiresAt) {
            return $connection;
        }
        try {
            $expires = new \DateTimeImmutable($connection->tokenExpiresAt);
        } catch (\Throwable) {
            return $connection;
        }
        // Phase 3.5 — attempt refresh when the token is within its
        // expiry window OR already past. `refreshAccessToken` throws
        // ProviderRejectedRequestException if the refresh_token itself
        // is invalid (revoked / merchant uninstalled the app); caller
        // (ConnectionService or a scheduled refresher job) is expected
        // to flip connection.status → 'reauth_required' in that case.
        if ($expires > (new \DateTimeImmutable())->modify('+5 minutes')) {
            return $connection;
        }
        return $this->refreshAccessToken($connection);
    }

    public function fetchOrder(CommerceConnectionDTO $connection, string $remoteOrderId): RawOrderDTO
    {
        if (! $connection->accessToken) {
            throw new ProviderUnavailableException('Salla access token is not set on this connection.');
        }

        $resp = $this->http('GET', '/orders/' . rawurlencode($remoteOrderId), $connection, function ($r) use ($connection) {
            return $r->withToken($connection->accessToken);
        });

        if ($resp->status() === 404) {
            throw new ProviderRejectedRequestException("Salla order {$remoteOrderId} not found.", ['status' => 404]);
        }
        if (! $resp->successful()) {
            throw new ProviderRejectedRequestException(
                "Salla returned HTTP {$resp->status()} fetching order {$remoteOrderId}.",
                ['status' => $resp->status(), 'body' => $resp->json() ?: $resp->body()],
            );
        }

        $data = (array) ($resp->json('data') ?? []);
        return new RawOrderDTO(
            providerCode:      'salla',
            remoteOrderId:     (string) ($data['id'] ?? $remoteOrderId),
            remoteOrderNumber: isset($data['reference_id']) ? (string) $data['reference_id'] : null,
            payload:           $data,
            occurredAt:        isset($data['date']['date']) ? (string) $data['date']['date'] : null,
        );
    }

    public function pushOrderUpdate(CommerceConnectionDTO $connection, string $remoteOrderId, array $update): void
    {
        // Salla writeback typically targets a shipment, not the order — the
        // existing app/Salla/Services/ApiClient does this via
        // /shipments/{id}/status. Wiring through the Phase 3 OMS layer
        // is where we route Rushly's ParcelStatusChanged → Salla writeback.
        throw new ProviderUnavailableException(
            'Salla pushOrderUpdate is wired in Phase 3 (OMS → CommerceListener handoff).',
            ['remote_order_id' => $remoteOrderId, 'update' => $update],
        );
    }

    // ---------------- SupportsOAuth ----------------

    /**
     * Build Salla's authorize URL for the OAuth 2.0 authorization-code
     * flow. Salla-side client credentials live in the tenant's
     * `integration_settings.meta.oauth_client_id` (read via
     * `sallaCreds()`).
     *
     * $params keys:
     *   - state       (required) — CSRF token; controller stores in session
     *   - redirect_uri (optional) — falls back to sallaCreds('oauth_redirect_uri')
     *                                or the SallaOAuthController's callback URL
     *   - scope       (optional) — defaults to 'offline_access' (we need refresh tokens)
     */
    public function buildAuthorizationUrl(array $params): string
    {
        $clientId    = (string) sallaCreds('oauth_client_id');
        $redirectUri = (string) (
            $params['redirect_uri']
            ?? sallaCreds('oauth_redirect_uri')
            ?? url('/admin/commerce/connections/salla/oauth/callback')
        );
        $state       = (string) ($params['state'] ?? '');
        $scope       = (string) ($params['scope'] ?? 'offline_access');
        $authorize   = (string) $this->config('oauth_authorize_url', 'https://accounts.salla.sa/oauth2/auth');

        if ($clientId === '') {
            throw new ProviderUnavailableException(
                'Salla oauth_client_id is not configured for this tenant. ' .
                'Set it via Admin → Integrations → Salla before starting the install flow.',
            );
        }

        return $authorize . '?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
        ]);
    }

    /**
     * Exchange the OAuth `code` for tokens, then fetch the merchant
     * profile so we know which Salla store this install belongs to.
     * Returns a fully populated ConnectionDTO ready for the controller
     * to upsert into `commerce_connections`.
     *
     * `id` on the returned DTO is null — the controller decides whether
     * this is a fresh install (INSERT) or a re-install of an existing
     * connection (UPDATE keyed on remote_store_id).
     */
    public function handleOAuthCallback(Request $request): CommerceConnectionDTO
    {
        if ($request->filled('error')) {
            throw new ProviderRejectedRequestException(
                'Salla returned OAuth error: ' . $request->input('error'),
                ['error_description' => $request->input('error_description')],
            );
        }

        $code = (string) $request->input('code', '');
        if ($code === '') {
            throw new ProviderRejectedRequestException(
                'Missing authorization code on Salla callback.',
                ['query' => $request->query()],
            );
        }

        $clientId     = (string) sallaCreds('oauth_client_id');
        $clientSecret = (string) sallaCreds('oauth_client_secret');
        $redirectUri  = (string) (
            sallaCreds('oauth_redirect_uri')
            ?: url('/admin/commerce/connections/salla/oauth/callback')
        );

        if ($clientId === '' || $clientSecret === '') {
            throw new ProviderUnavailableException(
                'Salla OAuth client credentials not configured for this tenant.',
            );
        }

        // Exchange code → tokens. Standard OAuth2 form-urlencoded.
        $tokenUrl = (string) $this->config('oauth_token_url', 'https://accounts.salla.sa/oauth2/token');
        $body = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
        ];
        try {
            $resp = $this->http('POST', $tokenUrl, null, function ($r) use ($body) {
                // Explicit form-urlencoded body. `send()` on the underlying
                // PendingRequest doesn't attach form data on its own — we
                // materialise the body here.
                return $r->withBody(http_build_query($body), 'application/x-www-form-urlencoded');
            }, rawBody: $body)->throw();
        } catch (\Throwable $e) {
            throw new ProviderRejectedRequestException(
                'Salla token exchange rejected: ' . $e->getMessage(),
                ['url' => $tokenUrl],
                $e,
            );
        }

        $tokens        = (array) $resp->json();
        $accessToken   = (string) ($tokens['access_token']  ?? '');
        $refreshToken  = (string) ($tokens['refresh_token'] ?? '');
        $expiresIn     = (int)    ($tokens['expires_in']    ?? 0);
        $tokenExpires  = $expiresIn > 0 ? now()->addSeconds($expiresIn)->toIso8601String() : null;

        if ($accessToken === '') {
            throw new ProviderRejectedRequestException('Salla returned no access_token.', ['response' => $tokens]);
        }

        // Fetch the merchant profile so we can populate remote_store_id +
        // domain. Salla exposes this at /oauth2/user/info OR
        // /admin/v2/store/info depending on the app config; we use the
        // admin surface since that's what the rest of the provider uses.
        $storeInfo = [];
        try {
            $storeResp = $this->http('GET', '/store/info', null, fn ($r) => $r->withToken($accessToken));
            if ($storeResp->successful()) {
                $storeInfo = (array) ($storeResp->json('data') ?? []);
            }
        } catch (\Throwable) {
            // Non-fatal — connection can still save without domain/name.
            // remote_store_id falls back to the token payload's merchant id.
        }

        $remoteStoreId = (string) (
            $storeInfo['id']
            ?? $tokens['merchant']['id']
            ?? ($tokens['merchant'] ?? '')
        );
        $domain = (string) (
            $storeInfo['url']    ?? ''
        );

        return new CommerceConnectionDTO(
            id:              null,
            companyId:       (int) (settings()->id ?? 0),
            providerCode:    'salla',
            connectionName:  (string) ($storeInfo['name'] ?? "Salla store {$remoteStoreId}"),
            remoteStoreId:   $remoteStoreId !== '' ? $remoteStoreId : null,
            domain:          $domain !== '' ? $domain : null,
            merchantId:      null,          // controller lets admin pick Rushly merchant on the edit page
            accessToken:     $accessToken,
            refreshToken:    $refreshToken !== '' ? $refreshToken : null,
            tokenExpiresAt:  $tokenExpires,
            apiKey:          null,
            apiSecret:       null,
            webhookSecret:   (string) sallaCreds('webhook_secret') ?: null,
            settings:        [
                'oauth_installed_at' => now()->toIso8601String(),
            ],
        );
    }

    /**
     * Exchange the stored refresh_token for a fresh access_token.
     * Returns a new DTO with the swapped tokens; caller persists.
     */
    public function refreshAccessToken(CommerceConnectionDTO $connection): CommerceConnectionDTO
    {
        if (! $connection->refreshToken) {
            throw new ProviderRejectedRequestException(
                'No refresh_token stored on this connection — cannot refresh. Re-install via OAuth.',
            );
        }

        $clientId     = (string) sallaCreds('oauth_client_id');
        $clientSecret = (string) sallaCreds('oauth_client_secret');
        $tokenUrl     = (string) $this->config('oauth_token_url', 'https://accounts.salla.sa/oauth2/token');

        $body = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $connection->refreshToken,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ];
        try {
            $resp = $this->http('POST', $tokenUrl, $connection, function ($r) use ($body) {
                return $r->withBody(http_build_query($body), 'application/x-www-form-urlencoded');
            }, rawBody: $body)->throw();
        } catch (\Throwable $e) {
            throw new ProviderRejectedRequestException(
                'Salla token refresh rejected: ' . $e->getMessage(),
                [],
                $e,
            );
        }

        $tokens       = (array) $resp->json();
        $accessToken  = (string) ($tokens['access_token']  ?? '');
        $refreshToken = (string) ($tokens['refresh_token'] ?? $connection->refreshToken);
        $expiresIn    = (int)    ($tokens['expires_in']    ?? 0);
        $tokenExpires = $expiresIn > 0 ? now()->addSeconds($expiresIn)->toIso8601String() : $connection->tokenExpiresAt;

        if ($accessToken === '') {
            throw new ProviderRejectedRequestException('Salla refresh returned no access_token.', ['response' => $tokens]);
        }

        return $connection->withTokens($accessToken, $refreshToken, $tokenExpires);
    }

    // ---------------- SupportsWebhooks ----------------

    /**
     * Reproduces the verification logic from
     * app/Salla/Http/Middleware/VerifyWebhook against a per-connection
     * webhook secret. Two strategies: HMAC-SHA256 over the raw body
     * (default) or Bearer-token equality.
     */
    public function verifyWebhook(Request $request, ?string $webhookSecret): bool
    {
        if (! $webhookSecret) {
            return false;
        }
        $strategy = $request->header('X-Salla-Security-Strategy', 'Signature');

        if ($strategy === 'Token') {
            return hash_equals($webhookSecret, (string) $request->header('Authorization'));
        }

        $signature = (string) $request->header('X-Salla-Signature');
        $expected  = hash_hmac('sha256', (string) $request->getContent(), $webhookSecret);
        return hash_equals($expected, $signature);
    }

    public function parseWebhookEvent(Request $request): WebhookEventDTO
    {
        $payload    = (array) $request->all();
        $event      = (string) ($payload['event'] ?? '');
        $merchantId = (string) ($payload['merchant']
            ?? data_get($payload, 'data.merchant.id')
            ?? data_get($payload, 'data.store.id')
            ?? '');
        $eventId = (string) (
            data_get($payload, 'data.id')
            ?? $payload['id']
            ?? hash('sha256', (string) $request->getContent())
        );

        return new WebhookEventDTO(
            providerCode:   'salla',
            connectionId:   null,                                    // resolved by Phase 3 ingest service
            eventType:      $event,
            idempotencyKey: "salla:{$merchantId}:{$event}:{$eventId}",
            payload:        $payload,
            signature:      (string) $request->header('X-Salla-Signature'),
            occurredAt:     (string) ($payload['created_at'] ?? null),
        );
    }

    // ---------------- SupportsInventorySync ----------------

    /**
     * Push stock levels to Salla. Salla keys products by internal id,
     * not SKU, so we do a lookup per SKU: GET /products?sku=<sku> →
     * find product id → PUT /products/{id}/quantity with absolute value.
     *
     * Absolute-value semantics: safe to retry with the same payload
     * because Salla replaces (not increments) the stored quantity.
     *
     * Unknown / unmatched SKUs are logged + skipped, not raised — a
     * merchant may have Rushly-only SKUs that don't exist on their
     * Salla storefront, and one missing SKU shouldn't fail a batched
     * job that also carries valid ones.
     */
    public function pushInventoryUpdate(CommerceConnectionDTO $connection, array $updates): void
    {
        if (! $connection->accessToken) {
            throw new ProviderUnavailableException('Salla access token missing on connection.');
        }

        foreach ($updates as $u) {
            $sku = (string) ($u['sku'] ?? '');
            $qty = (int) ($u['quantity'] ?? 0);
            if ($sku === '') {
                continue;
            }

            try {
                $productId = $this->resolveProductIdBySku($connection, $sku);
                if (! $productId) {
                    Log::info('commerce.salla.push_stock.sku_not_found', [
                        'connection_id' => $connection->id,
                        'sku'           => $sku,
                    ]);
                    continue;
                }

                $body = ['quantity' => $qty];
                $resp = $this->http('PUT', '/products/' . rawurlencode((string) $productId) . '/quantity', $connection,
                    function ($r) use ($connection, $body) {
                        return $r->withToken($connection->accessToken)->asJson()->withBody(
                            json_encode($body), 'application/json'
                        );
                    },
                    rawBody: $body,
                );

                if (! $resp->successful()) {
                    Log::warning('commerce.salla.push_stock.rejected', [
                        'connection_id' => $connection->id,
                        'sku'           => $sku,
                        'salla_product_id' => $productId,
                        'status'        => $resp->status(),
                        'body'          => $resp->json() ?: $resp->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Log per-SKU failures but continue with the rest of
                // the batch. Job-level retry catches transient outages
                // (5xx / transport) — those come back as
                // ProviderUnavailableException from $this->http() and
                // would propagate up here.
                Log::warning('commerce.salla.push_stock.sku_failed', [
                    'connection_id' => $connection->id,
                    'sku'           => $sku,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Resolve a Salla product's internal id by SKU. Two Salla API
     * surfaces support this — `GET /products?sku=<sku>` is the direct
     * search. Response shape: `{ data: [{ id, sku, ... }, ...] }`.
     * Returns null when no product matches (unknown SKU) so the caller
     * can skip.
     */
    private function resolveProductIdBySku(CommerceConnectionDTO $connection, string $sku): ?string
    {
        $resp = $this->http('GET', '/products', $connection,
            fn ($r) => $r->withToken($connection->accessToken)->withQueryParameters(['sku' => $sku]),
        );

        if (! $resp->successful()) {
            return null;
        }

        $items = (array) ($resp->json('data') ?? []);
        foreach ($items as $item) {
            // Salla returns arrays of products; we take the first one
            // that matches the SKU exactly (case-insensitive).
            if (strcasecmp((string) ($item['sku'] ?? ''), $sku) === 0) {
                return isset($item['id']) ? (string) $item['id'] : null;
            }
        }
        return null;
    }
}
