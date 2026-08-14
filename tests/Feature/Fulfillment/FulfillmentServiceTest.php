<?php

namespace Tests\Feature\Fulfillment;

use App\Fulfillment\Events\FulfillmentCompleted;
use App\Fulfillment\Events\FulfillmentFailed;
use App\Fulfillment\Events\FulfillmentRequested;
use App\Fulfillment\Models\Fulfillment;
use App\Fulfillment\Models\FulfillmentRoute;
use App\Fulfillment\Services\FulfillmentService;
use App\Oms\Models\Order;
use App\Oms\Models\OrderEvent;
use Illuminate\Support\Facades\Event;

class FulfillmentServiceTest extends FulfillmentTestCase
{
    private function order(): Order
    {
        return Order::create([
            'company_id'           => 1,
            'connection_id'        => null,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'ord-1',
            'currency'             => 'SAR',
            'total'                => 100.00,
            'cod_amount'           => 100.00,
            'shipping_city_id'     => 9,
        ]);
    }

    /** @test */
    public function no_route_and_no_default_leaves_order_pending_with_audit_note(): void
    {
        Event::fake([FulfillmentRequested::class, FulfillmentCompleted::class, FulfillmentFailed::class]);
        config(['fulfillment.default_strategy' => null]);

        $order = $this->order();
        $result = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNull($result);
        $this->assertSame(0, Fulfillment::count());
        $this->assertSame(1, OrderEvent::where('order_id', $order->id)->where('event_type', 'fulfillment_no_route')->count());
        Event::assertNotDispatched(FulfillmentRequested::class);
    }

    /** @test */
    public function default_strategy_applies_when_no_route_matches(): void
    {
        Event::fake([FulfillmentRequested::class, FulfillmentCompleted::class]);
        config(['fulfillment.default_strategy' => 'merchant_self']);

        $order = $this->order();
        $f = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNotNull($f);
        $this->assertSame('merchant_self', $f->strategy);
        $this->assertNull($f->route_id);
        $this->assertSame(Fulfillment::STATUS_COMPLETED, $f->status);
        Event::assertDispatched(FulfillmentRequested::class, 1);
        Event::assertDispatched(FulfillmentCompleted::class, 1);
    }

    /** @test */
    public function matching_route_drives_strategy_selection_and_writes_audit(): void
    {
        Event::fake([FulfillmentRequested::class, FulfillmentCompleted::class]);

        FulfillmentRoute::create([
            'company_id'           => 1,
            'name'                 => 'Salla → self',
            'priority'             => 10,
            'is_active'            => true,
            'source_provider_code' => 'salla',
            'strategy'             => 'merchant_self',
        ]);

        $order = $this->order();
        $f = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNotNull($f);
        $this->assertSame('merchant_self', $f->strategy);
        $this->assertNotNull($f->route_id);
        $this->assertSame(Fulfillment::STATUS_COMPLETED, $f->status);
        $this->assertNotNull($f->completed_at);

        // Audit row on the ORDER for the fulfillment request
        $this->assertSame(1, OrderEvent::where('order_id', $order->id)->where('event_type', 'fulfillment_requested')->count());

        Event::assertDispatched(FulfillmentRequested::class, 1);
        Event::assertDispatched(FulfillmentCompleted::class, 1);
    }

    /** @test */
    public function three_pl_dropship_without_shipping_connection_id_fails_cleanly(): void
    {
        Event::fake([FulfillmentFailed::class, FulfillmentCompleted::class]);

        FulfillmentRoute::create([
            'company_id' => 1,
            'name'       => 'bad 3pl',
            'priority'   => 10,
            'is_active'  => true,
            'strategy'   => 'threepl_dropship',
            'shipping_connection_id' => null,   // ← missing on purpose
        ]);

        $order = $this->order();
        $f = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNotNull($f);
        $this->assertSame(Fulfillment::STATUS_FAILED, $f->status);
        $this->assertNotNull($f->failed_at);
        $this->assertStringContainsString('requires shipping_connection_id', (string) $f->last_error);
        Event::assertDispatched(FulfillmentFailed::class, 1);
        Event::assertNotDispatched(FulfillmentCompleted::class);
    }

    /** @test */
    public function is_idempotent_on_repeat_when_order_already_has_non_terminal_fulfillment(): void
    {
        Event::fake([FulfillmentRequested::class]);
        config(['fulfillment.default_strategy' => 'merchant_self']);

        $order = $this->order();
        $service = $this->app->make(FulfillmentService::class);
        $first  = $service->fulfill($order);
        $second = $service->fulfill($order);

        // merchant_self completes → terminal → BUT since it is terminal
        // status (completed), the guard should NOT trip and a second call
        // would create a new row. The guard says "skip if non-terminal
        // exists" — completed is terminal, so we DO retry.
        // That is actually correct behavior for MVP: retry replays.
        // Let''s force the case where fulfillment is stuck non-terminal:
        // create a fulfillment manually in `in_progress` and call fulfill.
        Fulfillment::where("order_id", $order->id)->delete();
        Fulfillment::create([
            "company_id" => 1, "order_id" => $order->id,
            "strategy"   => "wms", "status" => "in_progress",
        ]);
        $third = $service->fulfill($order);
        $this->assertSame(Fulfillment::STATUS_IN_PROGRESS, $third->status);
        $this->assertSame("wms", $third->strategy, "should return the existing in_progress row unchanged");
    }
}
