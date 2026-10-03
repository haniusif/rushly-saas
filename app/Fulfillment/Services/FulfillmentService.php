<?php

namespace App\Fulfillment\Services;

use App\Fulfillment\Events\FulfillmentCompleted;
use App\Fulfillment\Events\FulfillmentFailed;
use App\Fulfillment\Events\FulfillmentRequested;
use App\Fulfillment\Events\FulfillmentStarted;
use App\Fulfillment\Exceptions\StrategyRejectedException;
use App\Fulfillment\Models\Fulfillment;
use App\Fulfillment\Models\FulfillmentDefault;
use App\Fulfillment\Models\FulfillmentRoute;
use App\Models\Backend\Merchant;
use App\Oms\Models\Order;
use App\Oms\Models\OrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator. `fulfill($order)` runs the whole pipeline:
 *   1. Ask FulfillmentRouter to pick a route (or fall back to default)
 *   2. Create Fulfillment row (status=pending, route/strategy captured)
 *   3. Write `parcel_linked`-style order_event and fire FulfillmentRequested
 *   4. Call strategy->execute() — mutates the row directly
 *   5. Fire FulfillmentStarted / FulfillmentCompleted based on final state
 *   6. On StrategyRejectedException: stamp failed_at + last_error, fire FulfillmentFailed
 *
 * Idempotency: refuses to create a second Fulfillment for an Order that
 * already has a non-terminal one. Callers who want to retry a failed
 * fulfillment call `retry($fulfillment)` which resets it and re-executes.
 */
class FulfillmentService
{
    public function __construct(
        private readonly FulfillmentRouter $router,
    ) {}

    public function fulfill(Order $order): ?Fulfillment
    {
        // Idempotency guard — don't stack fulfillments on the same order.
        $existing = Fulfillment::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status', [Fulfillment::STATUS_FAILED, Fulfillment::STATUS_CANCELLED])
            ->orderByDesc('id')
            ->first();
        if ($existing) {
            Log::info('fulfillment.skip_duplicate', [
                'order_id'        => $order->id,
                'existing_id'     => $existing->id,
                'existing_status' => $existing->status,
            ]);
            return $existing;
        }

        $route    = $this->router->route($order);
        $strategy = $route ? $route->strategy : $this->resolveFallbackStrategy($order);

        if ($strategy === '' || $strategy === null) {
            // No route matched, no service-mapped default, no global
            // default_strategy — leave order pending for manual
            // assignment. Record the miss on the order's audit trail
            // so ops sees why nothing happened.
            OrderEvent::create([
                'order_id'    => $order->id,
                'company_id'  => $order->company_id,
                'event_type'  => 'fulfillment_no_route',
                'payload'     => ['reason' => 'no route matched, no service-mapped or global default_strategy configured'],
                'occurred_at' => now(),
            ]);
            Log::info('fulfillment.no_route', ['order_id' => $order->id]);
            return null;
        }

        $fulfillment = DB::transaction(function () use ($order, $route, $strategy) {
            $f = Fulfillment::create([
                'company_id'             => $order->company_id,
                'order_id'               => $order->id,
                'strategy'               => $strategy,
                'route_id'               => $route?->id,
                'status'                 => Fulfillment::STATUS_PENDING,
                'shipping_connection_id' => $route?->shipping_connection_id,
                'hub_id'                 => $route?->hub_id,
                'payload'                => $this->routeSnapshot($route),
            ]);

            OrderEvent::create([
                'order_id'    => $order->id,
                'company_id'  => $order->company_id,
                'event_type'  => 'fulfillment_requested',
                'payload'     => [
                    'fulfillment_id' => $f->id,
                    'strategy'       => $strategy,
                    'route_id'       => $route?->id,
                    'route_name'     => $route?->name,
                ],
                'occurred_at' => now(),
            ]);

            return $f;
        });

        Event::dispatch(new FulfillmentRequested($fulfillment));

        // Execute the strategy. This mutates $fulfillment directly.
        try {
            $this->router->strategyByCode($strategy)->execute($fulfillment, $order);
        } catch (StrategyRejectedException $e) {
            $fulfillment->status     = Fulfillment::STATUS_FAILED;
            $fulfillment->failed_at  = now();
            $fulfillment->last_error = mb_substr($e->getMessage(), 0, 65_000);
            $fulfillment->save();
            Event::dispatch(new FulfillmentFailed($fulfillment, $e->getMessage()));
            return $fulfillment;
        } catch (\Throwable $e) {
            // Unexpected fault — stamp + rethrow so the caller (usually
            // a queue listener) triggers a retry per whatever policy
            // wraps it.
            $fulfillment->status     = Fulfillment::STATUS_FAILED;
            $fulfillment->failed_at  = now();
            $fulfillment->last_error = mb_substr($e->getMessage(), 0, 65_000);
            $fulfillment->save();
            Event::dispatch(new FulfillmentFailed($fulfillment, $e->getMessage()));
            throw $e;
        }

        // Fire the right terminal event based on where the strategy
        // parked the row.
        $fulfillment->refresh();
        if ($fulfillment->status === Fulfillment::STATUS_COMPLETED) {
            Event::dispatch(new FulfillmentCompleted($fulfillment));
        } elseif ($fulfillment->status === Fulfillment::STATUS_IN_PROGRESS) {
            Event::dispatch(new FulfillmentStarted($fulfillment));
        }

        return $fulfillment;
    }

    public function retry(Fulfillment $fulfillment): Fulfillment
    {
        // Reset state, keep audit history (payload log stays).
        $fulfillment->status     = Fulfillment::STATUS_PENDING;
        $fulfillment->last_error = null;
        $fulfillment->failed_at  = null;
        $fulfillment->started_at = null;
        $fulfillment->save();

        $order = $fulfillment->order;
        try {
            $this->router->strategyByCode($fulfillment->strategy)->execute($fulfillment, $order);
        } catch (StrategyRejectedException $e) {
            $fulfillment->status     = Fulfillment::STATUS_FAILED;
            $fulfillment->failed_at  = now();
            $fulfillment->last_error = mb_substr($e->getMessage(), 0, 65_000);
            $fulfillment->save();
            Event::dispatch(new FulfillmentFailed($fulfillment, $e->getMessage()));
        }
        return $fulfillment->fresh();
    }

    public function cancel(Fulfillment $fulfillment): Fulfillment
    {
        $this->router->strategyByCode($fulfillment->strategy)->cancel($fulfillment);
        return $fulfillment->fresh();
    }

    private function routeSnapshot(?FulfillmentRoute $route): array
    {
        if (! $route) {
            return ['route' => null, 'reason' => 'default_strategy fallback'];
        }
        return [
            'route' => [
                'id'                     => $route->id,
                'name'                   => $route->name,
                'priority'               => $route->priority,
                'strategy'               => $route->strategy,
                'shipping_connection_id' => $route->shipping_connection_id,
                'hub_id'                 => $route->hub_id,
            ],
        ];
    }

    /**
     * Fallback strategy resolution when no explicit fulfillment_route
     * matches. Precedence (first non-null wins):
     *
     *   1. Merchant's `services` JSON → mapped strategy from
     *      FulfillmentDefault (tenant override or global)
     *   2. FulfillmentDefault.default_strategy (tenant override or global)
     *   3. env FULFILLMENT_DEFAULT_STRATEGY (legacy env-driven fallback)
     *
     * Returns null when nothing resolves — caller records
     * fulfillment_no_route on the order.
     */
    private function resolveFallbackStrategy(Order $order): ?string
    {
        $resolved = FulfillmentDefault::resolvedFor($order->company_id);

        // 1. Merchant service mapping
        if ($order->merchant_id) {
            $services = Merchant::query()
                ->withoutGlobalScopes()
                ->where('id', $order->merchant_id)
                ->value('services');
            $services = is_string($services) ? (json_decode($services, true) ?: []) : (array) ($services ?? []);
            $mapped = FulfillmentDefault::strategyForMerchantServices($services, $resolved);
            if ($mapped) {
                return $mapped;
            }
        }

        // 2. Defaults table's default_strategy
        if (! empty($resolved['default_strategy'])) {
            return (string) $resolved['default_strategy'];
        }

        // 3. Legacy env fallback
        return (string) config('fulfillment.default_strategy') ?: null;
    }
}
