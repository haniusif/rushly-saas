<?php

namespace App\Fulfillment\Services;

use App\Fulfillment\Contracts\FulfillmentStrategyInterface;
use App\Fulfillment\Models\FulfillmentRoute;
use App\Oms\Models\Order;
use InvalidArgumentException;

/**
 * Given an Order, evaluate the tenant's active fulfillment_routes in
 * priority order and return the first match. All condition columns on a
 * route are AND'd; a null column means "don't filter on this column".
 *
 * When no route matches, returns null. FulfillmentService decides what
 * to do — apply the config('fulfillment.default_strategy') fallback, or
 * leave the Fulfillment pending for manual assignment.
 *
 * Router is pure: no side effects, no persistence. Testable via
 * seeding routes + calling `route($order)` and asserting the returned
 * FulfillmentRoute (or null).
 */
class FulfillmentRouter
{
    public function __construct(
        private readonly \Illuminate\Contracts\Container\Container $container,
    ) {}

    public function route(Order $order): ?FulfillmentRoute
    {
        $routes = FulfillmentRoute::query()
            ->where('company_id', $order->company_id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($routes as $route) {
            if ($this->matches($route, $order)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Resolve a strategy instance by code. Used by FulfillmentService
     * after route() picks a route (or after the default_strategy
     * fallback fires).
     */
    public function strategyByCode(string $code): FulfillmentStrategyInterface
    {
        $class = config('fulfillment.strategies.' . $code . '.class');
        if (! $class || ! class_exists($class)) {
            throw new InvalidArgumentException("Unknown fulfillment strategy: {$code}");
        }
        $instance = $this->container->make($class);
        if (! $instance instanceof FulfillmentStrategyInterface) {
            throw new InvalidArgumentException(
                "Class {$class} for strategy {$code} does not implement FulfillmentStrategyInterface."
            );
        }
        return $instance;
    }

    /**
     * All-or-nothing condition matcher. Every non-null condition on the
     * route must match the Order for the route to be picked.
     */
    private function matches(FulfillmentRoute $route, Order $order): bool
    {
        if ($route->merchant_id !== null && (int) $route->merchant_id !== (int) $order->merchant_id) {
            return false;
        }
        if ($route->source_provider_code !== null && $route->source_provider_code !== $order->source_provider_code) {
            return false;
        }
        if ($route->shipping_city_id !== null && (int) $route->shipping_city_id !== (int) $order->shipping_city_id) {
            return false;
        }
        if ($route->shipping_country !== null && strcasecmp($route->shipping_country, (string) $order->shipping_country) !== 0) {
            return false;
        }
        if ($route->min_total !== null && (float) $order->total < (float) $route->min_total) {
            return false;
        }
        if ($route->max_total !== null && (float) $order->total > (float) $route->max_total) {
            return false;
        }
        if ($route->is_cod !== null) {
            $orderIsCod = (float) $order->cod_amount > 0;
            if ($orderIsCod !== (bool) $route->is_cod) {
                return false;
            }
        }
        return true;
    }
}
