<?php

namespace Tests\Feature\Fulfillment;

use App\Fulfillment\Models\FulfillmentRoute;
use App\Fulfillment\Services\FulfillmentRouter;
use App\Oms\Models\Order;

class FulfillmentRouterTest extends FulfillmentTestCase
{
    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'company_id'           => 1,
            'connection_id'        => null,
            'source_provider_code' => 'salla',
            'remote_order_id'      => 'r1',
            'currency'             => 'SAR',
            'total'                => 100.00,
            'cod_amount'           => 100.00,
            'shipping_country'     => 'Saudi Arabia',
            'shipping_city_id'     => 9,
        ], $overrides));
    }

    private function route(array $overrides): FulfillmentRoute
    {
        return FulfillmentRoute::create(array_merge([
            'company_id' => 1,
            'name'       => 'r',
            'priority'   => 100,
            'is_active'  => true,
            'strategy'   => 'merchant_self',
        ], $overrides));
    }

    /** @test */
    public function returns_null_when_no_active_route_matches(): void
    {
        $router = $this->app->make(FulfillmentRouter::class);
        $this->assertNull($router->route($this->order()));
    }

    /** @test */
    public function ignores_inactive_routes(): void
    {
        $this->route(['name' => 'inactive', 'is_active' => false, 'source_provider_code' => 'salla']);
        $router = $this->app->make(FulfillmentRouter::class);
        $this->assertNull($router->route($this->order()));
    }

    /** @test */
    public function picks_first_match_by_priority(): void
    {
        $this->route(['name' => 'low',  'priority' => 50, 'source_provider_code' => 'zid']);        // won't match
        $this->route(['name' => 'mid',  'priority' => 60, 'source_provider_code' => 'salla']);      // matches, priority 60
        $this->route(['name' => 'high', 'priority' => 40, 'source_provider_code' => 'salla']);      // matches, priority 40 → wins

        $router = $this->app->make(FulfillmentRouter::class);
        $picked = $router->route($this->order());
        $this->assertNotNull($picked);
        $this->assertSame('high', $picked->name);
    }

    /** @test */
    public function all_conditions_are_and_ed(): void
    {
        // A route that requires salla + city 9 + is_cod
        $this->route([
            'name' => 'strict',
            'source_provider_code' => 'salla',
            'shipping_city_id'     => 9,
            'is_cod'               => true,
        ]);

        $router = $this->app->make(FulfillmentRouter::class);

        // Matches
        $this->assertNotNull($router->route($this->order()));

        // City mismatch → miss
        $this->assertNull($router->route($this->order(['shipping_city_id' => 99])));

        // Non-COD → miss
        $this->assertNull($router->route($this->order(['cod_amount' => 0])));

        // Provider mismatch → miss
        $this->assertNull($router->route($this->order(['source_provider_code' => 'zid'])));
    }

    /** @test */
    public function total_range_conditions_work(): void
    {
        $this->route([
            'name'      => 'mid-range',
            'min_total' => 50,
            'max_total' => 200,
        ]);
        $router = $this->app->make(FulfillmentRouter::class);

        $this->assertNotNull($router->route($this->order(['total' => 100])));
        $this->assertNotNull($router->route($this->order(['total' => 50])));
        $this->assertNotNull($router->route($this->order(['total' => 200])));
        $this->assertNull($router->route($this->order(['total' => 49])));
        $this->assertNull($router->route($this->order(['total' => 201])));
    }
}
