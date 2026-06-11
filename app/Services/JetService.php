<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper for the J&T Express Indonesia (jet.co.id) shipping API.
 *
 * Auth:
 *   - Order / Cancel / Tariff: data_sign = base64(md5(data_param + secret_key))
 *   - Tracking: HTTP Basic ({username, track_password}) + JSON body
 *
 * Body is form-urlencoded for order/cancel/tariff, JSON for tracking.
 *
 * All methods return a decoded array. Transport / non-JSON failures come back
 * as ['_error' => true, 'message' => ..., 'status' => int, 'body' => mixed].
 *
 * Reference: docs at https://developer.jet.co.id/documentation
 * (URLs are revealed in the customer dashboard; see config/services.php `jet`.)
 */
class JetService
{
    private string $username;
    private string $apiKey;
    private string $secretKey;
    private string $eccompanyid;
    private string $trackPassword;
    private string $cusName;
    private string $orderUrl;
    private string $trackUrl;
    private string $tariffUrl;
    private string $cancelUrl;
    private string $defaultOriginCode;
    private int $serviceType;
    private string $expressType;
    private int $timeout;

    public function __construct()
    {
        $cfg = (array) config('services.jet');
        $this->username          = (string) ($cfg['username']            ?? '');
        $this->apiKey            = (string) ($cfg['api_key']             ?? '');
        $this->secretKey         = (string) ($cfg['secret_key']          ?? '');
        $this->eccompanyid       = (string) ($cfg['eccompanyid']         ?? '');
        $this->trackPassword     = (string) ($cfg['track_password']      ?? '');
        $this->cusName           = (string) ($cfg['cus_name']            ?? '');
        $this->orderUrl          = (string) ($cfg['order_url']           ?? '');
        $this->trackUrl          = (string) ($cfg['track_url']           ?? '');
        $this->tariffUrl         = (string) ($cfg['tariff_url']          ?? '');
        $this->cancelUrl         = (string) ($cfg['cancel_url']          ?? '');
        $this->defaultOriginCode = strtoupper((string) ($cfg['default_origin_code'] ?? 'JKT'));
        $this->serviceType       = (int) ($cfg['service_type']           ?? 1);
        $this->expressType       = (string) ($cfg['express_type']        ?? '1');
        $this->timeout           = (int) ($cfg['timeout']                ?? 30);
    }

    public function isConfigured(): bool
    {
        return $this->username !== ''
            && $this->apiKey !== ''
            && $this->secretKey !== ''
            && $this->orderUrl !== '';
    }

    public function eccompanyid(): string { return $this->eccompanyid; }

    // -------------------------------------------------------------------------
    // Orders
    // -------------------------------------------------------------------------

    /**
     * Create one J&T order. `$orderData` should be the full inner detail object
     * (no signature, no wrapping). Wrapping + signing happens here.
     */
    public function createOrder(array $orderData): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Jet is not configured.');
        }
        // J&T requires username + api_key inside the detail object too.
        $orderData = array_merge([
            'username' => $this->username,
            'api_key'  => $this->apiKey,
        ], $orderData);

        $dataParam = json_encode(['detail' => [$orderData]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $dataSign  = $this->sign($dataParam);

        return $this->postForm($this->orderUrl, [
            'data_param' => $dataParam,
            'data_sign'  => $dataSign,
        ], 'createOrder');
    }

    public function cancelOrder(string $orderid, string $remark = 'Cancelled by Rushly'): array
    {
        if (! $this->isConfigured() || $this->cancelUrl === '') {
            return $this->errorEnvelope('Jet cancel endpoint is not configured.');
        }
        $detail = [
            'username' => $this->username,
            'api_key'  => $this->apiKey,
            'orderid'  => $orderid,
            'remark'   => mb_substr($remark, 0, 30),
        ];
        $dataParam = json_encode(['detail' => [$detail]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $dataSign  = $this->sign($dataParam);

        return $this->postForm($this->cancelUrl, [
            'data_param' => $dataParam,
            'data_sign'  => $dataSign,
        ], 'cancelOrder');
    }

    /**
     * Track a single AWB. J&T's tracking endpoint takes one AWB per call and
     * uses HTTP Basic auth (username + trackPassword) with a JSON body.
     */
    public function trackOrder(string $awb): array
    {
        if (! $this->isConfigured() || $this->trackUrl === '') {
            return $this->errorEnvelope('Jet track endpoint is not configured.');
        }
        try {
            $resp = Http::withBasicAuth($this->username, $this->trackPassword)
                ->withBody(json_encode([
                    'awb'         => $awb,
                    'eccompanyid' => $this->eccompanyid,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'application/json')
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($this->trackUrl);
            $body = $resp->json();
            if ($resp->successful() && is_array($body)) {
                return $body;
            }
            Log::warning('Jet track non-success', ['awb' => $awb, 'status' => $resp->status(), 'body' => $body]);
            return $this->errorEnvelope('Jet track HTTP ' . $resp->status(), $resp->status(), $body);
        } catch (\Throwable $e) {
            Log::warning('Jet track transport error', ['awb' => $awb, 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    public function checkTariff(float $weightKg, string $sendSiteCode, string $destAreaCode): array
    {
        if (! $this->isConfigured() || $this->tariffUrl === '') {
            return $this->errorEnvelope('Jet tariff endpoint is not configured.');
        }
        $data = json_encode([
            'weight'       => (string) $weightKg,
            'sendSiteCode' => strtoupper($sendSiteCode),
            'destAreaCode' => strtoupper($destAreaCode),
            'cusName'      => $this->cusName,
            'productType'  => 'EZ',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sign = $this->sign($data);

        // Tariff endpoint uses fields `data` and `sign` (not data_param/data_sign).
        return $this->postForm($this->tariffUrl, [
            'data' => $data,
            'sign' => $sign,
        ], 'checkTariff');
    }

    // -------------------------------------------------------------------------
    // Parcel → Jet order payload
    // -------------------------------------------------------------------------

    /**
     * Build a Jet order body from a local Parcel.
     *
     * NOTE: J&T expects 3-letter origin/destination_code (e.g. JKT) and a
     * 10-char receiver_area district code (e.g. JKT001). These come from
     * J&T's area-mapping step. Until you have a mapping table, this falls
     * back to the configured default and uppercased en_name — which Jet WILL
     * reject in production. Wire `cities.jet_code` / `areas.jet_code` columns
     * (or a translation table) once Jet returns the mapping.
     */
    public function buildOrderPayload($parcel, ?string $orderIdPrefix = 'RUSHLY'): array
    {
        $orderId = $orderIdPrefix . '-' . ($parcel->tracking_id ?? $parcel->id);
        $orderId = mb_substr(str_replace(['/', '_'], '-', (string) $orderId), 0, 20);

        $shipperName  = mb_substr((string) ($parcel->merchant->business_name ?? settings()->company_name ?? 'Sender'), 0, 30);
        $shipperPhone = $this->normalizeIndonesianPhone((string) ($parcel->pickup_phone ?: ($parcel->merchant->user->mobile ?? '')));
        $shipperAddr  = mb_substr((string) ($parcel->pickup_address ?? ''), 0, 200);

        $receiverName  = mb_substr((string) ($parcel->customer_name ?? ''), 0, 30);
        $receiverPhone = $this->normalizeIndonesianPhone((string) ($parcel->customer_phone ?? ''));
        $receiverAddr  = mb_substr((string) ($parcel->customer_address ?? ''), 0, 200);

        $originCode      = strtoupper(mb_substr((string) (optional($parcel->hub)->jet_code ?? optional($parcel->city)->jet_code ?? $this->defaultOriginCode), 0, 3));
        $destinationCode = strtoupper(mb_substr((string) (optional($parcel->city)->jet_code ?? $this->defaultOriginCode), 0, 3));
        $receiverArea    = strtoupper(mb_substr((string) (optional($parcel->area)->jet_code ?? optional($parcel->area)->en_name ?? ''), 0, 10));

        $cod         = (int) round((float) ($parcel->cash_collection ?? 0));
        $goodsValue  = (int) round((float) ($parcel->selling_price ?? 0));
        $weight      = max(0.01, round((float) ($parcel->weight ?? 0.5), 2));
        $itemName    = mb_substr($this->stripSpecial((string) ($parcel->package_description ?? 'Parcel')), 0, 50);
        $goodsDesc   = mb_substr($this->stripSpecial((string) ($parcel->package_description ?? $parcel->note ?? 'Parcel')), 0, 40);

        $now      = Carbon::now('+07:00');
        $pickupStart = $now->copy()->format('Y-m-d H:i:s');
        $pickupEnd   = $now->copy()->addHours(8)->format('Y-m-d H:i:s');

        return [
            'orderid'         => $orderId,
            'shipper_name'    => $shipperName,
            'shipper_contact' => $shipperName,
            'shipper_phone'   => $shipperPhone,
            'shipper_addr'    => $shipperAddr,
            'origin_code'     => $originCode,
            'receiver_name'   => $receiverName,
            'receiver_phone'  => $receiverPhone,
            'receiver_addr'   => $receiverAddr,
            'receiver_zip'    => str_pad((string) ($parcel->customer_zip ?? '00000'), 5, '0', STR_PAD_LEFT),
            'destination_code'=> $destinationCode,
            'receiver_area'   => $receiverArea,
            'qty'             => (int) ($parcel->number_of_boxes ?? 1),
            'weight'          => $weight,
            'goodsdesc'       => $goodsDesc,
            'servicetype'     => $this->serviceType,
            'insurance'       => '',
            'orderdate'       => $now->format('Y-m-d H:i:s'),
            'item_name'       => $itemName,
            'cod'             => $cod > 0 ? $cod : '',
            'sendstarttime'   => $pickupStart,
            'sendendtime'     => $pickupEnd,
            'expresstype'     => $this->expressType,
            'goodsvalue'      => $goodsValue,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function sign(string $payload): string
    {
        return base64_encode(md5($payload . $this->secretKey));
    }

    private function postForm(string $url, array $form, string $op): array
    {
        try {
            $resp = Http::asForm()
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($url, $form);
            $body = $resp->json();
            if ($resp->successful() && is_array($body)) {
                return $body;
            }
            Log::warning('Jet non-success response', ['op' => $op, 'status' => $resp->status(), 'body' => $body]);
            return $this->errorEnvelope('Jet HTTP ' . $resp->status(), $resp->status(), $body);
        } catch (\Throwable $e) {
            Log::warning('Jet transport error', ['op' => $op, 'error' => $e->getMessage()]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    /** Convert phone numbers to J&T's `+62…` format. */
    private function normalizeIndonesianPhone(string $phone): string
    {
        $p = preg_replace('/[^\d+]/', '', $phone);
        if ($p === '' || $p === '+') return '';
        if (str_starts_with($p, '+62')) return mb_substr($p, 0, 15);
        if (str_starts_with($p, '62'))  return mb_substr('+' . $p, 0, 15);
        if (str_starts_with($p, '0'))   return mb_substr('+62' . mb_substr($p, 1), 0, 15);
        return mb_substr($p, 0, 15);
    }

    private function stripSpecial(string $s): string
    {
        // J&T rejects special characters in goodsdesc / item_name.
        return preg_replace('/[^\p{L}\p{N}\s\-\.,]/u', '', $s) ?: '';
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
