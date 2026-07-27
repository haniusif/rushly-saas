<?php

namespace App\Salla\Services;

use App\Salla\Models\Merchant;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ApiClient
{
    /**
     * Salla's OAuth token endpoint. Kept here (not in config/salla.php)
     * because it's a platform constant, not a per-tenant setting.
     */
    private const TOKEN_URL = 'https://accounts.salla.sa/oauth2/token';

    public function __construct(private Merchant $merchant)
    {
        if (! $merchant->access_token) {
            throw new RuntimeException("Salla merchant {$merchant->id} has no access token");
        }
    }

    /**
     * PUT /admin/v2/shipments/{id} is Salla's single write endpoint for a
     * shipment — status changes, AWB return, label URL, cost, etc. all go
     * through the same body. `shipment_number` and `status` are required.
     *
     * Docs: https://docs.salla.dev/5394233e0
     */
    public function updateShipment(string $sallaShipmentId, array $fields): array
    {
        $response = $this->request('put', "/shipments/{$sallaShipmentId}", $fields);
        $this->ensureSuccess($response, 'shipment.update');
        return $response->json();
    }

    /**
     * Convenience wrapper for pushing just a status change. Requires
     * shipment_number because Salla's schema demands it — pass it from the
     * local salla_shipments row captured in ShipmentCreatingHandler.
     */
    public function updateShipmentStatus(
        string $sallaShipmentId,
        string $shipmentNumber,
        string $status,
        ?string $statusNote = null,
    ): array {
        return $this->updateShipment($sallaShipmentId, array_filter([
            'shipment_number' => $shipmentNumber,
            'status'          => $status,
            'status_note'     => $statusNote,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Return the AWB number + label URL back to Salla for an in-flight
     * shipment. Salla now requires a `status` alongside the tracking
     * fields; we send `created` (label ready) per the fulfillment cycle
     * docs. Use updateShipment() directly if a different status is needed.
     */
    public function returnWaybill(
        string $sallaShipmentId,
        string $shipmentNumber,
        string $trackingNumber,
        string $labelUrl,
        ?string $trackingLink = null,
    ): array {
        return $this->updateShipment($sallaShipmentId, array_filter([
            'shipment_number' => $shipmentNumber,
            'status'          => 'created',
            'tracking_number' => $trackingNumber,
            'tracking_link'   => $trackingLink,
            'pdf_label'       => $labelUrl,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function getOrder(int $orderId): array
    {
        $response = $this->request('get', "/orders/{$orderId}");
        $this->ensureSuccess($response, 'order.fetch');
        return $response->json();
    }

    private function request(string $method, string $path, array $payload = []): Response
    {
        $this->ensureFreshToken();

        $base = rtrim(config('salla.api_base'), '/');
        return Http::withToken($this->merchant->access_token)
            ->acceptJson()
            ->{$method}($base.$path, $payload);
    }

    /**
     * Refresh the access token if it has expired or expires within 60s.
     *
     * Salla access tokens live 14 days and refresh tokens are single-use,
     * so the new pair must be persisted atomically BEFORE the next API call
     * — otherwise a crash mid-request loses the refresh token permanently
     * and the tenant has to reinstall the app.
     */
    private function ensureFreshToken(): void
    {
        if (! $this->merchant->tokenExpiresSoon()) {
            return;
        }
        if (! $this->merchant->refresh_token) {
            // Nothing to refresh with — Easy Mode tenants get a fresh pair
            // via the app.store.authorize webhook. Let the request go and
            // 401 loudly if the token is truly dead.
            return;
        }

        $creds = $this->credentialsForMerchant();
        if (! $creds) {
            Log::error('salla.token.refresh.no_credentials', [
                'merchant' => $this->merchant->id,
            ]);
            return;
        }

        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->merchant->refresh_token,
                'client_id'     => $creds['client_id'],
                'client_secret' => $creds['client_secret'],
            ]);
        } catch (Throwable $e) {
            Log::error('salla.token.refresh.exception', [
                'merchant' => $this->merchant->id,
                'message'  => $e->getMessage(),
            ]);
            return;
        }

        if (! $response->successful()) {
            Log::error('salla.token.refresh.failed', [
                'merchant' => $this->merchant->id,
                'status'   => $response->status(),
                'body'     => $response->json(),
            ]);
            return;
        }

        $data = $response->json();
        $access  = $data['access_token']  ?? null;
        $refresh = $data['refresh_token'] ?? null;
        if (! $access) {
            Log::error('salla.token.refresh.malformed', [
                'merchant' => $this->merchant->id,
                'body'     => $data,
            ]);
            return;
        }

        $this->merchant->forceFill([
            'access_token'     => $access,
            // Salla rotates refresh tokens on refresh. If the response omits
            // one (some SDKs do), keep the current — but the docs say a new
            // one is always issued, so this is defensive.
            'refresh_token'    => $refresh ?: $this->merchant->refresh_token,
            'token_expires_at' => isset($data['expires_in'])
                ? Carbon::now()->addSeconds((int) $data['expires_in'])
                : null,
        ])->save();

        Log::info('salla.token.refresh.ok', [
            'merchant'   => $this->merchant->id,
            'expires_at' => optional($this->merchant->token_expires_at)->toIso8601String(),
        ]);
    }

    /**
     * Resolve the tenant's Salla OAuth client credentials for this merchant.
     *
     * Salla creds are per-tenant (integration_settings.meta, keyed by
     * company_id). Queue jobs run without HTTP tenant scope, so we walk
     * salla_merchants → merchants.company_id → integration_settings directly
     * instead of relying on the sallaCreds() helper (which needs settings()).
     */
    private function credentialsForMerchant(): ?array
    {
        $companyId = DB::table('merchants')
            ->where('id', $this->merchant->rushly_merchant_id)
            ->value('company_id');

        if (! $companyId) return null;

        $row = DB::table('integration_settings')
            ->where('company_id', $companyId)
            ->where('platform', 'salla')
            ->first();

        if (! $row || ! $row->meta) return null;

        $meta = json_decode($row->meta, true) ?: [];
        $id     = (string) ($meta['oauth_client_id']     ?? '');
        $secret = (string) ($meta['oauth_client_secret'] ?? '');
        if ($id === '' || $secret === '') return null;

        return ['client_id' => $id, 'client_secret' => $secret];
    }

    private function ensureSuccess(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        Log::error("salla.api.{$context} failed", [
            'merchant' => $this->merchant->salla_merchant_id,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        throw new RuntimeException("Salla API {$context} failed: ".$response->status());
    }
}
