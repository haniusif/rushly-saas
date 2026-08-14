# Fulfillment Module

Given an OMS `Order`, decide **how** to get it out the door and dispatch the work. Fulfillment is a *routing + strategy* layer — it doesn't ship parcels itself; it hands off to the module that does (Shipping, WMS, vendor bridge, or the merchant themselves).

- **Module root**: `app/Fulfillment/`
- **Trigger**: `OrderReceived` event → `RouteToFulfillmentListener` → `FulfillmentService::fulfill($order)`
- **Migrations**: `2026_07_01_120001_create_fulfillments_table.php` + `2026_07_01_120002_create_fulfillment_routes_table.php` + `2026_07_01_130002_add_parcel_id_to_fulfillments.php`

---

## 1. Where it sits

```
OMS.md                       This doc                         Downstream modules
─────────                    ────────────                     ────────────────────
Order created  ──event──►    FulfillmentRouter    ──picks──►  Strategy:
(OrderReceived)                                                  wms          → WMS module
                                    ↓                            threepl_dropship → Shipping module
                             FulfillmentService                  vendor_direct    → future
                                    ↓                            merchant_self    → email/notify only
                             Fulfillment row created
                             (status=pending → in_progress
                              → completed / failed)
```

Fulfillment owns:
- The **routing decision** (which strategy handles this order)
- The **audit row** (`fulfillments`) tying the Order to whichever downstream did the work
- The **cancel + retry** entry points

It doesn't own the actual pick-and-pack, courier handoff, or vendor coordination. Those live in the modules the strategies delegate to.

---

## 2. Folder structure

```
app/Fulfillment/
├── Contracts/
│   └── FulfillmentStrategyInterface.php    # code / execute / cancel
├── Models/
│   ├── Fulfillment.php                     # per-order fulfillment row (status machine)
│   ├── FulfillmentRoute.php                # tenant-level routing rule (priority + condition columns)
│   └── FulfillmentDefault.php              # service-mapped fallbacks (per country / merchant type)
├── Services/
│   ├── FulfillmentService.php              # orchestrator (fulfill / retry / cancel)
│   └── FulfillmentRouter.php               # pure route-matching, no side effects
├── Strategies/
│   ├── WmsFulfillmentStrategy.php          # pick/pack via WMS
│   ├── ThreePlDropshipStrategy.php         # dispatch straight to a courier via Shipping module
│   └── MerchantSelfStrategy.php            # notify merchant, they handle it
├── Bridges/
│   └── OrderToParcelBridge.php             # translates Order → Parcel (idempotent via parcels.oms_order_id)
├── Events/
│   ├── FulfillmentRequested.php            # fired on create (before strategy runs)
│   ├── FulfillmentStarted.php              # strategy transitioned to in_progress
│   ├── FulfillmentCompleted.php            # terminal success
│   └── FulfillmentFailed.php               # strategy rejected / retries exhausted
├── Exceptions/
│   ├── FulfillmentException.php
│   └── StrategyRejectedException.php       # non-retryable validation failure
└── Listeners/
    └── RouteToFulfillmentListener.php      # OMS→Fulfillment bridge listener
```

---

## 3. Database

```
fulfillments                (per-order audit + status machine)
    id, company_id, order_id (FK orders), parcel_id (FK parcels, nullable),
    strategy (e.g. 'threepl_dropship'), route_id (FK fulfillment_routes, nullable),
    shipping_connection_id (FK shipping_connections, nullable),
    wms_fulfillment_id (FK wms_fulfillments, nullable),
    external_reference (e.g. AWB, WMS pick id),
    status (pending | in_progress | completed | failed | cancelled),
    last_error, started_at, completed_at, failed_at, timestamps
    INDEX (company_id, status)
    INDEX (order_id)

fulfillment_routes          (tenant routing rules)
    id, company_id, priority, is_active,
    condition_merchant_id (nullable),
    condition_country     (nullable),
    condition_source_channel (nullable — 'salla', 'zid', …),
    condition_min_amount  (nullable, decimal),
    strategy (must match a registered strategy code),
    strategy_config (json — e.g. shipping_connection_id for threepl_dropship)
    ORDER BY priority

fulfillment_defaults        (fallback when no route matches)
    id, company_id, applies_when (json), strategy, strategy_config (json)
```

Routes are ANDed: every non-null condition column must match the incoming Order. First match wins (lowest `priority` value first). No route matches → `FulfillmentService::resolveFallbackStrategy()` walks defaults → `config('fulfillment.default_strategy')` last.

---

## 4. Strategy contract

```php
interface FulfillmentStrategyInterface
{
    public function code(): string;                        // 'wms', 'threepl_dropship', 'vendor_direct', 'merchant_self'
    public function execute(Fulfillment $f, Order $o): void;  // may be sync or dispatch a queued job
    public function cancel(Fulfillment $f): void;              // some strategies can't cancel past a certain state → throw
}
```

Strategies **mutate the Fulfillment row directly** (transition to `in_progress` / `completed` / `failed`, populate strategy-specific FKs). `FulfillmentService` guards against double-execute on the same row so strategies don't need to.

**Sync vs async:**
- `MerchantSelfStrategy` — synchronous. Notifies the merchant, transitions to `in_progress` (they'll manually mark completed later).
- `ThreePlDropshipStrategy` — semi-async. Bridges Order→Parcel synchronously, then queues `CreateShipmentJob` from the Shipping module. Fulfillment sits in `in_progress` until a listener on `ShipmentCreated`/`ShipmentDelivered` rolls it forward.
- `WmsFulfillmentStrategy` — async. Creates a `WmsFulfillment` row, WMS pick job runs out of band, status callbacks transition the Fulfillment.

---

## 5. `FulfillmentService::fulfill($order)` — the pipeline

```
FulfillmentService::fulfill($order)
    ├─ IDEMPOTENCY: skip if a non-terminal Fulfillment already exists for this order
    ├─ router->route($order) → FulfillmentRoute or null
    ├─ if null: resolveFallbackStrategy() (FulfillmentDefault → config)
    ├─ create Fulfillment row (status=pending, route + strategy captured)
    ├─ write OrderEvent(type='fulfillment_requested', diff has route info)
    ├─ fire FulfillmentRequested
    ├─ strategy = router->strategyByCode($chosenStrategy)
    ├─ try:
    │      strategy->execute($fulfillment, $order)     // strategy mutates row
    │      based on new status: fire FulfillmentStarted / FulfillmentCompleted
    │  catch (StrategyRejectedException):
    │      stamp failed_at + last_error, fire FulfillmentFailed
```

Called from `RouteToFulfillmentListener`, which subscribes to `OMS\OrderReceived` (see `app/Providers/EventServiceProvider.php`).

**No route + no default = no fulfillment.** The Fulfillment row is *not* created. The Order sits in the OMS untouched; the operator has to add a route or manually assign. `Log::warning('fulfillment.no_route')` fires so this is visible.

---

## 6. `OrderToParcelBridge` — the Order → Parcel jump

Used by any strategy that shipments — currently `ThreePlDropshipStrategy`, later potentially WMS post-pick. Maps the canonical `OrderDTO` to a `Parcel` row on the existing legacy parcel model. Idempotent via `parcels.oms_order_id` unique key — bridging the same order twice returns the existing Parcel.

**Why bridge at all?** The rest of the app (bulk-actions, tracking, timelines, ATM/COD flows) is built around `Parcel`, not `Order`. The bridge preserves that surface. Long-term the Order model may absorb Parcel's role and the bridge disappears; today it keeps the OMS/Fulfillment work non-breaking.

---

## 7. Retry + cancel

`FulfillmentService::retry($fulfillment)`:
- Only allowed on `failed` fulfillments.
- Resets `status=pending`, clears `last_error`, `failed_at`.
- Re-runs `execute()`. Retains the original `route_id` — doesn't re-route.

`FulfillmentService::cancel($fulfillment)`:
- Only allowed on non-terminal statuses.
- Calls `strategy->cancel($fulfillment)`. Strategies may refuse (e.g. `ThreePlDropshipStrategy` can't cancel once the courier has picked up).
- On success: `status=cancelled`. Not automatically propagated to the linked Parcel — that's a separate cancel-shipment call.

---

## 8. Adding a strategy

1. `App\Fulfillment\Strategies\FooStrategy implements FulfillmentStrategyInterface` — declare `code()` and implement `execute()` + `cancel()`.
2. Register in `config/fulfillment.php`:
   ```php
   'strategies' => [
       'wms'              => \App\Fulfillment\Strategies\WmsFulfillmentStrategy::class,
       'threepl_dropship' => \App\Fulfillment\Strategies\ThreePlDropshipStrategy::class,
       'merchant_self'    => \App\Fulfillment\Strategies\MerchantSelfStrategy::class,
       'foo'              => \App\Fulfillment\Strategies\FooStrategy::class,
   ],
   ```
3. Tenants can now target it from `fulfillment_routes.strategy = 'foo'`.

No routing logic to change — the router already loops routes by priority regardless of which strategies exist.

---

## 9. Events (for downstream)

| Event | Fired when | Common listener |
|---|---|---|
| `FulfillmentRequested` | Fulfillment row just created, before strategy runs | audit / metrics |
| `FulfillmentStarted` | Strategy transitioned to `in_progress` | notify merchant "we've started" |
| `FulfillmentCompleted` | Terminal success | writeback to storefront via Commerce module's `pushOrderUpdate` |
| `FulfillmentFailed` | Strategy rejected or retries exhausted | notify ops, retry queue candidate |

None of these are currently wired to listeners — Phase 6 fulfillment ships with events + no subscribers. Adding storefront writeback on `FulfillmentCompleted` is the obvious next step.

---

## 10. Related docs

- [`OMS.md`](OMS.md) — where the Order comes from.
- [`COMMERCE.md`](COMMERCE.md) — the source of storefront writeback for `FulfillmentCompleted`.
- [`docs/shipping-architecture.md`](docs/shipping-architecture.md) — what `ThreePlDropshipStrategy` delegates to.
- WMS docs (in the KB app) — what `WmsFulfillmentStrategy` delegates to.
