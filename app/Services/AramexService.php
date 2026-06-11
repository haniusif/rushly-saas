<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

/**
 * Wrapper for the Aramex Shipping Services API v1.0 (SOAP).
 *
 * Auth: every request includes a ClientInfo block with
 * username/password/version/account credentials.
 *
 * All methods return either a stdClass / array as decoded from the SOAP
 * response, or an `_error` envelope on transport/SOAP fault:
 *   ['_error' => true, 'message' => string, 'fault_code' => string|null, 'detail' => mixed]
 *
 * Reference docs: 3PL.md and Aramex's Developer Solution Center.
 */
class AramexService
{
    private array $clientInfo;
    private string $wsdl;
    private int $timeout;
    private string $productGroup;
    private string $productType;
    private string $paymentType;

    private ?SoapClient $client = null;

    public function __construct()
    {
        $this->wsdl    = (string) config('services.aramex.wsdl');
        $this->timeout = (int) (config('services.aramex.timeout') ?: 60);

        $this->productGroup = (string) config('services.aramex.product_group');
        $this->productType  = (string) config('services.aramex.product_type');
        $this->paymentType  = (string) config('services.aramex.payment_type');

        $this->clientInfo = [
            'UserName'           => (string) config('services.aramex.username'),
            'Password'           => (string) config('services.aramex.password'),
            'Version'            => (string) config('services.aramex.version'),
            'AccountNumber'      => (string) config('services.aramex.account_number'),
            'AccountPin'         => (string) config('services.aramex.account_pin'),
            'AccountEntity'      => (string) config('services.aramex.account_entity'),
            'AccountCountryCode' => (string) config('services.aramex.account_country_code'),
            'Source'             => 0,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->clientInfo['UserName'] !== ''
            && $this->clientInfo['Password'] !== ''
            && $this->clientInfo['AccountNumber'] !== ''
            && $this->wsdl !== '';
    }

    public function productGroup(): string { return $this->productGroup; }
    public function productType(): string  { return $this->productType; }
    public function paymentType(): string  { return $this->paymentType; }

    // -------------------------------------------------------------------------
    // Shipments
    // -------------------------------------------------------------------------

    public function createShipments(array $shipments): array
    {
        return $this->call('CreateShipments', [
            'Shipments'   => ['Shipment' => $shipments],
            'ClientInfo'  => $this->clientInfo,
            'Transaction' => $this->newTransaction(),
            'LabelInfo'   => ['ReportID' => 9201, 'ReportType' => 'URL'],
        ]);
    }

    public function trackShipments(array $awbNumbers, bool $lastUpdateOnly = false): array
    {
        return $this->call('TrackShipments', [
            'ClientInfo'                 => $this->clientInfo,
            'Transaction'                => $this->newTransaction(),
            'Shipments'                  => ['string' => array_values($awbNumbers)],
            'GetLastTrackingUpdateOnly'  => $lastUpdateOnly,
        ]);
    }

    public function printLabel(string $awb): array
    {
        return $this->call('PrintLabel', [
            'ClientInfo'    => $this->clientInfo,
            'Transaction'   => $this->newTransaction(),
            'ShipmentNumber'=> $awb,
            'LabelInfo'     => ['ReportID' => 9201, 'ReportType' => 'URL'],
        ]);
    }

    /**
     * Aramex doesn't expose a true CancelShipment in the public Shipping API —
     * cancellation is handled by the pickup-cancel flow (CancelPickup) before
     * pickup, and post-pickup cancellations must go through the account manager.
     * This method is here so the controller can call it uniformly; it returns
     * an error envelope when not supported by the current WSDL.
     */
    public function cancelShipment(string $awb, string $comments = ''): array
    {
        return $this->errorEnvelope(
            'Aramex does not support post-create cancellation via the API. '
            . 'Cancel the linked pickup with cancelPickup() before pickup, or contact Aramex.',
            null,
            ['awb' => $awb, 'comments' => $comments]
        );
    }

    // -------------------------------------------------------------------------
    // Pickups
    // -------------------------------------------------------------------------

    public function createPickup(array $pickup): array
    {
        return $this->call('CreatePickup', [
            'ClientInfo'  => $this->clientInfo,
            'Transaction' => $this->newTransaction(),
            'Pickup'      => $pickup,
            'LabelInfo'   => ['ReportID' => 9201, 'ReportType' => 'URL'],
        ]);
    }

    public function cancelPickup(string $pickupGuid, string $comments = ''): array
    {
        return $this->call('CancelPickup', [
            'ClientInfo'  => $this->clientInfo,
            'Transaction' => $this->newTransaction(),
            'PickupGUID'  => $pickupGuid,
            'Comments'    => $comments,
        ]);
    }

    // -------------------------------------------------------------------------
    // Lookups (cached — these change rarely)
    // -------------------------------------------------------------------------

    public function fetchCountries(): array
    {
        return Cache::remember('aramex:countries', now()->addHours(24), function () {
            return $this->call('FetchCountries', [
                'ClientInfo'  => $this->clientInfo,
                'Transaction' => $this->newTransaction(),
            ]);
        });
    }

    public function fetchCities(string $countryCode = 'AE', string $stateCode = ''): array
    {
        $key = 'aramex:cities:' . $countryCode . ':' . ($stateCode ?: 'ALL');
        return Cache::remember($key, now()->addHours(24), function () use ($countryCode, $stateCode) {
            return $this->call('FetchCities', [
                'ClientInfo'  => $this->clientInfo,
                'Transaction' => $this->newTransaction(),
                'CountryCode' => $countryCode,
                'State'       => $stateCode,
                'NameStartsWith' => '',
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Payload builder — Parcel → Aramex Shipment array
    // -------------------------------------------------------------------------

    /**
     * Build an Aramex Shipment payload from a local Parcel.
     *
     * NOTE: City/Country expect Aramex codes — for the UAE use 'AE' as the
     * country code; cities are uppercased en_name (DUBAI, ABU DHABI…). Seed
     * your local cities table to match Aramex's expected values, or add a
     * translation column later.
     */
    public function buildShipmentPayload($parcel): array
    {
        $currency = (string) (settings()->currency ?? 'AED');
        $isCod    = ((float) ($parcel->cash_collection ?? 0)) > 0;

        $shipperName  = (string) ($parcel->merchant->business_name ?? settings()->company_name ?? 'Sender');
        $shipperPhone = (string) ($parcel->pickup_phone ?: ($parcel->merchant->user->mobile ?? ''));

        $services = $isCod ? 'CODS' : '';

        return [
            'Reference1'    => (string) ($parcel->tracking_id ?? $parcel->id),
            'Reference2'    => '',
            'Reference3'    => '',
            'Shipper' => [
                'Reference1'    => 'shipper-' . $parcel->id,
                'AccountNumber' => $this->clientInfo['AccountNumber'],
                'PartyAddress' => [
                    'Line1'                  => (string) ($parcel->pickup_address ?? ''),
                    'Line2'                  => '',
                    'Line3'                  => '',
                    'City'                   => strtoupper((string) (optional($parcel->hub)->en_name ?? 'DUBAI')),
                    'StateOrProvinceCode'    => '',
                    'PostCode'               => '',
                    'CountryCode'            => $this->clientInfo['AccountCountryCode'],
                ],
                'Contact' => [
                    'PersonName'   => $shipperName,
                    'CompanyName'  => $shipperName,
                    'PhoneNumber1' => $shipperPhone,
                    'CellPhone'    => $shipperPhone,
                    'EmailAddress' => (string) ($parcel->merchant->user->email ?? ''),
                    'Type'         => '',
                ],
            ],
            'Consignee' => [
                'Reference1' => 'consignee-' . $parcel->id,
                'PartyAddress' => [
                    'Line1'                  => (string) ($parcel->customer_address ?? ''),
                    'Line2'                  => '',
                    'Line3'                  => '',
                    'City'                   => strtoupper((string) (optional($parcel->city)->en_name ?? 'DUBAI')),
                    'StateOrProvinceCode'    => '',
                    'PostCode'               => '',
                    'CountryCode'            => $this->clientInfo['AccountCountryCode'],
                ],
                'Contact' => [
                    'PersonName'   => (string) ($parcel->customer_name ?? ''),
                    'CompanyName'  => (string) ($parcel->customer_name ?? ''),
                    'PhoneNumber1' => (string) ($parcel->customer_phone ?? ''),
                    'CellPhone'    => (string) ($parcel->customer_phone ?? ''),
                    'EmailAddress' => '',
                    'Type'         => '',
                ],
            ],
            'ShippingDateTime'        => '/Date(' . (Carbon::now()->getTimestampMs()) . ')/',
            'DueDate'                 => '/Date(' . (Carbon::now()->addDays(3)->getTimestampMs()) . ')/',
            'Comments'                => (string) ($parcel->note ?? ''),
            'PickupLocation'          => 'Reception',
            'OperationsInstructions'  => '',
            'AccountingInstrcutions'  => '',
            'Details' => [
                'Dimensions' => [
                    'Length' => 10,
                    'Width'  => 10,
                    'Height' => 10,
                    'Unit'   => 'cm',
                ],
                'ActualWeight' => [
                    'Value' => max(0.5, round((float) ($parcel->weight ?? 0.5), 2)),
                    'Unit'  => 'kg',
                ],
                'ProductGroup'       => $this->productGroup,
                'ProductType'        => $this->productType,
                'PaymentType'        => $this->paymentType,
                'PaymentOptions'     => '',
                'Services'           => $services,
                'NumberOfPieces'     => (int) ($parcel->number_of_boxes ?? 1),
                'DescriptionOfGoods' => (string) ($parcel->package_description ?? 'General Goods'),
                'GoodsOriginCountry' => $this->clientInfo['AccountCountryCode'],
                'CashOnDeliveryAmount' => [
                    'Value'        => round((float) ($parcel->cash_collection ?? 0), 2),
                    'CurrencyCode' => $currency,
                ],
                'CustomsValueAmount' => [
                    'Value'        => round((float) ($parcel->selling_price ?? 0), 2),
                    'CurrencyCode' => $currency,
                ],
                'Items' => [],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // SOAP plumbing
    // -------------------------------------------------------------------------

    private function client(): SoapClient
    {
        if ($this->client !== null) {
            return $this->client;
        }
        return $this->client = new SoapClient($this->wsdl, [
            'trace'              => true,
            'exceptions'         => true,
            'connection_timeout' => $this->timeout,
            'cache_wsdl'         => WSDL_CACHE_BOTH,
            'features'           => SOAP_USE_XSI_ARRAY_TYPE,
        ]);
    }

    private function call(string $operation, array $params): array
    {
        if (! $this->isConfigured()) {
            return $this->errorEnvelope('Aramex is not configured.');
        }
        try {
            $result = $this->client()->$operation($params);
            return $this->toArray($result);
        } catch (SoapFault $f) {
            Log::warning('Aramex SOAP fault', [
                'op'      => $operation,
                'code'    => $f->faultcode ?? null,
                'message' => $f->getMessage(),
            ]);
            return $this->errorEnvelope($f->getMessage(), $f->faultcode ?? null);
        } catch (\Throwable $e) {
            Log::warning('Aramex transport error', [
                'op'    => $operation,
                'error' => $e->getMessage(),
            ]);
            return $this->errorEnvelope('Transport error: ' . $e->getMessage());
        }
    }

    private function newTransaction(): array
    {
        return [
            'Reference1' => (string) (Carbon::now()->getTimestamp()),
            'Reference2' => '',
            'Reference3' => '',
            'Reference4' => '',
            'Reference5' => '',
        ];
    }

    /** Convert nested stdClass into plain arrays for easier JSON handling. */
    private function toArray($value): array
    {
        return json_decode(json_encode($value), true) ?? [];
    }

    private function errorEnvelope(string $message, ?string $faultCode = null, $detail = null): array
    {
        return [
            '_error'     => true,
            'message'    => $message,
            'fault_code' => $faultCode,
            'detail'     => $detail,
        ];
    }
}
