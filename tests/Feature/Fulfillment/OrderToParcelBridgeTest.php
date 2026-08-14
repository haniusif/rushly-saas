<?php

namespace Tests\Feature\Fulfillment;

use App\Fulfillment\Bridges\OrderToParcelBridge;
use App\Fulfillment\Exceptions\StrategyRejectedException;
use App\Models\Backend\Parcel;
use App\Oms\Models\Order;

class OrderToParcelBridgeTest extends FulfillmentTestCase
{
    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'company_id'           => 1,
            'connection_id'        => null,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'ord-1',
            'remote_order_number'  => '#1042',
            'merchant_id'          => 42,
            'currency'             => 'SAR',
            'total'                => 100.00,
            'cod_amount'           => 100.00,
            'customer_name'        => 'Test Customer',
            'customer_phone'       => '+966501234567',
            'shipping_line1'       => 'King Fahd Rd',
            'shipping_city_name'   => 'Riyadh',
            'shipping_city_id'     => 9,
        ], $overrides));
    }

    /** @test */
    public function creates_parcel_with_mapped_fields(): void
    {
        $order = $this->order();
        $parcel = $this->app->make(OrderToParcelBridge::class)->toParcel($order);

        $this->assertInstanceOf(Parcel::class, $parcel);
        $this->assertSame(42, (int) $parcel->merchant_id);
        $this->assertSame($order->id, (int) $parcel->oms_order_id);
        $this->assertSame('Test Customer', $parcel->customer_name);
        $this->assertSame('+966501234567', $parcel->customer_phone);
        $this->assertStringContainsString('King Fahd Rd', $parcel->customer_address);
        $this->assertStringContainsString('Riyadh',       $parcel->customer_address);
        $this->assertSame(9, (int) $parcel->city_id);
        $this->assertEquals(100.00, $parcel->cash_collection);
        $this->assertEquals(100.00, $parcel->cod_amount);
        $this->assertSame('#1042',  $parcel->reference_number);
        $this->assertStringContainsString('salla', $parcel->note);
        $this->assertStringContainsString((string) $order->id, $parcel->note);
        $this->assertNotEmpty($parcel->tracking_id);
    }

    /** @test */
    public function is_idempotent_on_repeat(): void
    {
        $order  = $this->order();
        $bridge = $this->app->make(OrderToParcelBridge::class);

        $first  = $bridge->toParcel($order);
        $second = $bridge->toParcel($order);
        $third  = $bridge->toParcel($order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->id, $third->id);
        $this->assertSame(1, Parcel::where('oms_order_id', $order->id)->count());
    }

    /** @test */
    public function throws_strategy_rejected_when_merchant_id_missing(): void
    {
        $order = $this->order(['merchant_id' => null]);
        $this->expectException(StrategyRejectedException::class);
        $this->expectExceptionMessageMatches('/no merchant_id/');
        $this->app->make(OrderToParcelBridge::class)->toParcel($order);
    }
}
