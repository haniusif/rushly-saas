<?php

namespace Tests\Feature\Oms;

use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\CommerceProvider;
use App\Commerce\Models\WebhookEvent;
use App\Oms\Enums\OrderStatus;
use App\Oms\Enums\PaymentStatus;
use App\Oms\Enums\FulfillmentStatus;
use App\Oms\Events\OrderReceived;
use App\Oms\Events\OrderUpdated;
use App\Oms\Models\Order;
use App\Oms\Models\OrderEvent;
use App\Oms\Models\OrderItem;
use App\Oms\Normalization\Providers\SallaOrderMapper;
use App\Oms\Services\OrderService;
use Illuminate\Support\Facades\Event;

class OrderServiceTest extends OmsTestCase
{
    private CommerceConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed a minimal Salla connection so the service has something
        // to bind orders to.
        $provider = CommerceProvider::create([
            'code' => 'salla', 'name' => 'Salla', 'status' => 'active',
            'supports' => ['oauth', 'webhooks'],
        ]);
        $this->connection = CommerceConnection::create([
            'company_id'       => 1,
            'provider_id'      => $provider->id,
            'connection_name'  => 'Test connection',
            'remote_store_id'  => '99999999',
            'merchant_id'      => null,
            'status'           => 'active',
        ]);
    }

    private function fixtureDto()
    {
        $payload = json_decode(file_get_contents(base_path('tests/fixtures/salla/order.created.json')), true);
        return $this->app->make(SallaOrderMapper::class)->map($payload);
    }

    /** @test */
    public function creates_order_items_and_created_event_on_first_receive(): void
    {
        Event::fake([OrderReceived::class, OrderUpdated::class]);

        $dto = $this->fixtureDto();
        $service = $this->app->make(OrderService::class);
        $order = $service->receiveNormalized($dto, $this->connection);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('salla',    $order->source_provider_code);
        $this->assertSame('4242001',  $order->remote_order_id);
        $this->assertSame('1042',     $order->remote_order_number);
        $this->assertSame('SAR',      $order->currency);
        $this->assertEquals(200.00,   $order->total);
        $this->assertEquals(200.00,   $order->cod_amount);
        $this->assertSame('cod',      $order->payment_method);
        $this->assertSame('under_review', $order->provider_status);

        // Canonical statuses on create
        $this->assertSame(OrderStatus::PENDING,          $order->status);
        $this->assertSame(FulfillmentStatus::UNFULFILLED, $order->fulfillment_status);
        $this->assertSame(PaymentStatus::PENDING,        $order->payment_status);   // fixture has payment_method_status: "pending" → canonicalised to pending

        // Customer + shipping snapshot
        $this->assertSame('Layla Al-Harbi', $order->customer_name);
        $this->assertSame('+966501234567',  $order->customer_phone);
        $this->assertSame('Riyadh',         $order->shipping_city_name);
        $this->assertSame('Al Olaya',       $order->shipping_area_name);

        // Line items rewritten in full
        $this->assertCount(2, $order->items);
        $this->assertSame('SKU-A-001', $order->items[0]->sku);
        $this->assertEquals(1,          $order->items[0]->quantity);
        $this->assertEquals(150.00,     $order->items[0]->total_price);
        $this->assertEquals(2,          $order->items[1]->quantity);
        $this->assertEquals(30.00,      $order->items[1]->total_price);

        // Exactly one audit-trail row of type `created`
        $this->assertSame(1, OrderEvent::where('order_id', $order->id)->count());
        $this->assertSame(OrderEvent::TYPE_CREATED, OrderEvent::where('order_id', $order->id)->first()->event_type);

        // OrderReceived dispatched exactly once
        Event::assertDispatched(OrderReceived::class, 1);
        Event::assertNotDispatched(OrderUpdated::class);
    }

    /** @test */
    public function is_idempotent_on_repeat_with_identical_payload(): void
    {
        Event::fake([OrderReceived::class, OrderUpdated::class]);

        $dto = $this->fixtureDto();
        $service = $this->app->make(OrderService::class);
        $first  = $service->receiveNormalized($dto, $this->connection);
        $second = $service->receiveNormalized($dto, $this->connection);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Order::count());
        $this->assertSame(2, OrderItem::where('order_id', $first->id)->count());

        // Only OrderReceived fires (on first insert). Second call is a
        // no-op update: no fields changed, so no OrderUpdated event and
        // no `updated` audit row.
        Event::assertDispatched(OrderReceived::class, 1);
        Event::assertNotDispatched(OrderUpdated::class);
        $this->assertSame(1, OrderEvent::where('order_id', $first->id)->count());
    }

    /** @test */
    public function dispatches_order_updated_and_writes_audit_row_when_fields_change(): void
    {
        Event::fake([OrderReceived::class, OrderUpdated::class]);

        $service = $this->app->make(OrderService::class);
        $first = $service->receiveNormalized($this->fixtureDto(), $this->connection);

        // Second delivery of the SAME order with a different total
        $payload = json_decode(file_get_contents(base_path('tests/fixtures/salla/order.created.json')), true);
        $payload['data']['total']['amount'] = 250.00;
        $payload['data']['note'] = 'Updated instructions from customer';

        $dto2 = $this->app->make(SallaOrderMapper::class)->map($payload);
        $second = $service->receiveNormalized($dto2, $this->connection);

        $this->assertSame($first->id, $second->id);
        $this->assertEquals(250.00, $second->total);
        $this->assertSame('Updated instructions from customer', $second->note);

        Event::assertDispatched(OrderUpdated::class, function (OrderUpdated $e) use ($first) {
            return $e->order->id === $first->id
                && array_key_exists('total', $e->changes)
                && array_key_exists('note',  $e->changes);
        });

        $auditRows = OrderEvent::where('order_id', $first->id)->orderBy('id')->get();
        $this->assertSame(2, $auditRows->count());
        $this->assertSame(OrderEvent::TYPE_CREATED, $auditRows[0]->event_type);
        $this->assertSame(OrderEvent::TYPE_UPDATED, $auditRows[1]->event_type);
    }

    /** @test */
    public function links_source_webhook_event_when_provided(): void
    {
        $webhookEvent = WebhookEvent::create([
            'company_id'      => $this->connection->company_id,
            'connection_id'   => $this->connection->id,
            'provider_code'   => 'salla',
            'event_type'      => 'order.created',
            'idempotency_key' => 'salla:test:order.created:4242001',
            'payload'         => ['dummy' => true],
            'received_at'     => now(),
        ]);

        $service = $this->app->make(OrderService::class);
        $order = $service->receiveNormalized($this->fixtureDto(), $this->connection, $webhookEvent);

        $this->assertSame($webhookEvent->id, $order->webhook_event_id);
    }
}
