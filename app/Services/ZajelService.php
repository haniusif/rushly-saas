<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper for the Zajel Merchant API (v1.8).
 *
 * Auth: every request carries an X-AUTH-API-KEY header.
 *
 * All methods return the decoded JSON body as an array, or an array shaped
 * like ['_error' => true, 'status' => int, 'message' => string, 'body' => mixed]
 * when the request fails. Callers should branch on the presence of '_error'
 * rather than blindly indexing into the response.
 *
 * Reference docs: 3PL.md and the Zajel Merchant API PDF v1.8.
 */
class ZajelService
{
    private string $baseUrl;
    private string $apiKey;
    private string $customerCode;
    private string $serviceTypeId;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl       = rtrim((string) config('services.zajel.base_url'), '/');
        $this->apiKey        = (string) config('services.zajel.key');
        $this->customerCode  = (string) config('services.zajel.customer_code');
        $this->serviceTypeId = (string) (config('services.zajel.service_type_id') ?: 'DDN');
        $this->timeout       = (int) (config('services.zajel.timeout') ?: 30);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->customerCode !== '' && $this->baseUrl !== '';
    }

    public function customerCode(): string
    {
        return $this->customerCode;
    }

    public function serviceTypeId(): string
    {
        return $this->serviceTypeId;
    }

    // -------------------------------------------------------------------------
    // Shipments
    // -------------------------------------------------------------------------

    public function createShipment(array $data): array
    {
        return $this->post('/api/Merchant/CreateShipment', $data);
    }

    public function createInternationalShipment(array $data): array
    {
        return $this->post('/api/Merchant/CreateInternationalShipment', $data);
    }

    public function trackShipment(string $referenceNumber): array
    {
        return $this->get('/api/Merchant/TrackShipment', ['reference_number' => $referenceNumber]);
    }

    public function cancelShipment(string $referenceNumber): array
    {
        return $this->post('/api/Merchant/CancelShipment', ['reference_number' => $referenceNumber]);
    }

    /**
     * Returns the URL to the AWB label PDF. Zajel responds with a 200 either
     * containing a URL string or redirecting to the file; we return the raw
     * Response body so the caller can decide how to surface it (download,
     * iframe, etc.).
     */
    public function getShipmentLabel(string $referenceNumber): array
    {
        return $this->get('/api/Merchant/GetShipmentLabel', ['reference_number' => $referenceNumber]);
    }

    // -------------------------------------------------------------------------
    // Lookups (cached — these change rarely)
    // -------------------------------------------------------------------------

    public function getCities(?string $countryCode = null): array
    {
        $cacheKey = 'zajel:cities:' . ($countryCode ?: 'UAE');
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($countryCode) {
            $query = $countryCode !== null ? ['country_code' => $countryCode] : [];
            return $this->get('/api/Merchant/GetCities', $query);
        });
    }

    public function getAreas(?string $cityCode = null): array
    {
        $cacheKey = 'zajel:areas:' . ($cityCode ?: 'ALL');
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($cityCode) {
            $query = $cityCode !== null ? ['city_code' => $cityCode] : [];
            return $this->get('/api/Merchant/GetAreas', $query);
        });
    }

    /**
     * Build a Zajel CreateShipment payload from a local Parcel.
     *
     * NOTE: city/area expect Zajel codes (e.g. DXB, AUH) from /GetCities and
     * /GetAreas. We send the local `en_name` uppercased — set up your seed
     * data to match the Zajel codes, or add a translation table later.
     */
    public function buildShipmentPayload($parcel): array
    {
        $originName  = $parcel->merchant->business_name ?? settings()->company_name ?? 'Sender';
        $originPhone = $parcel->pickup_phone ?: ($parcel->merchant->user->mobile ?? '');
        $originCity  = strtoupper((string) (optional($parcel->hub)->en_name ?? optional($parcel->city)->en_name ?? 'DXB'));
        $destCity    = strtoupper((string) (optional($parcel->city)->en_name ?? 'DXB'));
        $destArea    = strtoupper((string) (optional($parcel->area)->en_name ?? ''));

        return [
            'customer_reference_number' => (string) ($parcel->tracking_id ?? $parcel->id),
            'weight_in_kg'              => max(0.05, round((float) ($parcel->weight ?? 0.5), 2)),
            'customer_code'             => $this->customerCode,
            'service_type_id'           => $this->serviceTypeId,
            'product_type'              => 'NON-DOCUMENT',
            'description'               => (string) ($parcel->package_description ?? $parcel->note ?? 'Parcel'),
            'length_in_cm'              => 0,
            'width_in_cm'               => 0,
            'height_in_cm'              => 0,
            'num_of_pieces'             => (int) ($parcel->number_of_boxes ?? 1),
            'cod_amount'                => (string) round((float) ($parcel->cash_collection ?? 0), 2),
            'origin' => [
                'name'           => (string) $originName,
                'phone'          => (string) $originPhone,
                'company_name'   => (string) ($parcel->merchant->business_name ?? ''),
                'address_line_1' => (string) ($parcel->pickup_address ?? ''),
                'address_line_2' => '',
                'area'           => '',
                'city'           => $originCity,
                'country'        => 'UAE',
                'email'          => (string) ($parcel->merchant->user->email ?? ''),
                'latitude'       => '',
                'longitude'      => '',
            ],
            'destination' => [
                'name'           => (string) ($parcel->customer_name ?? ''),
                'phone'          => (string) ($parcel->customer_phone ?? ''),
                'company_name'   => '',
                'address_line_1' => (string) ($parcel->customer_address ?? ''),
                'address_line_2' => '',
                'area'           => $destArea,
                'city'           => $destCity,
                'country'        => 'UAE',
                'email'          => '',
                'latitude'       => '',
                'longitude'      => '',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // HTTP plumbing
    // -------------------------------------------------------------------------

    private function get(string $endpoint, array $query = []): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope(0, 'Zajel is not configured.');
        }
        try {
            $resp = Http::withHeaders($this->headers())
                ->timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl . $endpoint, $query);
            return $this->unwrap($resp, $endpoint);
        } catch (\Throwable $e) {
            Log::warning('Zajel GET transport error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return $this->errorEnvelope(0, 'Transport error: ' . $e->getMessage());
        }
    }

    private function post(string $endpoint, array $body): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope(0, 'Zajel is not configured.');
        }
        try {
            $resp = Http::withHeaders($this->headers())
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->baseUrl . $endpoint, $body);
            return $this->unwrap($resp, $endpoint);
        } catch (\Throwable $e) {
            Log::warning('Zajel POST transport error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return $this->errorEnvelope(0, 'Transport error: ' . $e->getMessage());
        }
    }

    private function unwrap(Response $resp, string $endpoint): array
    {
        $status = $resp->status();
        $body   = $resp->json();

        // 2xx with JSON object/array → return as-is.
        if ($resp->successful() && is_array($body)) {
            return $body;
        }

        // Non-2xx OR non-JSON → wrap so callers can branch on '_error'.
        $msg = is_array($body) && isset($body['title'])
            ? (string) $body['title']
            : 'Zajel responded with HTTP ' . $status;

        Log::warning('Zajel non-success response', [
            'endpoint' => $endpoint,
            'status'   => $status,
            'body'     => $body,
        ]);

        return $this->errorEnvelope($status, $msg, $body);
    }

    private function headers(): array
    {
        return [
            'X-AUTH-API-KEY' => $this->apiKey,
            'Accept'         => '*/*',
        ];
    }

    private function errorEnvelope(int $status, string $message, $body = null): array
    {
        return [
            '_error'  => true,
            'status'  => $status,
            'message' => $message,
            'body'    => $body,
        ];
    }
}
