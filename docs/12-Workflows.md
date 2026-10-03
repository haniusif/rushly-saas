# 12 — Operational Workflows

> **Scope:** Phase 10 end-to-end operational flows for the Rushly logistics platform, rendered as Mermaid diagrams and grounded in `rushly-saas` (the single source of truth). Flutter apps (`rushly-warehouse-app`, `rushly-sorting-app`, `rushly-driver-app`, …) are **clients** — they call the backend endpoints shown here; they never own business logic.
>
> Each workflow below cites the exact code that drives it. Where an existing repo-root doc conflicts with code, a **⚠️ Doc vs Code** note flags it.
>
> Sibling docs: [03-Business-Domain.md](03-Business-Domain.md) · [04-Business-Logic.md](04-Business-Logic.md) · [05-System-Architecture.md](05-System-Architecture.md) · [06-Database.md](06-Database.md) · [09-API.md](09-API.md). Repo-root primary sources: [`OMS.md`](../OMS.md), [`FULFILLMENT.md`](../FULFILLMENT.md), [`shipping-architecture.md`](shipping-architecture.md), [`ACCOUNTING.md`](../ACCOUNTING.md), [`COMMERCE.md`](../COMMERCE.md).

---

## 0. The two lifecycles (read this first)

Rushly runs **two orthogonal status machines** that must not be confused:

| Lifecycle | Enum | Values | Owns |
|---|---|---|---|
| **Commerce / Order** | `app/Oms/Enums/OrderStatus.php` | `pending → confirmed → in_fulfillment → shipped → delivered` (+ `cancelled`, `returned`) | Merchant-side order lifecycle. 7 string states. |
| **Courier / Parcel** | `app/Enums/ParcelStatus.php` | 41 integer constants (`PENDING=1 … CANCELLED=41`) | Physical parcel movement through hubs, drivers, WMS, 3PL. |

One OMS `Order` can produce 0..N `Parcel`s. The bridge between them is `app/Fulfillment/Bridges/OrderToParcelBridge.php` (idempotent via `parcels.oms_order_id`). Companion aggregate states live in `app/Oms/Enums/FulfillmentStatus.php` (`unfulfilled/in_progress/partial/fulfilled/cancelled`) and `app/Oms/Enums/PaymentStatus.php` (`pending/paid/partially_paid/refunded/voided/unknown`).

WMS work has **its own** status enum (`app/Enums/Wms/FulfillmentStatus.php`: `pending/picking/packing/ready/dispatched/cancelled`) that is distinct again from the OMS-level `FulfillmentStatus`.

```mermaid
flowchart LR
    subgraph Commerce["Commerce plane — OrderStatus (string)"]
        O1[pending] --> O2[confirmed] --> O3[in_fulfillment] --> O4[shipped] --> O5[delivered]
    end
    subgraph Courier["Courier plane — ParcelStatus (int 1-41)"]
        P1[PENDING=1] --> P2[WMS/3PL states] --> P3[DELIVERY_MAN_ASSIGN=7] --> P4[DELIVERED=9]
    end
    Commerce -. OrderToParcelBridge .-> Courier
```

---

## 1. Order flow (storefront → canonical Order → fulfillment)

**Modules:** `app/Commerce/` → `app/Oms/` → `app/Fulfillment/`. Feature-gated behind `config('features.commerce_layer')` (default off — `config/features.php`).

A storefront webhook (first provider: Salla) is ingested, normalized into a canonical `orders` row, and an `OrderReceived` event triggers the fulfillment router. Source: `OMS.md`, `app/Oms/Services/OrderService.php`, `app/Fulfillment/Listeners/RouteToFulfillmentListener.php`.

```mermaid
sequenceDiagram
    participant SF as Storefront (Salla)
    participant WH as Commerce WebhookIngestService
    participant NM as OrderNormalizer
    participant OS as OrderService
    participant DB as orders / order_items / order_events
    participant EB as Event Bus
    participant RL as RouteToFulfillmentListener
    participant FS as FulfillmentService

    SF->>WH: POST webhook (order.created)
    WH->>NM: normalize(RawOrderDTO, connection)
    NM->>NM: PayloadValidator::assert()
    NM->>NM: SallaOrderMapper::map() → OrderDTO
    NM->>NM: AddressResolver::hydrate() (local city/area)
    NM-->>OS: OrderDTO (canonical)
    OS->>DB: lookup by (connection_id, remote_order_id)
    alt new order
        OS->>DB: INSERT order + items + OrderEvent(received)
        OS->>EB: fire OrderReceived
    else exists + changed
        OS->>DB: UPDATE + OrderEvent(updated, diff_json)
        OS->>EB: fire OrderUpdated (no fulfillment)
    else exists + no change
        OS-->>WH: no-op (idempotent)
    end
    EB->>RL: handle OrderReceived
    RL->>FS: fulfill(order)
```

**Key facts (grounded):**
- Idempotency linchpin: `UNIQUE (connection_id, remote_order_id)` on `orders` (`OMS.md` §3). Replayed webhooks no-op or update-with-diff.
- `OrderReceived` has two listeners in `EventServiceProvider`: `LogOrderReceivedListener` (audit, first) then `RouteToFulfillmentListener` (`OMS.md` §6).
- `RouteToFulfillmentListener::handle()` **never throws** — it swallows exceptions so a failed fulfillment doesn't fail the parent `IngestWebhookJob` and trigger pointless Salla retries (`app/Fulfillment/Listeners/RouteToFulfillmentListener.php`).
- `OrderUpdated` has **no listeners today** — storefront edits after creation are a manual ops task (`OMS.md` §6).

---

## 2. Fulfillment routing (which strategy handles this order)

`FulfillmentService::fulfill($order)` picks a strategy via `FulfillmentRouter`, creates a `fulfillments` audit row, and delegates. Source: `app/Fulfillment/Services/FulfillmentService.php`, `app/Fulfillment/Services/FulfillmentRouter.php`, `FULFILLMENT.md`.

```mermaid
flowchart TD
    A["fulfill(order)"] --> B{"Non-terminal Fulfillment<br/>already exists?"}
    B -->|yes| B1[Return existing — skip_duplicate]
    B -->|no| C["router->route(order)"]
    C --> D{"Route matched?"}
    D -->|yes| E["strategy = route.strategy"]
    D -->|no| F["resolveFallbackStrategy(order)"]
    F --> F1["1. Merchant services JSON → FulfillmentDefault mapping"]
    F1 --> F2["2. FulfillmentDefault.default_strategy"]
    F2 --> F3["3. config('fulfillment.default_strategy')"]
    F3 --> G{"strategy resolved?"}
    E --> H
    G -->|no| G1["OrderEvent(fulfillment_no_route)<br/>Log warning · return null"]
    G -->|yes| H["Create Fulfillment (status=pending)<br/>OrderEvent(fulfillment_requested)"]
    H --> I["fire FulfillmentRequested"]
    I --> J["strategy->execute(fulfillment, order)"]
    J --> K{"final status"}
    K -->|in_progress| L["fire FulfillmentStarted"]
    K -->|completed| M["fire FulfillmentCompleted"]
    J -.->|StrategyRejectedException| N["status=failed · stamp last_error<br/>fire FulfillmentFailed"]
```

**Router matching** (`FulfillmentRouter::matches()`): all non-null route conditions are AND'd; lowest `priority` wins. Conditions checked in code: `merchant_id`, `source_provider_code`, `shipping_city_id`, `shipping_country` (case-insensitive), `min_total`, `max_total`, `is_cod` (derived from `cod_amount > 0`).

⚠️ **Doc vs Code:** `FULFILLMENT.md` §3 lists route columns as `condition_merchant_id`, `condition_country`, `condition_source_channel`, `condition_min_amount`. The **actual** router (`FulfillmentRouter::matches()`) reads `merchant_id`, `shipping_country`, `source_provider_code`, `min_total`, and adds `shipping_city_id`, `max_total`, `is_cod` that the doc omits. **Code wins.**

⚠️ **Doc vs Code:** `FULFILLMENT.md` §5 shows `resolveFallbackStrategy()` walking `FulfillmentDefault → config`. The actual precedence (code) is: **(1)** merchant `services` JSON mapped through `FulfillmentDefault::strategyForMerchantServices()`, **(2)** `FulfillmentDefault::resolvedFor()->default_strategy`, **(3)** `config('fulfillment.default_strategy')`. Also, `strategyByCode()` resolves the class from `config('fulfillment.strategies.<code>.class')` — a nested `.class` key, not the flat map shown in `FULFILLMENT.md` §8.

### The three strategies

| Strategy | `code()` | Sync/async | What it does | Source |
|---|---|---|---|---|
| `MerchantSelfStrategy` | `merchant_self` | sync | Notify merchant, park `in_progress`; they mark done manually. Skips the Parcel bridge entirely. | `app/Fulfillment/Strategies/MerchantSelfStrategy.php` |
| `ThreePlDropshipStrategy` | `threepl_dropship` | semi-async | Bridge Order→Parcel, `ShipmentService::dispatchCreate()` queues `CreateShipmentJob`, park `in_progress`. | `app/Fulfillment/Strategies/ThreePlDropshipStrategy.php` |
| `WmsFulfillmentStrategy` | `wms` | async | Bridge Order→Parcel, create `WmsFulfillment` (status=pending), park `in_progress`. Warehouse ops drive it forward. | `app/Fulfillment/Strategies/WmsFulfillmentStrategy.php` |

**Terminal roll-forward gap:** All three strategies park at `in_progress`. `FULFILLMENT.md` §9 confirms **none of the `Fulfillment*` events have listeners yet** — the roll-forward from `in_progress → completed` (on `ShipmentDelivered` / WMS `dispatched`) is described as a "future Phase 7 listener" and is **Not yet wired in the current codebase**.

---

## 3. Shipment flow (3PL dispatch via the Shipping module)

`ThreePlDropshipStrategy` hands a `Parcel` to `ShipmentService::dispatchCreate()`, which pre-creates a `shipments` row (`state=pending`) and queues `CreateShipmentJob`. The job calls the provider (first: Logestechs) and fires `ShipmentCreated`. Source: [`shipping-architecture.md`](shipping-architecture.md) §6, `app/Shipping/Services/ShipmentService.php`, `app/Shipping/Jobs/CreateShipmentJob.php`.

```mermaid
sequenceDiagram
    participant ST as ThreePlDropshipStrategy
    participant SS as ShipmentService
    participant Q as Queue
    participant J as CreateShipmentJob
    participant F as ShippingProviderFactory
    participant P as LogestechsProvider
    participant API as Logestechs API
    participant DB as shipments
    participant EB as Event Bus

    ST->>ST: validate connection (exists, same tenant, status=active)
    ST->>SS: dispatchCreate(parcel, connection)
    SS->>SS: assert parcel.company_id == connection.company_id
    SS->>DB: SELECT by (parcel, connection)
    alt has remote_shipment_id
        DB-->>SS: existing (no-op)
    else new
        SS->>DB: INSERT shipments (state=pending)
        SS->>Q: dispatch CreateShipmentJob(id)
    end
    Q->>J: handle()
    J->>SS: executeCreate(shipment)
    SS->>F: forConnection(connection)
    F-->>SS: LogestechsProvider
    SS->>P: createShipment(ConnectionDTO, ShipmentDTO)
    P->>API: POST /ship/request/by-email
    API-->>P: {id, barcode, awb, ...}
    SS->>DB: UPDATE state=created, awb_number, response
    SS->>EB: fire ShipmentCreated

    Note over Q,J: retry 3x backoff [10,30,90]s<br/>ProviderRejected (4xx) = no retry
```

**Tenant safety** (`shipping-architecture.md` §9): every shipping table carries `company_id`; `dispatchCreate()` explicitly asserts `parcel.company_id === connection.company_id` and throws on mismatch. `ThreePlDropshipStrategy::execute()` independently re-checks cross-tenant and `status === 'active'` before dispatch.

---

## 4. Tracking sync → parcel status bridge

A cron dispatches one `SyncTrackingJob` per active connection every 5 minutes; each polls open shipments and fires `ShipmentStatusChanged` / `ShipmentDelivered`, which `UpdateParcelStatus` bridges back to the legacy `Parcel`. Source: `app/Console/Commands/ShippingSyncTracking.php`, `app/Shipping/Services/TrackingService.php`, `app/Shipping/Listeners/UpdateParcelStatus.php`.

```mermaid
sequenceDiagram
    participant Cron as "*/5 * * * *  shipping:sync-tracking"
    participant Cmd as ShippingSyncTracking
    participant Q as Queue
    participant J as SyncTrackingJob
    participant TS as TrackingService
    participant P as Provider
    participant EB as Event Bus
    participant UPS as UpdateParcelStatus
    participant PR as ParcelRepository

    Cron->>Cmd: run
    Cmd->>Q: dispatch SyncTrackingJob per active connection
    Q->>J: handle()
    J->>TS: syncConnection(connection)
    TS->>P: getStatus(remote_id) per non-terminal shipment (LIMIT 200)
    P-->>TS: TrackingDTO(rawStatus, localStatus)
    TS->>EB: fire ShipmentStatusChanged (if changed)
    TS->>EB: fire ShipmentDelivered (if DELIVERED)
    EB->>UPS: handle ShipmentStatusChanged
    alt DELIVERED and parcel was DELIVERY_MAN_ASSIGN
        UPS->>PR: parcelDelivered(parcel_id) → money + notifications
    else other status
        UPS->>UPS: parcel.status = mapped local status
    end
```

Registered listeners (`shipping-architecture.md` §13): `ShipmentStatusChanged → [UpdateParcelStatus, StoreTrackingHistory]`; `ShipmentDelivered → SendShipmentNotifications`. `ShipmentCreated` and `ShipmentCancelled` have **no listeners registered yet**.

`UpdateParcelStatus::handle()` routes a `DELIVERED` transition through `ParcelRepository::parcelDelivered()` (not a raw `status` write) precisely so the accounting + notification side effects in §11/§13/§15 fire.

---

## 5. Receiving (WMS Goods Receipt Note / GRN)

Inbound merchant stock is booked as a `wms_grn` (draft), quantities entered per line, then **completed** — which credits stock. Source: `app/Http/Controllers/Backend/Wms/WmsGrnController.php`, `app/Repositories/Wms/WmsGrnRepository.php`, `app/Enums/Wms/GrnStatus.php`.

`GrnStatus`: `draft → in_progress → completed | discrepancy`.

```mermaid
stateDiagram-v2
    [*] --> draft: create() — GRN-YYYY-NNNNN, received_by=Auth::id()
    draft --> completed: complete() — all lines expected==received
    draft --> discrepancy: complete() — any line expected≠received
    completed --> [*]
    discrepancy --> [*]
    note right of draft
      destroy() allowed only while draft
      completed/discrepancy = immutable
    end note
```

**`complete()` logic** (`WmsGrnRepository::complete()`, wrapped in `DB::transaction`):
- Per line, if `expected_qty !== received_qty` → mark GRN `discrepancy`.
- **Damaged** condition (`ItemCondition::DAMAGED`, qty>0) → auto-create `WmsDamageReport` (cause `transit_damage`), **do not credit stock**.
- **Expired** condition → **do not credit**.
- Otherwise (`received_qty > 0`) → `WmsStockRepository::adjustStock(+received_qty, FIFO, batch/expiry/reason=OTHER)` — this is the **putaway credit** (§6).
- Stamp `received_at = now()`.

---

## 6. Putaway & inventory (WmsStock — reserve / release / adjust)

There is no separate "putaway" transaction: stock is credited **into a location** at GRN completion via `WmsStockRepository::adjustStock()`, and every quantity mutation fires `StockChanged` for storefront sync. Source: `app/Repositories/Wms/WmsStockRepository.php`, `app/Wms/Observers/WmsStockObserver.php`, `app/Wms/Events/StockChanged.php`.

```mermaid
flowchart LR
    subgraph Inbound
      GRN[GRN complete] -->|adjustStock +qty FIFO| STK[(wms_stock<br/>per product+location)]
      ADJ[WmsAdjustment] -->|+/- qty| STK
      CC[Cycle count correction] -->|delta| STK
    end
    subgraph Reservation
      PICK[Fulfillment enters PICKING] -->|reserve| STK
      DISP[Fulfillment dispatch] -->|release + adjustStock -picked| STK
    end
    STK -->|Eloquent observer<br/>created/updated/deleted| OBS[WmsStockObserver]
    OBS -->|product total qty across all locations| SC[[StockChanged event]]
    SC -.->|Phase 7 PushStockToConnectedChannelsListener| SF[Connected storefronts]
```

**Stock semantics (grounded):**
- `WmsStock` is keyed per `(product_id, location_id)` with `quantity` and a reserved concept. Picking **reserves**; dispatch **releases then debits**.
- `WmsStockObserver` fires `StockChanged` on `updated` (quantity dirty), `created`, and `deleted`. It skips inactive products (`is_active=false`). It reports the **product's total across all locations**, not the per-location delta.
- `StockChanged` carries `total_qty` as sellable stock — **reserved qty is intentionally excluded** (`StockChanged` docblock notes this is an "aggressive" oversell tradeoff deferred to Phase 7.5).
- `PickingStrategy` enum (`FIFO`, `FEFO`, `LIFO`) governs which stock rows are consumed; GRN credit uses `FIFO`, dispatch debit uses `FEFO` (`WmsFulfillmentRepository::dispatch()`).
- Adjustment reasons: `damage / count_correction / expiry / theft / system_error / other` (`app/Enums/Wms/AdjustmentReason.php`).

⚠️ **Doc vs Code:** The `StockChanged` docblock references a *Phase 7* `Commerce\PushStockToConnectedChannelsListener` / `PushStockJob` and `SupportsInventorySync`. Verify before relying on storefront push — the listener wiring is described as forward-looking and may be **Not yet wired in the current codebase**.

---

## 7. Picking (WmsFulfillment: pending → picking → packing)

Warehouse picker walks the warehouse one item at a time (location-code order). Entering picking **reserves** stock; all-picked auto-advances to packing. Source: `app/Http/Controllers/Backend/Wms/WmsFulfillmentController.php`, `app/Repositories/Wms/WmsFulfillmentRepository.php`.

```mermaid
sequenceDiagram
    participant WA as Warehouse app (Pick&Pack tab)
    participant FC as WmsFulfillmentController
    participant FR as WmsFulfillmentRepository
    participant STK as WmsStockRepository
    participant PR as ParcelRepository (statusUpdate)

    Note over FC: fulfillment created at status=pending<br/>parcel → WMS_FULFILLMENT_PENDING (37)
    WA->>FC: GET picking/{id} — next pending/short item (location order)
    WA->>FC: POST confirmPick {item_id, picked_qty}
    FC->>FR: confirmPick(f, userId, picks)
    alt first pick (was pending)
        FR->>STK: reserve(product, location, qty_required) per item
        FR->>FR: status=picking, picker_id=userId
        FR->>PR: parcel → WMS_PICKING (38) + ParcelEvent
    end
    FR->>FR: item.quantity_picked=min(picked,required)
    FR->>FR: item.status = picked | short
    alt all items picked
        FR->>FR: status=packing, picked_at=now
        FR->>PR: parcel → WMS_PACKING (39) + ParcelEvent
    end
    FR-->>WA: still picking → next item · else → packing
```

Reservation is best-effort: a `reserve()` failure is caught and the item is later flagged `short` (`quantity_picked < quantity_required`). SLA deadline is stamped at creation: `now()->addHours(config wms_sla_hours ?? 24)` (`WmsFulfillmentRepository::slaHours()`, read from `Config` key `wms_sla_hours`).

---

## 8. Packing & dispatch/loading (packing → ready → dispatched)

Packing confirmation flips to `ready`; dispatch consumes reserved stock, marks `dispatched`, and **hands the parcel to the courier workflow** (`DELIVERY_MAN_ASSIGN`). Source: `WmsFulfillmentRepository::confirmPack()` / `dispatch()`.

```mermaid
stateDiagram-v2
    pending --> picking: confirmPick (reserve stock) · parcel WMS_PICKING(38)
    picking --> packing: all items picked · parcel WMS_PACKING(39)
    packing --> ready: confirmPack (packer_id, packed_at) · parcel WMS_READY_TO_SHIP(40)
    ready --> dispatched: dispatch() · parcel DELIVERY_MAN_ASSIGN(7)
    picking --> cancelled: cancel (if picking not started downstream)
    pending --> cancelled
    note right of ready
      dispatch(): per item
      release(reserved) then
      adjustStock(-picked, FEFO)
      → real stock debit
      dispatched_at = now()
    end note
```

`WmsFulfillmentStrategy` maps WMS status back to the OMS `Fulfillment` row via `wms_fulfillment_id`; its `cancel()` refuses if the `WmsFulfillment` is already `dispatched` (`app/Fulfillment/Strategies/WmsFulfillmentStrategy.php`). "Loading" onto a vehicle is not a distinct DB state — dispatch directly transitions the parcel to `DELIVERY_MAN_ASSIGN`, from which the courier plane takes over.

---

## 9. Sorting (sorting center → hub transfer)

The `rushly-sorting-app` scans AWBs and hands parcels over to a destination hub in bulk. Bags/routes are **device-side ephemeral buckets**; only lookup + handover hit the server. Source: `app/Http/Controllers/Api/V10/Admin/AdminSortingController.php`, `rushly-sorting-app/lib/features/sorting/data/sorting_repository.dart`, `routes/api.php:170-172`.

```mermaid
sequenceDiagram
    participant SA as Sorting app (Scan In / Sort / Bags / Routes)
    participant API as AdminSortingController
    participant DB as parcels / parcel_events

    SA->>API: GET /admin/sorting/lookup/{tracking}
    API-->>SA: {parcel: id, status, current_hub, destination_hub, cash_collection}
    Note over SA: parcels bucketed into bags/routes locally
    SA->>API: GET /admin/sorting/hubs (destination picker)
    SA->>API: POST /admin/sorting/handover {parcel_ids[], destination_hub_id, note?}
    API->>API: HUB/INCHARGE users scoped to own hub_id
    API->>DB: per parcel: transfer_hub_id=dest, status=TRANSFER_TO_HUB(6)
    API->>DB: ParcelEvent(TRANSFER_TO_HUB) per parcel
    API-->>SA: {updated: N, destination_hub_id}
```

**Access control (grounded):** `UserType::HUB` and `UserType::INCHARGE` with a `hub_id` may only hand over parcels currently in their own hub (`$q->where('hub_id', $user->hub_id)`). Handover writes are wrapped in `DB::transaction`.

---

## 10. Delivery (courier plane) & the parcel status machine

Once dispatched/assigned, the parcel moves through the 41-state `ParcelStatus` machine driven by `app/Repositories/Parcel/ParcelRepository.php` and the driver/supervisor apps. `parcelDelivered()` is the money-moving terminal (§11). Source: `app/Enums/ParcelStatus.php`, `app/Repositories/Parcel/ParcelRepository.php`, `ACCOUNTING.md` §4.4.

```mermaid
flowchart TD
    P1[PENDING=1] --> P2[PICKUP_ASSIGN=2]
    P2 --> P4[RECEIVED_BY_PICKUP_MAN=4]
    P4 --> P5[RECEIVED_WAREHOUSE=5]
    P5 --> P6[TRANSFER_TO_HUB=6]
    P6 --> P19[RECEIVED_BY_HUB=19]
    P19 --> P7[DELIVERY_MAN_ASSIGN=7]
    P7 --> P9[DELIVERED=9]
    P7 --> P8[DELIVERY_RE_SCHEDULE=8]
    P7 --> P32[PARTIAL_DELIVERED=32]
    P7 --> NDR[NDR_CREATED=35]
    P9 --> DONE([settlement — see §11])

    P7 -.WMS path.-> W37[WMS_FULFILLMENT_PENDING=37]
    W37 --> W38[WMS_PICKING=38] --> W39[WMS_PACKING=39] --> W40[WMS_READY_TO_SHIP=40] --> P7

    P7 -.3PL path.-> P34[ASSIGN_TO_3PL=34]
    P34 -.tracking sync.-> P9

    classDef term fill:#1b5e20,color:#fff
    class P9 term
```

Non-happy-path branches all exist as first-class constants: reschedules (`3,8,18,27`), cancels (each state has a paired `_CANCEL`), abnormal (`ABNORMAL=36`), and the full return sub-machine (§12).

---

## 11. Settlement / parcel-driven accounting (on `delivered`)

Delivery is where most money moves. `ParcelRepository::parcelDelivered()` (≈lines 2015–2130) writes to all three accounting layers atomically. Source: `ACCOUNTING.md` §4.4 + §7, `app/Repositories/Parcel/ParcelRepository.php`.

```mermaid
flowchart TD
    D["parcel → DELIVERED (9)"] --> A1["deliveryman_statements INCOME +delivery_charge<br/>delivery_man.current_balance += charge"]
    A1 --> A2["courier_statements EXPENSE -charge (pay driver)"]
    A2 --> A3{"cash_collection > 0?"}
    A3 -->|yes| A4["deliveryman_statements EXPENSE (cash_collection=1) -cash<br/>driver now holds cash on courier's behalf"]
    A4 --> A5["merchant_statements INCOME +cash<br/>merchants.current_balance += cash"]
    A3 -->|no| A5
    A5 --> A6["merchant_statements EXPENSE -total_delivery_amount<br/>+ VAT EXPENSE -vat_amount"]
    A6 --> A7["merchants.current_balance -= (charge + vat)"]
    A7 --> A8["courier_statements INCOME +total_delivery_amount"]
    A8 --> A9["vat_statements INCOME +vat_amount"]
```

**Net merchant delta = `cash_collection − total_delivery_amount − vat_amount`** = what the courier owes the merchant for this parcel.

Three-layer model (`ACCOUNTING.md` §1): **Layer 3** mutable running balances (`merchants/delivery_man/hubs.current_balance`, `accounts.balance`) · **Layer 2** append-only statement ledgers (`*_statements`, each row `type = INCOME(1) | EXPENSE(2)` per `StatementType`) · **Layer 1** cash (`accounts` + immutable `bank_transactions`). The system is **not double-entry** — balances are maintained by application increments; a missed write path drifts.

---

## 12. Return flow

Undeliverable/refused parcels enter the return sub-machine (`ParcelStatus` 11–13, 24–31) and/or the NDR workflow. Source: `app/Enums/ParcelStatus.php`, `app/Enums/NdrStatus.php`, `app/Enums/NdrAction.php`, `ACCOUNTING.md` §4.4 (return handlers).

```mermaid
stateDiagram-v2
    direction LR
    delivery_fail --> NDR_CREATED: NDR_CREATED (35)
    NDR_CREATED --> reschedule: NdrAction.reschedule → DELIVERY_RE_SCHEDULE (8)
    NDR_CREATED --> transfer: NdrAction.transfer_hub
    NDR_CREATED --> escalate: NdrAction.escalate
    NDR_CREATED --> return_flow: NdrAction.return_to_merchant

    return_flow --> RETURN_WAREHOUSE: RETURN_WAREHOUSE (11)
    RETURN_WAREHOUSE --> RETURN_TO_COURIER: (24)
    RETURN_TO_COURIER --> ASSIGN_MERCHANT: RETURN_ASSIGN_TO_MERCHANT (26)
    ASSIGN_MERCHANT --> RETURNED_MERCHANT: RETURN_RECEIVED_BY_MERCHANT (30)
    RETURNED_MERCHANT --> [*]
```

- **NDR** (`ndr_status`): `open → in_progress → resolved | returned`. Actions (`NdrAction`): `reschedule / return_to_merchant / transfer_hub / escalate`. Additional enums: `NdrFailureReason`.
- **WMS returns**: `WmsOutbound` supports `OutboundType::RETURN_TO_MERCHANT` for warehouse-side merchant return processing (`app/Enums/Wms/OutboundType.php`, `app/Repositories/Wms/WmsOutboundRepository.php`).
- **Accounting**: return / partial-delivered / return-to-merchant follow analogous per-party ledger patterns in the same `ParcelRepository` (`ACCOUNTING.md` §4.4 closing note); return charges feed the invoice (§14).

---

## 13. Cash settlement chain (driver → hub → bank)

Collected COD cash walks up the chain, each hop clearing the prior party's held balance. Source: `ACCOUNTING.md` §4.5 + §7, `app/Repositories/CashReceivedFromDeliveryman/ReceivedRepository.php`, `app/Repositories/HubManage/HubPayment/HubPaymentRepository.php`.

```mermaid
sequenceDiagram
    participant DRV as Driver (holds COD)
    participant HUB as Hub (ReceivedRepository)
    participant BANK as Bank account (HubPayment)

    DRV->>HUB: hand over cash
    Note over HUB: hubs.current_balance -= amount (hub now holds — inverted sign)<br/>deliveryman_statements +amount · driver.current_balance += amount<br/>bank_transactions recorded
    HUB->>BANK: deposit
    Note over BANK: accounts.balance += amount<br/>bank_transactions recorded
```

⚠️ **Gotcha (from `ACCOUNTING.md` §8):** `hubs.current_balance` is **inverted** — it *increases* when cash flows out (bank deposit) and *decreases* when cash is received from a driver. Read it as "amount the hub owes the company."

---

## 14. Invoice generation (settlement snapshot)

The scheduled `invoice:generate` command (`app/Console/Commands/Invoice.php`) snapshots each merchant's billable parcels into an `invoices` row. It is **read-only against balances** — it moves no money. Source: `ACCOUNTING.md` §4.9, `app/Repositories/Invoice/InvoiceRepository.php`, `app/Enums/InvoiceStatus.php`.

```mermaid
flowchart TD
    C["invoice:generate (scheduled)"] --> P{"merchant payment_period elapsed?"}
    P -->|yes| S["Pull DELIVERED + partial_delivered=YES<br/>+ return parcels with invoice_id=null"]
    S --> SUM["Sum: collected, delivery_charge, vat, return_charges"]
    SUM --> CALC["current_payable = (collected − charges − vat) − return_charges"]
    CALC --> INS["INSERT invoices (status=PROCESSING=2)<br/>stamp parcel.invoice_id on each"]
```

`InvoiceStatus`: `UNPAID=0`, `PROCESSING=2`, `PAID=3` (note: no `1`). Invoice **payment** happens separately via the merchant-payout flow (§15).

---

## 15. Payment / merchant payout

Merchant payouts run through `PaymentRepository::store()` (not the generic expense head). Online gateways are also available via `PayoutController`. Source: `ACCOUNTING.md` §4.6 + §5, `app/Repositories/MerchantManage/Payment/PaymentRepository.php`, `app/Enums/PaymentType.php`, `app/Enums/PayoutSetup.php`.

```mermaid
flowchart TD
    R["PaymentRepository::store(payment)"] --> M{"mode"}
    M -->|Pending| P1["INSERT payments (status=PENDING)<br/>no balance moves"]
    M -->|Processed / isprocess=true| P2["INSERT payments (PROCESSED)"]
    P2 --> P3["merchant_statements EXPENSE 'payment_withdrawal'<br/>merchants.current_balance -= amount"]
    P3 --> P4["bank_transactions EXPENSE on from_account<br/>accounts.balance -= amount"]
    P1 -.later.-> P2
```

- **Gateways** (`PayoutSetup`): Stripe, SSLCommerz, PayPal, Payoneer, bKash, Visa, Skrill, AamarPay, Razorpay, Paystack, Offline. Inbound gateway receipts land in `merchant_online_payments` / `_receiveds` (`ACCOUNTING.md` §2.3).
- **Account-head hardcoding gotcha** (`ACCOUNTING.md` §3): head IDs 1–7 are positional and hardcoded in `if ($request->account_head_id == 4)` style checks. Head 4 ("Payment paid to merchant") is seeded **inactive** precisely because payouts run through `PaymentRepository` instead. Do not reorder `AccountHeadSeeder`.

---

## 16. Notification workflow

Notifications are a **side-effect layer**, not a state machine. Terminal parcel/shipment transitions call into the push + SMS pipelines, gated by per-parcel `send_sms_*` flags. Source: `app/Http/Services/PushNotificationService.php`, `app/Http/Services/SmsService.php`, `app/Shipping/Listeners/SendShipmentNotifications.php`, `app/Enums/SmsSendStatus.php`.

```mermaid
flowchart LR
    subgraph Triggers
      T1[parcelDelivered / status change] 
      T2[ShipmentDelivered event]
      T3[Manual admin push]
    end
    T1 --> G{"send_sms_customer / send_sms_merchant flags"}
    G -->|on| SMS[SmsService::sendSms — multi-gateway]
    T1 --> PUSH[PushNotificationService — FCM topic]
    T2 --> HOOK[SendShipmentNotifications — log-only hook today]
    T3 --> PUSH
    PUSH --> FCM[["FCM /topics/{fcm_topic}_{recipient}"]]
    SMS --> GW[["Twilio · Taqnyat · Msegat · 4jawaly · Unifonic · Nexmo · Reve"]]
```

**Grounded facts:**
- `PushNotificationService::sendPushNotification()` posts to FCM legacy HTTP (`https://fcm.googleapis.com/fcm/send`) with a topic derived from `notificationSettings()->fcm_topic` + sanitized recipient.
- `SmsService` is multi-gateway (`sendSms()` dispatches to `reveSms/twilioSms/taqnyatSms/msegatSms/jawaly4Sms/unifonicSms/nexmoSms`, plus `sendOtp()`); status tracked via `SmsSendStatus` / `SmsSetup`.
- `SendShipmentNotifications` (on `ShipmentDelivered`) is **log-only today** — it defers real SMS/email to `ParcelRepository::parcelDelivered`'s existing `send_sms_*` handling and is reserved for future shipping-specific notifications (`app/Shipping/Listeners/SendShipmentNotifications.php` docblock).

⚠️ **Doc vs Code:** the FCM integration uses the **legacy** `fcm/send` + server-key API, which Google has deprecated in favor of FCM HTTP v1. Present in code as legacy; flag for modernization.

---

## 17. End-to-end: one order, both planes

```mermaid
flowchart TD
    SF[Salla webhook] --> OMS[OMS Order — pending/confirmed]
    OMS -->|OrderReceived| RT[FulfillmentRouter]
    RT -->|wms| WMS[WmsFulfillment pending]
    RT -->|threepl_dropship| SHIP[ShipmentService → CreateShipmentJob]
    RT -->|merchant_self| MS[Notify merchant]

    WMS --> WPICK[picking→packing→ready→dispatched]
    WPICK -->|parcel DELIVERY_MAN_ASSIGN| COUR

    SHIP -->|Parcel via bridge| COUR[Courier plane]
    COUR --> SORT[Sorting → TRANSFER_TO_HUB]
    SORT --> HUBIN[RECEIVED_BY_HUB → DELIVERY_MAN_ASSIGN]
    HUBIN --> DEL{Delivered?}
    DEL -->|yes| MONEY[parcelDelivered → 3-layer accounting]
    DEL -->|no| NDR[NDR → reschedule / return]
    MONEY --> CASH[Driver→Hub→Bank cash chain]
    CASH --> INV[invoice:generate snapshot]
    INV --> PAYOUT[PaymentRepository merchant payout]
    MONEY -.-> NOTIF[Push + SMS notifications]
```

---

## Sources

**Repo-root & docs primary sources**
- [`OMS.md`](../OMS.md) · [`FULFILLMENT.md`](../FULFILLMENT.md) · [`shipping-architecture.md`](shipping-architecture.md) · [`ACCOUNTING.md`](../ACCOUNTING.md) · [`COMMERCE.md`](../COMMERCE.md) · `docs/_CONTEXT_BRIEF.md`

**Fulfillment** (`app/Fulfillment/`)
- `Services/FulfillmentService.php`, `Services/FulfillmentRouter.php`
- `Strategies/{WmsFulfillmentStrategy,ThreePlDropshipStrategy,MerchantSelfStrategy}.php`
- `Bridges/OrderToParcelBridge.php`, `Listeners/RouteToFulfillmentListener.php`, `Contracts/FulfillmentStrategyInterface.php`

**OMS** (`app/Oms/`)
- `Enums/{OrderStatus,FulfillmentStatus,PaymentStatus}.php`, `Services/OrderService.php` (per `OMS.md`)

**Shipping** (`app/Shipping/`)
- `Services/ShipmentService.php`, `Services/TrackingService.php`, `Jobs/CreateShipmentJob.php`, `Jobs/SyncTrackingJob.php`
- `Listeners/{UpdateParcelStatus,SendShipmentNotifications,StoreTrackingHistory}.php`, `Events/*`
- `app/Console/Commands/ShippingSyncTracking.php`

**WMS** (`app/Wms/`, `app/Models/Backend/Wms/`, `app/Repositories/Wms/`, `app/Enums/Wms/`)
- `Observers/WmsStockObserver.php`, `Events/StockChanged.php`
- `Http/Controllers/Backend/Wms/{WmsGrnController,WmsFulfillmentController,WmsOutboundController}.php`
- `Repositories/Wms/{WmsGrnRepository,WmsFulfillmentRepository,WmsOutboundRepository,WmsStockRepository}.php`
- `Models/Backend/Wms/{WmsFulfillment,WmsGrn,WmsOutbound}.php`
- `Enums/Wms/{GrnStatus,FulfillmentStatus,OutboundType,PickingStrategy,AdjustmentReason,ItemCondition,LocationType}.php`

**Courier / status machines** (`app/Enums/`)
- `ParcelStatus.php`, `NdrStatus.php`, `NdrAction.php`, `InvoiceStatus.php`, `PaymentType.php`, `PayoutSetup.php`, `StatementType.php`, `PickupRequestType.php`

**Sorting**
- `app/Http/Controllers/Api/V10/Admin/AdminSortingController.php`, `routes/api.php` (sorting routes)
- `rushly-sorting-app/lib/features/sorting/data/sorting_repository.dart`, `.../core/api/api_endpoints.dart`

**Accounting / settlement** (per `ACCOUNTING.md`)
- `app/Repositories/Parcel/ParcelRepository.php` (parcelDelivered money moves)
- `app/Repositories/CashReceivedFromDeliveryman/ReceivedRepository.php`, `app/Repositories/Invoice/InvoiceRepository.php`
- `app/Repositories/MerchantManage/Payment/PaymentRepository.php`, `app/Console/Commands/Invoice.php`

**Notifications**
- `app/Http/Services/PushNotificationService.php`, `app/Http/Services/SmsService.php`
