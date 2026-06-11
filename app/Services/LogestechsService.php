<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound integration to the Logestechs platform.
 *
 * Auth model (UNUSUAL):
 *   - Every request includes a `company-id` HTTP header identifying the
 *     receiving Logestechs company.
 *   - Create & cancel calls ALSO carry the customer's `email` + `password`
 *     INSIDE the JSON body (Logestechs validates the customer account on
 *     each call rather than using a session token).
 *
 * Reference: temp/LogesTechs_postman_collection.json
 *
 * All methods return an array — either the decoded JSON body, or an
 * `_error` envelope on transport / non-2xx failures.
 */
class LogestechsService
{
    private string $baseUrl;
    private int $timeout;
    private string $integrationSource;

    public function __construct()
    {
        $this->baseUrl           = rtrim((string) config('services.logestechs.base_url'), '/');
        $this->timeout           = (int) (config('services.logestechs.timeout') ?: 30);
        $this->integrationSource = (string) (config('services.logestechs.integration_source') ?: 'API');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    // -------------------------------------------------------------------------
    // Shipments
    // -------------------------------------------------------------------------

    /**
     * Create a shipment in Logestechs. `$body` is the full request body —
     * `{email, password, pkg, destinationAddress, pkgUnitType}`. Use
     * ::buildCreatePayload() to construct it from a Parcel.
     */
    public function createShipment(array $body, string $companyId): array
    {
        return $this->post('/ship/request/by-email', $body, $companyId, 'createShipment');
    }

    public function trackShipment(string $companyId, ?string $barcode = null, ?string $id = null): array
    {
        $query = array_filter(['barcode' => $barcode, 'id' => $id], fn ($v) => $v !== null && $v !== '');
        return $this->get('/guests/' . rawurlencode($companyId) . '/packages/tracking', $query, $companyId, 'trackShipment');
    }

    public function getPackageStatus(string $companyId, ?string $barcode = null, ?string $id = null): array
    {
        $query = array_filter(['barcode' => $barcode, 'id' => $id], fn ($v) => $v !== null && $v !== '');
        return $this->get('/guests/packages/status', $query, $companyId, 'getPackageStatus');
    }

    /**
     * Cancel a shipment. Logestechs requires the customer's email + password
     * in the body for cancellation, same as create.
     */
    public function cancelShipment(string $companyId, string $shipmentId, string $email, string $password): array
    {
        $path = '/guests/' . rawurlencode($companyId) . '/packages/' . rawurlencode($shipmentId) . '/cancel';
        return $this->request('PUT', $path, ['email' => $email, 'password' => $password], $companyId, 'cancelShipment');
    }

    public function printAwbs(string $companyId, array $shipmentIds): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Logestechs is not configured.');
        }
        try {
            $resp = Http::withHeaders($this->headers($companyId))
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/guests/' . rawurlencode($companyId) . '/packages/pdf', ['ids' => array_values($shipmentIds)]);
            if (! $resp->successful()) {
                return $this->errorEnvelope('Logestechs HTTP ' . $resp->status(), $resp->status(), $resp->json());
            }
            return [
                'content_type' => $resp->header('Content-Type'),
                'body_base64'  => base64_encode($resp->body()),
            ];
        } catch (\Throwable $e) {
            Log::warning('Logestechs transport error', ['op' => 'printAwbs', 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Lookups
    // -------------------------------------------------------------------------

    /**
     * Logestechs village lookup. Returns the full `{data: [...], ...}` wrapper.
     */
    public function getVillages(string $companyId, string $search = ''): array
    {
        return $this->get('/addresses/villages', ['search' => $search], $companyId, 'getVillages');
    }

    public function getCompanyIdByDomain(string $domain): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Logestechs is not configured.');
        }
        try {
            $resp = Http::acceptJson()->timeout($this->timeout)
                ->get($this->baseUrl . '/guests/companies/info-by-domain', ['domain' => $domain]);
            return $this->unwrap($resp, 'getCompanyIdByDomain');
        } catch (\Throwable $e) {
            Log::warning('Logestechs transport error', ['op' => 'getCompanyIdByDomain', 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    public function customerLogin(string $email, string $password, int $companyId): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Logestechs is not configured.');
        }
        try {
            $resp = Http::acceptJson()->asJson()->timeout($this->timeout)
                ->post($this->baseUrl . '/auth/customer/login', [
                    'email'     => $email,
                    'password'  => $password,
                    'companyId' => $companyId,
                    'device'    => ['operatingSystem' => 'API', 'UUID' => 'rushly-' . (settings()->id ?? 0)],
                ]);
            return $this->unwrap($resp, 'customerLogin');
        } catch (\Throwable $e) {
            Log::warning('Logestechs transport error', ['op' => 'customerLogin', 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Parcel -> Logestechs createShipment body
    // -------------------------------------------------------------------------

    /**
     * Build the full `/ship/request/by-email` body from a local Parcel.
     *
     * @param mixed       $parcel
     * @param string      $email        customer account email on the target Logestechs company
     * @param string      $password     customer account password
     * @param string|null $villageName  Logestechs village name (resolved separately via getVillages).
     *                                  If null, falls back to the parcel's area/city en_name.
     * @param array|null  $village      Optional full village object from ::resolveVillage() —
     *                                  if supplied, its cityId/regionId/etc. populate
     *                                  destinationAddress; takes precedence over $villageName.
     */
    public function buildCreatePayload($parcel, string $email, string $password, ?string $villageName = null, ?array $village = null): array
    {
        $cod  = (int) round((float) ($parcel->cash_collection ?? 0));
        $qty  = (int) ($parcel->number_of_boxes ?? 1);
        $desc = (string) ($parcel->package_description ?? ($parcel->note ?? 'Parcel'));

        $itemName = mb_substr($desc, 0, 200);

        $villageStr = $village['englishName'] ?? $village['arabicName'] ?? $villageName ?? (string) (
            optional($parcel->area)->en_name
                ?: optional($parcel->area)->name
                ?: optional($parcel->city)->en_name
                ?: optional($parcel->city)->name
                ?: ''
        );
        $cityId   = $village['cityId']   ?? null;
        $regionId = $village['regionId'] ?? null;

        return [
            'email'    => $email,
            'password' => $password,
            'pkg' => [
                'receiverName'              => (string) ($parcel->customer_name ?? ''),
                'cod'                       => $cod,
                'notes'                     => (string) ($parcel->note ?? ''),
                'supplierInvoice'           => (string) ($parcel->invoice_no ?? $parcel->id),
                'packageItemsToDeliverList' => [[
                    'name' => $itemName,
                    'cod'  => $cod,
                ]],
                'senderName'         => (string) ($parcel->merchant->business_name ?? ''),
                'businessSenderName' => (string) ($parcel->merchant->business_name ?? ''),
                'senderPhone'        => (string) ($parcel->pickup_phone ?: ($parcel->merchant->user->mobile ?? '')),
                'receiverPhone'      => (string) ($parcel->customer_phone ?? ''),
                'receiverPhone2'     => '',
                'serviceType'        => 'STANDARD',
                'shipmentType'       => $cod > 0 ? 'COD' : 'NORMAL',
                'quantity'           => max(1, $qty),
                'description'        => 'Tracking: ' . ($parcel->tracking_id ?? $parcel->id),
                'integrationSource'  => $this->integrationSource,
            ],
            'destinationAddress' => array_filter([
                'village'      => $villageStr,
                'cityId'       => $cityId,
                'regionId'     => $regionId,
                'addressLine1' => mb_substr((string) ($parcel->customer_address ?? ''), 0, 250),
                'addressLine2' => '',
            ], fn ($v) => $v !== null),
            'pkgUnitType' => 'METRIC',
        ];
    }

    /**
     * Convenience: resolve a free-text query to a Logestechs village id+name.
     * Returns the first match's `englishName`/`arabicName` so the caller can
     * use whichever Logestechs expects in `destinationAddress.village`.
     */
    public function resolveVillage(string $companyId, string $query): ?array
    {
        $resp = $this->getVillages($companyId, $query);
        if (! empty($resp['_error'])) return null;
        $first = $resp['data'][0] ?? null;
        if (! is_array($first)) return null;
        return [
            'id'          => $first['id']          ?? null,
            'englishName' => $first['englishName'] ?? ($first['name'] ?? null),
            'arabicName'  => $first['arabicName']  ?? null,
            'cityId'      => $first['cityId']      ?? null,
            'cityName'    => $first['cityName']    ?? null,
            'regionId'    => $first['regionId']    ?? null,
            'regionName'  => $first['regionName']  ?? null,
            'prefix'      => $first['prefix']      ?? null,
        ];
    }

    // -------------------------------------------------------------------------
    // HTTP plumbing
    // -------------------------------------------------------------------------

    private function get(string $endpoint, array $query, string $companyId, string $op): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Logestechs is not configured.');
        }
        try {
            $resp = Http::withHeaders($this->headers($companyId))
                ->acceptJson()->timeout($this->timeout)
                ->get($this->baseUrl . $endpoint, $query);
            return $this->unwrap($resp, $op);
        } catch (\Throwable $e) {
            Log::warning('Logestechs transport error', ['op' => $op, 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    private function post(string $endpoint, array $body, string $companyId, string $op): array
    {
        return $this->request('POST', $endpoint, $body, $companyId, $op);
    }

    private function request(string $method, string $endpoint, array $body, string $companyId, string $op): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Logestechs is not configured.');
        }
        try {
            // JSON_FORCE_OBJECT guards against PHP's `[]` -> JSON `[]` quirk
            // when an inner subobject happens to be empty; Logestechs's Jackson
            // serializer rejects START_ARRAY where it expects an object.
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $resp = Http::withHeaders($this->headers($companyId) + ['Content-Type' => 'application/json'])
                ->acceptJson()->timeout($this->timeout)
                ->withBody($json, 'application/json')
                ->send($method, $this->baseUrl . $endpoint);
            return $this->unwrap($resp, $op);
        } catch (\Throwable $e) {
            Log::warning('Logestechs transport error', ['op' => $op, 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    private function unwrap(Response $resp, string $op): array
    {
        $status = $resp->status();
        $body   = $resp->json();
        if ($resp->successful() && is_array($body)) {
            return $body;
        }
        Log::warning('Logestechs non-success response', ['op' => $op, 'status' => $status, 'body' => $body]);
        return $this->errorEnvelope('Logestechs HTTP ' . $status, $status, $body);
    }

    private function headers(string $companyId): array
    {
        return [
            'company-id' => $companyId,
            'Accept'     => 'application/json',
        ];
    }

    private function errorEnvelope(string $message, int $status = 0, $body = null): array
    {
        return [
            '_error'  => true,
            'message' => $message,
            'status'  => $status,
            'body'    => $body,
        ];
    }
}
