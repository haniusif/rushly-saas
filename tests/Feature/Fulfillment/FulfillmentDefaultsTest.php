<?php

namespace Tests\Feature\Fulfillment;

use App\Fulfillment\Events\FulfillmentCompleted;
use App\Fulfillment\Events\FulfillmentRequested;
use App\Fulfillment\Models\Fulfillment;
use App\Fulfillment\Models\FulfillmentDefault;
use App\Fulfillment\Services\FulfillmentService;
use App\Models\Backend\Merchant;
use App\Oms\Models\Order;
use App\Oms\Models\OrderEvent;
use Illuminate\Support\Facades\Event;

class FulfillmentDefaultsTest extends FulfillmentTestCase
{
    /** @test */
    public function tenant_override_wins_over_global_for_non_null_fields(): void
    {
        FulfillmentDefault::create([
            'company_id'                  => null,
            'default_strategy'            => 'merchant_self',
            'service_last_mile_strategy'  => 'threepl_dropship',
            'service_fulfillment_strategy'=> 'wms',
        ]);
        FulfillmentDefault::create([
            'company_id'                  => 42,
            'default_strategy'            => null,               // ← falls through to global
            'service_fulfillment_strategy'=> 'merchant_self',    // ← overrides global
        ]);

        $resolved = FulfillmentDefault::resolvedFor(42);

        $this->assertSame('merchant_self',    $resolved['default_strategy'],             'default falls through to global when tenant null');
        $this->assertSame('threepl_dropship', $resolved['service_last_mile_strategy'],   'last_mile inherits from global');
        $this->assertSame('merchant_self',    $resolved['service_fulfillment_strategy'], 'fulfillment overridden by tenant');
        $this->assertNull($resolved['service_storage_strategy'], 'storage unset everywhere → null');
    }

    /** @test */
    public function service_to_strategy_pick_uses_first_matching_service(): void
    {
        $resolved = [
            'default_strategy'             => 'merchant_self',
            'service_last_mile_strategy'   => 'threepl_dropship',
            'service_fulfillment_strategy' => 'wms',
            'service_storage_strategy'     => null,
        ];

        // Merchant with all three — order matters (last_mile first)
        $this->assertSame('threepl_dropship', FulfillmentDefault::strategyForMerchantServices(['last_mile', 'fulfillment', 'storage'], $resolved));
        // Merchant with fulfillment only
        $this->assertSame('wms',              FulfillmentDefault::strategyForMerchantServices(['fulfillment'], $resolved));
        // Merchant with storage only (nothing mapped) → null
        $this->assertNull(                     FulfillmentDefault::strategyForMerchantServices(['storage'], $resolved));
        // Empty services → null
        $this->assertNull(                     FulfillmentDefault::strategyForMerchantServices([], $resolved));
    }

    /** @test */
    public function fulfillment_service_uses_service_mapped_strategy_when_no_route_matches(): void
    {
        Event::fake([FulfillmentRequested::class, FulfillmentCompleted::class]);

        // Global maps fulfillment → merchant_self. No routes, no tenant override.
        FulfillmentDefault::create([
            'company_id'                  => null,
            'service_fulfillment_strategy'=> 'merchant_self',
        ]);

        // Seed a merchant with services=[fulfillment]. Merchant's
        // $fillable is deliberately narrow (services was added via a
        // separate migration for the biz-logic feature), so set it
        // directly.
        $merchant = Merchant::create(['business_name' => 'test']);
        \DB::table('merchants')->where('id', $merchant->id)->update([
            'company_id' => 1,
            'services'   => json_encode(['fulfillment']),
        ]);

        $order = Order::create([
            'company_id'           => 1,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'svc-1',
            'merchant_id'          => $merchant->id,
            'currency'             => 'SAR',
            'total'                => 100,
            'cod_amount'           => 100,
        ]);

        $f = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNotNull($f);
        $this->assertSame('merchant_self', $f->strategy, 'strategy resolved via service→strategy mapping');
        $this->assertSame(Fulfillment::STATUS_COMPLETED, $f->status);
        Event::assertDispatched(FulfillmentCompleted::class);
    }

    /** @test */
    public function falls_through_to_default_strategy_when_no_service_matches(): void
    {
        Event::fake([FulfillmentRequested::class, FulfillmentCompleted::class]);

        FulfillmentDefault::create([
            'company_id'                => null,
            'default_strategy'          => 'merchant_self',
            'service_storage_strategy'  => 'wms',                // ← would match if merchant had `storage`
        ]);

        $merchant = Merchant::create(['business_name' => 'no-storage']);
        \DB::table('merchants')->where('id', $merchant->id)->update([
            'company_id' => 1,
            'services'   => json_encode(['last_mile']),   // no `storage` → falls through to default
        ]);

        $order = Order::create([
            'company_id'           => 1,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'svc-2',
            'merchant_id'          => $merchant->id,
            'currency'             => 'SAR',
            'total'                => 100,
        ]);

        $f = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNotNull($f);
        $this->assertSame('merchant_self', $f->strategy, 'fell through to default_strategy since services did not match');
    }

    /** @test */
    public function no_route_no_default_no_service_mapping_records_audit_and_returns_null(): void
    {
        // Empty defaults table.
        config(['fulfillment.default_strategy' => null]);

        $order = Order::create([
            'company_id'           => 1,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'svc-3',
            'merchant_id'          => null,
            'currency'             => 'SAR',
            'total'                => 100,
        ]);

        $result = $this->app->make(FulfillmentService::class)->fulfill($order);

        $this->assertNull($result);
        $this->assertSame(1, OrderEvent::where('order_id', $order->id)->where('event_type', 'fulfillment_no_route')->count());
    }
}
