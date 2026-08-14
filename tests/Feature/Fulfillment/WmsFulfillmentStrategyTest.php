<?php

namespace Tests\Feature\Fulfillment;

use App\Enums\Wms\FulfillmentStatus as WmsStatus;
use App\Fulfillment\Models\Fulfillment;
use App\Fulfillment\Strategies\WmsFulfillmentStrategy;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wms\WmsFulfillment;
use App\Oms\Models\Order;

class WmsFulfillmentStrategyTest extends FulfillmentTestCase
{
    // parcels + wms_fulfillments tables built in FulfillmentTestCase::setUp
    // alongside the fulfillments + orders tables.

    private function order(): Order
    {
        return Order::create([
            'company_id'           => 1,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'wms-1',
            'merchant_id'          => 42,
            'customer_name'        => 'C',
            'customer_phone'       => '+9660000',
            'shipping_line1'       => 'Addr',
            'currency'             => 'SAR',
            'total'                => 100,
            'cod_amount'           => 100,
        ]);
    }

    private function fulfillment(Order $order, ?int $hubId = 7): Fulfillment
    {
        return Fulfillment::create([
            'company_id' => 1,
            'order_id'   => $order->id,
            'strategy'   => 'wms',
            'status'     => Fulfillment::STATUS_PENDING,
            'hub_id'     => $hubId,
        ]);
    }

    /** @test */
    public function creates_parcel_wms_fulfillment_and_stamps_links(): void
    {
        $order = $this->order();
        $f     = $this->fulfillment($order);

        $this->app->make(WmsFulfillmentStrategy::class)->execute($f, $order);
        $f->refresh();

        $this->assertSame(Fulfillment::STATUS_IN_PROGRESS, $f->status);
        $this->assertNotNull($f->parcel_id);
        $this->assertNotNull($f->wms_fulfillment_id);
        $this->assertNotNull($f->external_reference);
        $this->assertStringStartsWith('WMS-', $f->external_reference);
        $this->assertNotNull($f->started_at);

        $parcel = Parcel::find($f->parcel_id);
        $this->assertSame($order->id, (int) $parcel->oms_order_id);
        $this->assertSame((int) $f->wms_fulfillment_id, (int) $parcel->wms_fulfillment_id);

        $wms = WmsFulfillment::find($f->wms_fulfillment_id);
        $this->assertSame($parcel->id,     (int) $wms->parcel_id);
        $this->assertSame(42,              (int) $wms->merchant_id);
        $this->assertSame(7,               (int) $wms->hub_id);
        $this->assertSame(WmsStatus::PENDING, $wms->status);
    }

    /** @test */
    public function is_idempotent_on_repeat_execute(): void
    {
        $order = $this->order();
        $f     = $this->fulfillment($order);
        $strategy = $this->app->make(WmsFulfillmentStrategy::class);

        $strategy->execute($f, $order);
        $f->refresh();
        $wmsIdFirst = $f->wms_fulfillment_id;

        $strategy->execute($f, $order);
        $f->refresh();

        $this->assertSame($wmsIdFirst, $f->wms_fulfillment_id);
        $this->assertSame(1, WmsFulfillment::count());
        $this->assertSame(1, Parcel::where('oms_order_id', $order->id)->count());
    }
}
