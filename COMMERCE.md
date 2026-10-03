# Commerce Module

Generic, multi-tenant abstraction for talking to storefronts (Salla, Shopify, Zid, WooCommerce, …). Sits between the storefront and the OMS: it owns credentials, receives orders, and pushes inventory + order updates back. **Storefront + OMS are strictly decoupled** — new providers plug in without touching business logic.

- **Module root**: `app/Commerce/`
- **Config**: `config/commerce.php`
- **Migrations**: `2026_06_30_140001_create_commerce_providers_table.php` … `2026_07_01_100001_add_normalized_payload_to_webhook_events.php`
- **Service provider**: `App\Commerce\CommerceServiceProvider` (registered in `config/app.php`)
- **First provider**: Salla (`App\Commerce\Providers\Salla\`)
- **Feature flag**: `config('features.commerce_layer')` gates user-visible behavior (the module itself loads unconditionally, but no routes/commands run until the flag is on).

---

## 1. Where it sits

```
┌──────────────────────────────────────────────────────────────┐
│ Storefront (Salla / Shopify / Zid / WooCommerce)             │
└───────────┬──────────────────────────────────┬───────────────┘
            │ webhooks / OAuth                 │ writeback / stock push
┌───────────▼──────────────────────────────────▼───────────────┐
│ Commerce module (this doc)                                    │
│   CommerceConnection (credentials)                            │
│   CommerceProviderInterface (contract)                        │
│   WebhookIngestService → IngestWebhookJob                     │
│   PushStockJob                                                │
│   ApiLogger → commerce_api_logs                               │
└───────────┬──────────────────────────────────────────────────┘
            │ RawOrderDTO → OrderNormalizer → OrderDTO
┌───────────▼──────────────────────────────────────────────────┐
│ OMS (see OMS.md) — canonical orders + order_events           │
└──────────────────────────────────────────────────────────────┘
            │ event: OrderReceived
┌───────────▼──────────────────────────────────────────────────┐
│ Fulfillment (see FULFILLMENT.md) — routes to a strategy      │
└──────────────────────────────────────────────────────────────┘
```

The Commerce module **never** creates parcels, decides fulfillment, or updates WMS stock directly. It normalizes raw input to `RawOrderDTO`, hands off to OMS, and dispatches provider-side outputs (stock pushes, order writebacks). Symmetric to how the Shipping module never touches parcel status — that's the listener's job.

---

## 2. Folder structure

```
app/Commerce/
├── CommerceServiceProvider.php          # binds factory + logger + repo
├── Contracts/
│   ├── CommerceProviderInterface.php    # code / testConnection / authenticate / fetchOrder / pushOrderUpdate
│   ├── SupportsOAuth.php                # OAuth-based providers
│   ├── SupportsWebhooks.php             # push-based providers (Salla)
│   ├── SupportsBulkFetch.php            # can list orders in bulk (backfill)
│   ├── SupportsOrderWriteback.php       # can push status/tracking back
│   └── SupportsInventorySync.php        # accepts stock updates
├── DTOs/
│   ├── CommerceConnectionDTO.php        # snapshot of a connection at call time
│   ├── RawOrderDTO.php                  # raw provider payload + metadata (pre-normalization)
│   ├── WebhookEventDTO.php              # parsed inbound webhook envelope
│   └── TestResultDTO.php
├── Exceptions/                          # CommerceException + Provider{Rejected,Unavailable} + ConnectionTestFailed
├── Factory/
│   └── CommerceProviderFactory.php      # resolve by code (config/commerce.php driven)
├── Jobs/
│   ├── IngestWebhookJob.php             # per-event: parse → handler → OrderService
│   └── PushStockJob.php                 # per-connection: push one product's stock
├── Listeners/
│   └── PushStockToConnectedChannelsListener.php   # fan-out on WMS StockChanged
├── Logging/
│   └── ApiLogger.php                    # writes commerce_api_logs
├── Models/
│   ├── CommerceProvider.php             # catalog: id/code/name/supports[]
│   ├── CommerceConnection.php           # per-tenant credentials (OAuth token, API key, webhook secret)
│   ├── CommerceApiLog.php               # ring buffer, pruned by shipping:prune-logs sibling
│   └── WebhookEvent.php                 # inbound webhook envelope + normalized payload
├── Providers/
│   ├── AbstractCommerceProvider.php     # shared HTTP + auto-logging + retry
│   └── Salla/
│       ├── SallaProvider.php            # implements the full contract
│       └── SallaWebhookHandler.php      # parses Salla webhook → RawOrderDTO
├── Repositories/
│   └── CommerceConnectionRepository.php
├── Services/
│   ├── ConnectionService.php            # create / test / rotate tokens
│   └── WebhookIngestService.php         # verifies signature → persists WebhookEvent → dispatches IngestWebhookJob
└── Webhooks/
    └── HandlerInterface.php             # provider-specific webhook parser contract
```

---

## 3. Database

```
commerce_providers        (central catalog — seeded)
    id, code UK, name, logo_url, status, supports (json)

commerce_connections      (per-tenant credentials)
    id, company_id, provider_id,
    connection_name, remote_store_id, domain, merchant_id,
    access_token_encrypted, refresh_token_encrypted, token_expires_at,
    api_key_encrypted, api_secret_encrypted, webhook_secret_encrypted,
    settings (json), status, is_default,
    last_tested_at, last_sync_at, last_event_at

commerce_api_logs         (ring buffer, ~30 days, pruned daily)
    id, company_id, connection_id, provider_code, endpoint, method,
    request_headers (json, sensitive-masked), request_body,
    response_status, response_body, duration_ms, error, created_at

webhook_events            (inbound audit trail + normalized payload)
    id, company_id, connection_id, provider_code, event_type,
    remote_event_id, headers (json), raw_payload, normalized_payload,
    signature, verified, processed_at, error, received_at
```

Every column is scoped by `company_id` (matches codebase convention). `commerce_connections` has UNIQUE `(company_id, provider_id, connection_name)` and INDEX `(company_id, is_default)`.

---

## 4. Provider contract

```php
interface CommerceProviderInterface
{
    public function code(): string;
    public function testConnection(CommerceConnectionDTO $c): TestResultDTO;
    public function authenticate(CommerceConnectionDTO $c): CommerceConnectionDTO; // OAuth refresh lives here
    public function fetchOrder(CommerceConnectionDTO $c, string $remoteOrderId): RawOrderDTO;
    public function pushOrderUpdate(CommerceConnectionDTO $c, string $remoteOrderId, array $update): void;
}
```

Capability variance is wider than the Shipping module (some providers only push, some only pull, some don't accept writeback). Providers opt into extra behavior via **marker interfaces**:

| Marker | Purpose |
|---|---|
| `SupportsOAuth` | Provider requires the OAuth dance; enables the `/oauth/callback` route |
| `SupportsWebhooks` | `verifyWebhook(Request)` + `handleWebhook(Request): WebhookEventDTO` |
| `SupportsBulkFetch` | `fetchOrdersSince(DateTime): iterable` — backfill on connect |
| `SupportsOrderWriteback` | `pushOrderUpdate()` is safe to call |
| `SupportsInventorySync` | Accepts stock pushes from WMS `StockChanged` events |

`CommerceProvider::supports` json column mirrors these so the admin UI can render capability chips without instantiating the provider class.

---

## 5. Ingestion flow (webhook)

```
[Storefront sends webhook]
   ↓
POST /webhooks/commerce/{providerCode}      (routed to WebhookIngestController)
   ↓
WebhookIngestService::handle($providerCode, $request)
   ├─ resolve provider via CommerceProviderFactory
   ├─ verifyWebhook(request)                 (HMAC / signature check)
   ├─ persist WebhookEvent (raw payload + headers + signature)
   ├─ dispatch IngestWebhookJob(webhookEvent.id)
   └─ 200 OK (fast — heavy work is queued)
   ↓
IngestWebhookJob::handle()
   ├─ HandlerInterface::parse(WebhookEvent) → RawOrderDTO
   ├─ OrderNormalizer::normalize(RawOrderDTO) → OrderDTO       (OMS layer, see OMS.md §4)
   ├─ OrderService::receiveNormalized(OrderDTO, connection)    → Order
   └─ WebhookEvent::update({processed_at, normalized_payload})
   ↓
Order write triggers OrderReceived event → RouteToFulfillmentListener (FULFILLMENT.md)
```

**Failure paths:**
- Signature invalid → 401, event NOT persisted
- Parse fails → event persisted with `error` set, job fails, alerts on repeat
- Normalize fails → `NormalizationException`, event stays unprocessed
- OMS write fails → job retries with backoff

The `webhook_events` table is the audit trail. Re-processing = requeue a job with the same event id.

---

## 6. Inventory sync flow

```
[WMS stock row changes]
   ↓
WmsStockObserver → StockChanged event
   ↓
PushStockToConnectedChannelsListener::handle()
   ├─ for each active CommerceConnection where provider->supports 'inventory_sync'
   ├─ filter by merchant_id (products owned by merchant A don't leak to merchant B's connection)
   ├─ skip if product has no SKU
   └─ dispatch PushStockJob(connection_id, sku, quantity)
   ↓
PushStockJob::handle()
   ├─ provider = factory->forConnection(connection)
   ├─ provider->pushStock($connectionDto, $sku, $qty)
   └─ log to commerce_api_logs
```

Listener is synchronous (dispatching jobs is cheap). Provider HTTP work runs inside the job.

---

## 7. Adding a new provider

Same pattern as `Shipping` (see `docs/shipping-architecture.md` §8):

1. Implement `App\Commerce\Providers\Foo\FooProvider extends AbstractCommerceProvider` — override `code()` and the interface methods. Use `$this->http(...)` for HTTP; get logging + retry for free.
2. If it uses webhooks, implement `SupportsWebhooks` + a `FooWebhookHandler` in the same namespace.
3. If it uses OAuth, implement `SupportsOAuth` + declare the redirect URI logic.
4. Add a `commerce_providers` seed row.
5. Register in `config/commerce.php`:

```php
'foo' => [
    'class'        => \App\Commerce\Providers\Foo\FooProvider::class,
    'handler'      => \App\Commerce\Providers\Foo\FooWebhookHandler::class,
    'order_mapper' => \App\Oms\Normalization\Providers\FooOrderMapper::class,
    'config'       => [ 'base_url' => env('FOO_API_BASE'), 'timeout' => 30 ],
],
```

6. Write an `OrderMapperInterface` impl at `app/Oms/Normalization/Providers/FooOrderMapper.php` so OMS knows how to translate their raw payload to canonical `OrderDTO`.

Done. Business logic (OrderService, FulfillmentRouter, listeners) is unchanged.

---

## 8. Retention + observability

- `commerce_api_logs` prunes daily at 03:00 via `commerce:prune-logs` (Kernel). Retention configurable via `config('commerce.logging.retention_days')`.
- Sensitive headers (`authorization`, cookie-style secrets) masked before persisted per `config('commerce.logging.sensitive_headers')`.
- `webhook_events` is intentionally never pruned automatically — the audit trail is valuable long after the api log rolls over. Manual archival if it grows.
- Standard Laravel log tags: `commerce.webhook.received`, `commerce.webhook.parse_failed`, `commerce.inventory.push_ok`, `commerce.inventory.push_failed`.

---

## 9. Related docs

- [`docs/shipping-architecture.md`](docs/shipping-architecture.md) — sister module for outbound couriers; same abstraction shape.
- [`OMS.md`](OMS.md) — canonical order data model + normalization pipeline the Commerce module hands off to.
- [`FULFILLMENT.md`](FULFILLMENT.md) — what happens after an order lands.
- [`INTEGRATIONS.md`](INTEGRATIONS.md) — external-perspective view (webhooks / apiKey / OAuth surface).
