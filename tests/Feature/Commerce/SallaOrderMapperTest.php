<?php

namespace Tests\Feature\Commerce;

use App\Oms\DTOs\OrderDTO;
use App\Oms\Exceptions\NormalizationException;
use App\Oms\Normalization\OrderNormalizer;
use App\Oms\Normalization\Providers\SallaOrderMapper;
use Tests\TestCase;

class SallaOrderMapperTest extends TestCase
{
    private function fixture(): array
    {
        $path = base_path('tests/fixtures/salla/order.created.json');
        $this->assertFileExists($path, 'salla order.created fixture missing');
        return json_decode((string) file_get_contents($path), true);
    }

    /** @test */
    public function maps_the_full_salla_order_shape_into_a_canonical_order_dto(): void
    {
        $payload = $this->fixture();

        /** @var SallaOrderMapper $mapper */
        $mapper = $this->app->make(SallaOrderMapper::class);
        $dto    = $mapper->map($payload, companyId: null);

        // Identity + currency
        $this->assertInstanceOf(OrderDTO::class, $dto);
        $this->assertSame('salla',    $dto->providerCode);
        $this->assertSame('4242001',  $dto->remoteOrderId);
        $this->assertSame('1042',     $dto->remoteOrderNumber);
        $this->assertSame('under_review', $dto->providerStatus);
        $this->assertSame('SAR',      $dto->currency);

        // Totals — pass through verbatim, no math
        $this->assertSame(180.0, $dto->subtotal);
        $this->assertSame(9.0,   $dto->tax);
        $this->assertSame(15.0,  $dto->shippingFee);
        $this->assertSame(4.0,   $dto->discount);
        $this->assertSame(200.0, $dto->total);

        // COD: explicit cash_on_delivery block wins over the fallback
        $this->assertTrue($dto->isCod());
        $this->assertSame(200.0, $dto->codAmount);
        $this->assertSame('cod', $dto->paymentMethod);

        // Customer
        $this->assertSame('88123',              $dto->customer->remoteId);
        $this->assertSame('Layla Al-Harbi',     $dto->customer->name);
        $this->assertSame('+966501234567',      $dto->customer->phone);
        $this->assertSame('layla@example.com',  $dto->customer->email);

        // Shipping address
        $this->assertSame('Layla Al-Harbi',            $dto->shippingAddress->name);
        $this->assertSame('+966501234567',             $dto->shippingAddress->phone);
        $this->assertSame('King Fahd Road, Bldg 5',    $dto->shippingAddress->line1);
        $this->assertSame('Floor 3, Apartment 8',      $dto->shippingAddress->line2);
        $this->assertSame('Riyadh',                    $dto->shippingAddress->cityName);
        $this->assertSame('Al Olaya',                  $dto->shippingAddress->areaName);
        $this->assertSame('Saudi Arabia',              $dto->shippingAddress->country);
        $this->assertSame('12341',                     $dto->shippingAddress->postcode);

        // AddressResolver ran — resolved ids will be null on a clean DB
        // where cities isn't seeded with Riyadh, but the DTO must still
        // hold null (not throw / not be missing).
        $this->assertArrayHasKey('resolved_city_id', $dto->shippingAddress->toArray());

        // Items — count, price extraction, currency inheritance
        $this->assertCount(2, $dto->items);

        $first = $dto->items[0];
        $this->assertSame('SKU-A-001',                    $first->sku);
        $this->assertSame('Traditional Kaftan (Size M)',  $first->name);
        $this->assertSame(1,                              $first->quantity);
        $this->assertSame(142.86,                         $first->unitPrice);   // price_without_tax preferred
        $this->assertSame(150.0,                          $first->totalPrice);
        $this->assertSame('55501',                        $first->remoteProductId);
        $this->assertSame('66601',                        $first->remoteVariantId);

        $second = $dto->items[1];
        $this->assertSame(2,     $second->quantity);
        $this->assertSame(15.0,  $second->unitPrice);
        $this->assertSame(30.0,  $second->totalPrice);

        // Note absent in fixture → null
        $this->assertNull($dto->note);
        $this->assertSame('2026-07-01 09:12:34.000000', $dto->occurredAt);

        // Provider-specific overflow lives in `extra`, not in the canonical fields
        $this->assertSame('store', $dto->extra['salla_channel']);
        $this->assertSame('https://example.salla.sa/order/4242001', $dto->extra['salla_urls']['customer']);
    }

    /** @test */
    public function returns_a_dto_that_round_trips_via_to_array_for_persistence(): void
    {
        $payload = $this->fixture();

        $dto = $this->app->make(SallaOrderMapper::class)->map($payload);
        $arr = $dto->toArray();

        // Persistence shape has the canonical keys downstream OMS relies on
        foreach ([
            'provider_code', 'remote_order_id', 'remote_order_number',
            'provider_status', 'payment_method', 'financial_status',
            'customer', 'shipping_address', 'items',
            'subtotal', 'tax', 'shipping_fee', 'discount', 'total',
            'cod_amount', 'currency', 'occurred_at', 'extra',
        ] as $key) {
            $this->assertArrayHasKey($key, $arr, "toArray() missing key: $key");
        }

        $this->assertSame('salla', $arr['provider_code']);
        $this->assertIsArray($arr['customer']);
        $this->assertIsArray($arr['shipping_address']);
        $this->assertIsArray($arr['items']);
        $this->assertCount(2, $arr['items']);
    }

    /** @test */
    public function throws_normalization_exception_on_missing_required_fields(): void
    {
        $bad = [
            'event' => 'order.created',
            'data'  => [
                // 'id' intentionally missing to trip validation
                'customer' => ['first_name' => 'X'],
            ],
        ];

        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessageMatches('/Payload validation failed for provider .salla./');

        $this->app->make(SallaOrderMapper::class)->map($bad);
    }

    /** @test */
    public function facade_normalizer_routes_by_provider_code(): void
    {
        $payload = $this->fixture();

        /** @var OrderNormalizer $normalizer */
        $normalizer = $this->app->make(OrderNormalizer::class);
        $dto = $normalizer->normalize('salla', $payload, companyId: null);

        $this->assertSame('4242001', $dto->remoteOrderId);
    }
}
