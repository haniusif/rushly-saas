<?php

namespace App\Oms\Normalization\Providers;

use App\Oms\DTOs\OrderAddressDTO;
use App\Oms\DTOs\OrderCustomerDTO;
use App\Oms\DTOs\OrderDTO;
use App\Oms\DTOs\OrderItemDTO;
use App\Oms\Normalization\AddressResolver;
use App\Oms\Normalization\OrderMapperInterface;
use App\Oms\Normalization\PayloadValidator;

/**
 * Salla → canonical OrderDTO.
 *
 * Salla webhook order payloads sit at either the root or under `data`
 * depending on the event. We normalize to `data` up front so the field
 * paths below stay consistent whether the caller passed the whole
 * webhook envelope or just the order object.
 *
 * Field paths are based on the existing app/Salla/Webhooks/Handlers/
 * OrderCreatedHandler mapping (the source of truth in production).
 */
class SallaOrderMapper implements OrderMapperInterface
{
    public function __construct(
        private readonly PayloadValidator $validator,
        private readonly AddressResolver  $addresses,
    ) {}

    public function code(): string
    {
        return 'salla';
    }

    public function map(array $payload, ?int $companyId = null): OrderDTO
    {
        // Accept both the full webhook envelope (event/merchant/data/...) and
        // a bare order payload — normalize to a single $data array.
        $data = isset($payload['data']) && is_array($payload['data'])
            ? $payload['data']
            : $payload;

        $this->validator->validate($data, [
            'id'                            => ['required'],
            'customer'                      => ['required', 'array'],
            'customer.mobile'               => ['nullable', 'string'],
            'shipping.address'              => ['nullable', 'array'],
            'total.amount'                  => ['nullable', 'numeric'],
            'total.currency'                => ['nullable', 'string'],
            'items'                         => ['nullable', 'array'],
        ], 'salla');

        $customer = $this->extractCustomer($data);
        $shipping = $this->extractShippingAddress($data, $companyId);
        $items    = $this->extractItems($data);
        $currency = $this->extractCurrency($data);
        $totals   = $this->extractTotals($data, $currency);

        return new OrderDTO(
            providerCode:      'salla',
            remoteOrderId:     (string) ($data['id'] ?? ''),
            remoteOrderNumber: isset($data['reference_id']) ? (string) $data['reference_id'] : null,
            providerStatus:    $this->extractStatus($data),
            paymentMethod:     $this->canonicalPaymentMethod($data),
            financialStatus:   $this->canonicalFinancialStatus($data),
            customer:          $customer,
            shippingAddress:   $shipping,
            billingAddress:    null,               // Salla doesn't distinguish shipping vs billing
            items:             $items,
            subtotal:          $totals['subtotal'],
            tax:               $totals['tax'],
            shippingFee:       $totals['shipping_fee'],
            discount:          $totals['discount'],
            total:             $totals['total'],
            codAmount:         $totals['cod_amount'],
            currency:          $currency,
            note:              $this->extractNote($data),
            occurredAt:        $this->extractDate($data),
            extra:             [
                'salla_urls'      => $data['urls']   ?? null,
                'salla_channel'   => $data['channel'] ?? null,
                'salla_tags'      => $data['tags']   ?? null,
            ],
        );
    }

    // ---------------------------------------------------------------

    private function extractCustomer(array $data): OrderCustomerDTO
    {
        $c = (array) ($data['customer'] ?? []);
        $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
        return new OrderCustomerDTO(
            remoteId: isset($c['id']) ? (string) $c['id'] : null,
            name:     $name !== '' ? $name : null,
            email:    $c['email']  ?? null,
            phone:    $c['mobile'] ?? ($c['phone'] ?? null),
        );
    }

    private function extractShippingAddress(array $data, ?int $companyId): OrderAddressDTO
    {
        $addr    = (array) ($data['shipping']['address'] ?? []);
        $receiver = (array) ($data['shipping']['receiver'] ?? []);
        $customer = (array) ($data['customer'] ?? []);

        $name = trim(($receiver['name'] ?? '')) ?: trim(
            ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')
        );

        $cityName = $addr['city']    ?? null;
        $areaName = $addr['district'] ?? ($addr['area'] ?? null);

        $dto = new OrderAddressDTO(
            name:            $name !== '' ? $name : null,
            phone:           $receiver['phone']   ?? ($customer['mobile'] ?? null),
            line1:           $addr['street']      ?? ($addr['address'] ?? null),
            line2:           $addr['description'] ?? null,
            cityName:        $cityName,
            areaName:        $areaName,
            region:          $addr['region']      ?? ($addr['state'] ?? null),
            country:         $addr['country']     ?? null,
            postcode:        $addr['postal_code'] ?? ($addr['zip'] ?? null),
            resolvedCityId:  null,
            resolvedAreaId:  null,
            extra:           [
                'country_code' => $addr['country_code'] ?? null,
                'latitude'     => $addr['latitude']     ?? null,
                'longitude'    => $addr['longitude']    ?? null,
            ],
        );

        // Best-effort resolution against tenant's cities/areas. Never
        // throws — null resolved ids just mean no confident match yet.
        $resolved = $this->addresses->resolve($cityName, $areaName, $companyId);
        return $dto->withResolved($resolved['city_id'], $resolved['area_id']);
    }

    /** @return OrderItemDTO[] */
    private function extractItems(array $data): array
    {
        $rawItems = (array) ($data['items'] ?? []);
        $currency = $this->extractCurrency($data);
        $out = [];
        foreach ($rawItems as $i) {
            $qty       = (int) ($i['quantity'] ?? 1);
            $unitPrice = (float) (
                data_get($i, 'amounts.price_without_tax.amount')
                ?? data_get($i, 'amounts.price.amount')
                ?? $i['price']
                ?? 0
            );
            $totalPrice = (float) (
                data_get($i, 'amounts.total.amount')
                ?? ($unitPrice * max(1, $qty))
            );
            $itemCurrency = (string) (
                data_get($i, 'amounts.price.currency')
                ?? $currency
            );

            $out[] = new OrderItemDTO(
                sku:              isset($i['sku']) ? (string) $i['sku'] : null,
                name:             (string) ($i['name'] ?? ($i['product_name'] ?? 'Untitled')),
                quantity:         max(1, $qty),
                unitPrice:        $unitPrice,
                totalPrice:       $totalPrice,
                currency:         $itemCurrency,
                remoteProductId:  isset($i['product_id']) ? (string) $i['product_id'] : null,
                remoteVariantId:  isset($i['variant_id']) ? (string) $i['variant_id'] : null,
                extra:            [
                    'options' => $i['options'] ?? null,
                    'weight'  => data_get($i, 'weight.value'),
                ],
            );
        }
        return $out;
    }

    private function extractCurrency(array $data): string
    {
        return (string) (
            data_get($data, 'total.currency')
            ?? data_get($data, 'currency')
            ?? 'SAR'
        );
    }

    /**
     * @return array{subtotal: float, tax: float, shipping_fee: float, discount: float, total: float, cod_amount: float}
     */
    private function extractTotals(array $data, string $currency): array
    {
        $sub      = (float) (data_get($data, 'sub_total.amount') ?? data_get($data, 'subtotal.amount') ?? 0);
        $tax      = (float) (data_get($data, 'tax.amount') ?? 0);
        $shipping = (float) (data_get($data, 'shipping_cost.amount') ?? data_get($data, 'shipping.amount') ?? 0);
        $discount = (float) (data_get($data, 'discount.amount') ?? data_get($data, 'discounts_amounts.amount') ?? 0);
        $total    = (float) (data_get($data, 'total.amount') ?? 0);

        // Salla's `cash_on_delivery` block only appears for COD orders; when
        // absent AND payment_method is 'cod', we fall back to the order total.
        $codAmount = (float) (data_get($data, 'cash_on_delivery.amount') ?? 0);
        if ($codAmount === 0.0 && $this->isCodPaymentMethod((string) data_get($data, 'payment_method', ''))) {
            $codAmount = $total;
        }

        return [
            'subtotal'     => $sub,
            'tax'          => $tax,
            'shipping_fee' => $shipping,
            'discount'     => $discount,
            'total'        => $total,
            'cod_amount'   => $codAmount,
        ];
    }

    private function extractStatus(array $data): ?string
    {
        return (string) (
            data_get($data, 'status.slug')
            ?? data_get($data, 'status.name')
            ?? data_get($data, 'status')
            ?? ''
        ) ?: null;
    }

    private function extractNote(array $data): ?string
    {
        $note = trim((string) (
            $data['note']
            ?? $data['notes']
            ?? data_get($data, 'shipping.address.notes')
            ?? ''
        ));
        return $note !== '' ? $note : null;
    }

    private function extractDate(array $data): ?string
    {
        return data_get($data, 'date.date')
            ?? data_get($data, 'created_at')
            ?? null;
    }

    private function canonicalPaymentMethod(array $data): ?string
    {
        $native = strtolower(trim((string) ($data['payment_method'] ?? '')));
        if ($native === '') return null;

        // Best-effort canonicalization. Anything not recognized passes
        // through so Phase 5 diagnostics can flag new provider values.
        return match (true) {
            $native === 'cod'
                || str_contains($native, 'cash') => 'cod',
            str_contains($native, 'mada')        => 'mada',
            str_contains($native, 'applepay')
                || $native === 'apple_pay'       => 'apple_pay',
            str_contains($native, 'tabby')       => 'tabby',
            str_contains($native, 'tamara')      => 'tamara',
            str_contains($native, 'bank')        => 'bank_transfer',
            str_contains($native, 'wallet')      => 'wallet',
            str_contains($native, 'credit')
                || str_contains($native, 'card') => 'card',
            default                              => 'prepaid',
        };
    }

    private function canonicalFinancialStatus(array $data): ?string
    {
        $native = strtolower(trim((string) (
            data_get($data, 'payment_method_status')
            ?? data_get($data, 'financial_status')
            ?? data_get($data, 'status.payment_status')
            ?? ''
        )));
        if ($native === '') return null;

        return match (true) {
            str_contains($native, 'paid')      => 'paid',
            str_contains($native, 'pending')   => 'pending',
            str_contains($native, 'refund')    => 'refunded',
            str_contains($native, 'void')
                || str_contains($native, 'cancel') => 'voided',
            default                            => 'unknown',
        };
    }

    private function isCodPaymentMethod(string $native): bool
    {
        $n = strtolower(trim($native));
        return $n === 'cod' || str_contains($n, 'cash');
    }
}
