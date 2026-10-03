# OMS — Order Management System

Canonical order data model. Sits between the storefront ingestion (Commerce module) and the fulfillment router. Every order — regardless of source — ends up as one `orders` row + N `order_items` + at least one `order_events` audit entry. Downstream systems (Fulfillment, dashboards, reports) query only this canonical shape.

- **Module root**: `app/Oms/`
- **Migrations**: `2026_07_01_110001_create_orders_table.php` + `2026_07_01_110002_create_order_items_table.php`
- **Events**: `OrderReceived` (new order landed), `OrderUpdated` (idempotent replay with real diff)
- **Feature flag**: shares `config('features.commerce_layer')` with the Commerce module.

---

## 1. Where it sits

```
Commerce module         →   OMS (this doc)         →   Fulfillment module
─────────────────────       ─────────────────────      ─────────────────────
RawOrderDTO from            OrderService              RouteToFulfillmentListener
storefront webhook          .receiveNormalized()      picks a strategy, creates
   ↓                            ↓                        Fulfillment row
OrderMapper (per-provider)  orders / order_items /
   → OrderDTO               order_events rows
   ↓                            ↓
OrderNormalizer             event: OrderReceived
   → OrderDTO                (or OrderUpdated)
```

**OMS never creates parcels.** That decision belongs to `RouteToFulfillmentListener`. OMS's job ends at "the canonical order exists and the world knows about it via an event."

---

## 2. Folder structure

```
app/Oms/
├── DTOs/
│   ├── OrderDTO.php               # canonical order shape
│   ├── OrderItemDTO.php
│   ├── OrderCustomerDTO.php
│   └── OrderAddressDTO.php
├── Enums/
│   ├── OrderStatus.php            # PENDING / CONFIRMED / SHIPPED / CANCELLED / …
│   ├── FulfillmentStatus.php      # UNFULFILLED / PARTIAL / FULFILLED / …
│   └── PaymentStatus.php          # UNPAID / PAID / REFUNDED / …
├── Events/
│   ├── OrderReceived.php          # new Order created
│   └── OrderUpdated.php           # existing Order updated with diff
├── Exceptions/
│   └── NormalizationException.php
├── Listeners/
│   └── LogOrderReceivedListener.php   # writes a diagnostic log row per receive
├── Models/
│   ├── Order.php
│   ├── OrderItem.php
│   └── OrderEvent.php             # audit trail per Order
├── Normalization/
│   ├── OrderNormalizer.php        # facade — picks the right per-provider mapper
│   ├── OrderMapperInterface.php   # per-provider contract
│   ├── PayloadValidator.php       # sanity checks the raw payload before mapping
│   ├── AddressResolver.php        # local city/area lookup from storefront address
│   └── Providers/
│       └── SallaOrderMapper.php   # first provider mapper (Salla)
├── Repositories/
│   └── OrderRepository.php
└── Services/
    └── OrderService.php           # the single entry point (receiveNormalized)
```

---

## 3. Database

```
orders                        (canonical order, per tenant)
    id, company_id, connection_id (FK commerce_connections),
    remote_order_id, merchant_id (nullable),
    order_status, payment_status, fulfillment_status,
    customer_name, customer_email, customer_phone,
    shipping_address_json, billing_address_json,
    subtotal, shipping_total, discount_total, tax_total, grand_total,
    currency, placed_at, timestamps
    UNIQUE (connection_id, remote_order_id)
    INDEX  (company_id, order_status)

order_items
    id, order_id (FK), sku, name, quantity,
    unit_price, line_total, meta_json, timestamps

order_events                  (audit trail)
    id, order_id (FK), type, note, diff_json,
    source_webhook_event_id (FK nullable), created_at
```

`UNIQUE (connection_id, remote_order_id)` is the linchpin — makes idempotent replay trivial. A webhook fired twice → same key → we see the existing row and either no-op (no changes) or update-with-diff.

---

## 4. Normalization pipeline

Two-step: `RawOrderDTO` → `OrderDTO`. First step is provider-specific (each storefront's raw shape is different), second is canonical.

```
Commerce webhook  → RawOrderDTO
                       ↓
OrderNormalizer::normalize($raw, $connection)
    ├─ resolve OrderMapperInterface via config('commerce.providers.<code>.order_mapper')
    ├─ PayloadValidator::assert($raw)              (throws NormalizationException on missing critical fields)
    ├─ mapper->map($raw, $connection) → OrderDTO
    └─ AddressResolver::hydrate($orderDto)         (local city_id / area_id lookup for shipping address)
                       ↓
                   OrderDTO (canonical)
                       ↓
OrderService::receiveNormalized($dto, $connection, $sourceEvent?)
```

**Why not just build the OrderDTO inside the provider?** Two reasons:
1. Providers can't be trusted to know the tenant's local city/area IDs — that lookup lives in `AddressResolver`, which is OMS-owned.
2. The `PayloadValidator` guards the whole pipeline centrally instead of relying on 5 different providers each implementing "am I missing anything?" correctly.

---

## 5. `OrderService::receiveNormalized`

Idempotent by `(connection_id, remote_order_id)`. The method:

```php
public function receiveNormalized(
    OrderDTO $dto,
    CommerceConnection $connection,
    ?WebhookEvent $sourceEvent = null,
): Order
```

Behavior:
1. Look up existing Order by `(connection_id, remote_order_id)`.
2. **If none**: insert Order + N OrderItems + one `OrderEvent(type=received)`. Fire `OrderReceived` event.
3. **If exists**: compute a diff between the persisted state and the new DTO. If empty → return existing Order, no event fired (idempotent no-op). If non-empty → update + insert `OrderEvent(type=updated, diff_json=…)` + fire `OrderUpdated` with the diff.
4. Wrapped in `DB::transaction()` so a partial write can't leave orphaned OrderItems.

**Why fire different events for received vs updated?** Downstream fulfillment logic only wants to run once per order (on receive). Storefront replay/edit webhooks shouldn't re-trigger fulfillment.

---

## 6. Listeners

Registered in `EventServiceProvider`:

```php
OrderReceived::class => [
    LogOrderReceivedListener::class,          // audit log: source + timing
    RouteToFulfillmentListener::class,        // FULFILLMENT.md — the real work
],
```

Listener order matters: log first so we have a diagnostic row even if routing throws. `LogOrderReceivedListener` is synchronous; `RouteToFulfillmentListener` may dispatch a queued job depending on the picked strategy.

**`OrderUpdated` has no listeners today.** Add one if you need to push storefront edits into an already-created Parcel (e.g. customer changed the shipping address before pickup — currently a manual ops task).

---

## 7. Reading orders

`OrderRepository` — thin. Public methods:

- `findByConnectionAndRemote(int $connectionId, string $remoteOrderId): ?Order`
- `findForCompany(int $id, int $companyId): ?Order` — tenant-scoped fetch
- `pendingForCompany(int $companyId, int $limit = 100)` — orders awaiting fulfillment
- `sinceForConnection(CommerceConnection $conn, DateTime $since)` — used by bulk-fetch backfill

Business code (dashboards, listeners) should call the repository, not raw Eloquent, so we can add cross-cutting filters later without hunting through call sites.

---

## 8. Non-goals

- **No parcel creation.** OMS ends at "canonical Order exists." Parcels are a Fulfillment concern.
- **No writeback to the storefront.** That's a Commerce provider concern (`pushOrderUpdate`), called by whichever downstream module owns the writeback trigger (e.g. FulfillmentService when the Fulfillment moves to `completed`).
- **No email/SMS.** Notifications belong to their existing pipelines; OMS is a data layer.

---

## 9. Related docs

- [`COMMERCE.md`](COMMERCE.md) — where `RawOrderDTO` comes from.
- [`FULFILLMENT.md`](FULFILLMENT.md) — what happens on `OrderReceived`.
- [`ARCHITECTURE.md`](ARCHITECTURE.md) — codebase-wide context.
