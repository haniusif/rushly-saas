# Rushly SaaS — Integrations Reference

How external systems talk to Rushly. Sister document to `ARCHITECTURE.md` (which covers the codebase layout) — this one covers what's exposed to the world and how to wire something new into it.

---

## 1. What's an "integration" here?

Anything outside the Laravel monolith that calls `/api/v10/*` or receives webhooks from us. Three live integrations today:

| Integration | Repo | Direction | Status |
|---|---|---|---|
| **Driver mobile app** (Flutter) | `haniusif/rushly-driver` | Bidirectional (REST + FCM push) | Production |
| **Rushly Express** (Shopify embedded app) | `haniusif/rushly-shopify-app` | Bidirectional (Rushly REST + Shopify webhooks + Shopify GraphQL) | Production-ready bridge, hosting deferred |
| **External cron** (Vercel/EasyCron/curl-loop) | n/a | Inbound HTTP only | Triggers `syncRushlyShipments` per shop |

Everything goes through one of two auth gates: **static apiKey header** and/or **Sanctum bearer token**. See §3.

---

## 2. API surface area

Two versioned API namespaces live under `routes/api.php`:

| Prefix | Purpose | Auth | Consumers |
|---|---|---|---|
| `/api/v10/*` | Public REST surface for partners | `apiKey` header + Sanctum (most routes) | Driver app, Shopify bridge, future partners |
| `/api/sync-rushly` | Per-shop cron entry (Shopify side calls it) | `X-Cron-Key` or `X-Shop-Key` | External scheduler |

Within `/api/v10/*` the major resource groups:

```
parcel/                  — Create + read + status-mutate parcels
parcel/tracking/{id}     — PUBLIC tracking timeline (no auth, no apiKey)
delivery-man/*           — Driver app lifecycle (login, parcel list, status updates)
merchant-panel/*         — Merchant-self-service (orders, invoices, payments)
general-settings         — Tenant branding/config probe (used by Shopify "Test connection")
notification/*           — Push token registration + history
ndr/*                    — Non-delivery report submission (driver-side)
wms/*                    — Warehouse Management endpoints (per-merchant)
```

Versioning: there's no `/api/v11/*` planned. New endpoints land inside `v10` for the foreseeable future.

---

## 3. Auth model

Two layers, both must pass on most routes:

### Layer 1 — `apiKey` static header (`app/Http/Middleware/CheckApiKey.php`)

Single shared secret. Same value for every request from every integration. Gates the *door*; doesn't identify the caller.

```
apiKey: 123456rx-ecourier123456
```

If wrong: HTTP 400 `{"success":false,"message":"Invalid Api Key"}`.

> ⚠️ **Known weakness**: the `/general-settings` route only checks `apiKey`, not the bearer. The Shopify "Test connection" button therefore only validates layer 1. See *§7 Known gaps*.

### Layer 2 — Sanctum bearer token (per-user)

Identifies *which* tenant user is making the call. Minted via `User::createToken('name')->plainTextToken`. Format:

```
Authorization: Bearer 1788|iLJ7hxQsyZpq0Yo3...
```

Each integration gets its **own token, bound to a specific user that owns the merchant**:

| Integration | User the token belongs to |
|---|---|
| Shopify Express bridge | The `User` linked to the `Merchant` you're wiring up (e.g. merchant 32 → user 56 = `william@rushly.me`) |
| Driver app | The delivery man's user record (minted on driver login) |

### Minting / rotating

```bash
# Mint:
cd ~/public_html    # or local: /Users/haniyousif/dev/rushly-space/rushly-saas
php artisan tinker --execute='
tenancy()->initialize(App\Models\Tenant::find("rushly-logistic"));
$u = App\Models\Backend\Merchant::find(MERCHANT_ID)->user;
echo $u->createToken("name-of-integration")->plainTextToken . PHP_EOL;
'

# Revoke all tokens for a user:
$u->tokens()->delete();

# Revoke just one:
$u->tokens()->where('name', 'name-of-integration')->delete();
```

---

## 4. The Shopify Express bridge

Largest integration today. Bidirectional. Lives in `haniusif/rushly-shopify-app` (Node + React Router v7 + Prisma).

### 4.1 Per-shop config model

The Shopify app stores **one `ShopSettings` row per Shopify shop domain**. The relevant Rushly-bridge columns:

```prisma
model ShopSettings {
  shop             String  @id            // "rushly-wko1dret.myshopify.com"
  rushlyApiBase    String?                // "https://admin.rushly-logistic.com"
  rushlyApiKey     String?                // 123456rx-ecourier123456
  rushlyAuthToken  String?                // 1786|iLJ7hxQs...
  rushlyMerchantId Int?                   // 8
  cronSecret       String?                // per-shop X-Shop-Key for sync calls
  // ...
}
```

A merchant connects their Shopify shop to a Rushly merchant account by filling all 4 fields in the embedded admin's **Settings → Rushly bridge** card. Each Shopify shop maps to exactly one Rushly merchant.

### 4.2 Outbound flow: Shopify order → Rushly parcel

Three entry points, all funnel through the same `createShipmentFromOrder` helper (`app/lib/shipment-bridge.server.js`):

```
[Shopify webhook orders/create]    [User clicks "Create" on /app/shipments/new]    [User selects N orders on /app/shipments/bulk]
              │                                       │                                              │
              └───────────────────────────┬───────────┴──────────────────────────────────────────────┘
                                          ▼
                          createShipmentFromOrder({ shop, order })
                                          │
                                          ├─ 1. Idempotency check (skip if shipment for this Shopify order already exists)
                                          ├─ 2. Create local Shipment row (Prisma)
                                          ├─ 3. POST to Rushly /api/v10/parcel/store
                                          ├─ 4. Capture rushlyTracking + rushlyParcelId, write back to local Shipment
                                          └─ 5. ⭐ Shopify fulfillmentCreate with the Rushly tracking#
                                                  (fires the "your order has shipped" email to the customer)
```

Each step is non-fatal — if step 5 fails, the parcel still exists; the error lands on `Shipment.lastSyncError`. The status sync job's `markFulfilledInShopify` retries fulfillment on every poll as a safety net.

The Rushly side accepts the parcel via:

```
POST /api/v10/parcel/store
Authorization: Bearer <merchant-user-sanctum-token>
apiKey: 123456rx-ecourier123456
Content-Type: application/json

{
  "merchant_id":         8,
  "customer_name":       "...",
  "customer_phone":      "...",
  "customer_address":    "...",
  "customer_city":       "...",
  "customer_area":       "...",
  "cash_collection":     100.00,
  "weight":              1.4,
  "number_of_boxes":     1,
  "package_description": "...",
  "reference_number":    "#1042",
  "note":                "Shopify order #1042"
}
```

Returns the created parcel including `data.parcel.id` (numeric) and `data.parcel.tracking_id` (string, e.g. `RL241121AB12`).

### 4.3 Inbound flow: Rushly status → Shopify

Every shop has a **per-shop secret** (`cronSecret`) used to authenticate sync calls. The cron pattern:

```
*/15 * * * *  curl -X POST 'https://<shopify-app>/api/sync-rushly?shop=rushly-wko1dret.myshopify.com' \
                      -H 'X-Shop-Key: <the shop's cronSecret>'
```

Inside the Shopify app, `syncRushlyShipments({ shop })` runs this loop for each non-terminal local shipment:

```
1. GET https://admin.rushly-logistic.com/api/v10/parcel/tracking/{rushlyTracking}    (PUBLIC, no auth)
2. Parse latest status event → map Rushly status code → local status (LABEL_CREATED|PICKED_UP|IN_TRANSIT|OUT_FOR_DELIVERY|DELIVERED|EXCEPTION|CANCELED)
3. If new status differs from current: update local Shipment + insert ShipmentEvent
4. If new status == DELIVERED && order not yet fulfilled: call Shopify fulfillmentCreate (safety net for step 5 above)
5. Stamp Shipment.lastSyncedAt (always) and clear/set Shipment.lastSyncError
```

Status map lives at `app/jobs/rushly-status-sync.server.js` (constant `RUSHLY_TO_LOCAL` covers 24 Rushly status codes).

### 4.4 Cancellation flow

`webhooks/orders/updated` fires when a Shopify order is changed. We only act on `cancelled_at` being set:

```
1. Flip local Shipment to CANCELED
2. For each linked Rushly parcel: GET /api/v10/parcel/{id}/status/30  (30 = RETURN_RECEIVED_BY_MERCHANT)
```

Cancellations are best-effort — terminal Rushly states (DELIVERED, etc.) are skipped because pushing CANCEL would be a lie.

### 4.5 Required Shopify scopes

Listed in `shopify.app.toml`:

```
write_products, write_metaobjects, write_metaobject_definitions,
read_orders,                            ← reads order list + details
read_customers,                         ← reads customer list (for picker)
write_assigned_fulfillment_orders       ← Shopify fulfillmentCreate
```

⚠️ `read_orders` and `read_customers` are **Protected Customer Data** topics. On dev stores access is instant; on prod stores it requires Partner Dashboard approval before real data flows.

### 4.6 Webhook subscriptions

Two always-on:

| Topic | Handler | Purpose |
|---|---|---|
| `app/uninstalled` | `webhooks.app.uninstalled.jsx` | Clean up the shop's Session + ShopSettings |
| `app/scopes_update` | `webhooks.app.scopes_update.jsx` | Re-grant scope tracking |

Two **commented out** pending PCD approval (`shopify.app.toml` lines 50-67):

| Topic | Handler | Why disabled |
|---|---|---|
| `orders/create` | `webhooks.orders.create.jsx` | PCD-restricted (customer email/phone/address) |
| `orders/updated` | `webhooks.orders.updated.jsx` | Same |

To re-enable after PCD approval: uncomment the two `[[webhooks.subscriptions]]` blocks, then `npx shopify app deploy`.

---

## 5. The driver mobile app

Flutter app (`haniusif/rushly-driver`) used by delivery men.

### Endpoints it hits

Largely under `/api/v10/delivery-man/*`. Notable ones:

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/login` | Exchanges email + password for a Sanctum token |
| `GET`  | `/profile` | Driver profile + permissions |
| `GET`  | `/parcels/all` | Today's parcels list (paginated) |
| `POST` | `/parcels/{id}/delivered` | Mark delivered + collect signature |
| `POST` | `/parcels/{id}/not-delivered` | Submit NDR (failure reason, attempt next steps) |
| `POST` | `/device-token` | Register FCM device token for push notifications |

### Auth lifecycle

1. Driver opens app → enters email + password → app POSTs to `/login`
2. Backend mints a Sanctum token, returns it
3. App stores token in secure storage; every subsequent request sends `Authorization: Bearer <token>` + `apiKey: <static>`
4. On logout: app calls `/logout` → backend deletes the token row

### Push notifications

Server side: `App\Services\NotificationDispatcher` (or similar) builds the FCM payload and sends via `firebase/php-jwt` to FCM HTTP v1.

Client side: `lib/services/push_notification_service.dart`:
- Background handler stored as top-level function (`@pragma('vm:entry-point')`)
- Foreground messages → in-app banner via `Get.snackbar`
- Tap on notification → deep-link to `ParcelDetails(parcelId: ...)` if payload includes `parcel_id`
- All received messages persisted to GetStorage for the notification history page

### Env-driven API base

`lib/services/app_config.dart` — `--dart-define=RUSHLY_API_BASE=...` overrides at build time. Default fallback: `admin.rushly.test` in debug, `admin.rushly-logistic.com` in release.

---

## 6. Public webhook + cron endpoints

Exposed to the internet, called by external systems:

| Endpoint | Auth | Source | Effect |
|---|---|---|---|
| `POST /api/sync-rushly?shop=<X>` | `X-Shop-Key: <shop's cronSecret>` | External cron | Triggers `syncRushlyShipments({ shop })` |
| `POST /api/sync-rushly` | `X-Cron-Key: <global env CRON_KEY>` | Global cron | Triggers sync for ALL shops |
| `POST /webhooks/orders/create` (disabled) | Shopify HMAC signature | Shopify | `createShipmentFromOrder({ shop, order })` |
| `POST /webhooks/orders/updated` (disabled) | Shopify HMAC signature | Shopify | Cancellation handler (above) |
| `POST /webhooks/app/uninstalled` | Shopify HMAC signature | Shopify | Tear down `ShopSettings` + `Session` |
| `GET  /api/v10/parcel/tracking/{id}` | none (PUBLIC) | Anyone | Returns parcel timeline JSON |

The public tracking endpoint is intentionally unauth'd — the tracking ID itself is the bearer (long enough to be unguessable for a single parcel).

---

## 7. Known gaps + things to harden

| # | Gap | Fix difficulty |
|---|---|---|
| 1 | `/general-settings` only checks apiKey, not the Sanctum bearer — "Test connection" passes even with a bogus token | Add `auth:sanctum` middleware to the route |
| 2 | The `apiKey` is a single shared secret hard-coded into every integration. A leak anywhere = a leak everywhere | Rotate via env + redeploy all consumers, OR move to per-integration apiKeys |
| 3 | Sanctum tokens have no UI for rotation — must be done via tinker | Add a "Tokens" page under merchant settings |
| 4 | Webhook signature verification isn't double-checked beyond what `authenticate.webhook` does — should fail-closed on bad HMAC | Add explicit assertion in handlers |
| 5 | Status sync runs sequentially per shop with no HTTP keep-alive — slow on shops with 100+ active parcels | Switch to `undici` with a keep-alive pool, parallelise with bounded concurrency |
| 6 | The Shopify app's `/general-settings` "Test connection" only probes auth, not full parcel-create capability | Add a "Test create" button that creates + immediately cancels a synthetic parcel |
| 7 | Bulk shipment creation processes orders sequentially — 100 orders ≈ 100s of API calls | Bounded parallelism (e.g. `p-limit(5)`) |
| 8 | No COD reconciliation dashboard in the Shopify app — merchants can't see "collected / in-transit-to-merchant / paid out" | New page reading Rushly's payment endpoints |

---

## 8. Adding a new integration — checklist

Say you want to add a "WooCommerce bridge" or a "TikTok Shop bridge". The pattern:

1. **Decide on the per-tenant linkage** — almost always 1 external account ↔ 1 Rushly merchant. Add the linkage columns to the tenant DB (e.g. `woo_store_url`, `woo_api_key`, `woo_merchant_id` on a config table).

2. **Mint a Sanctum token** for the user owning the target merchant. Document the name (`createToken('woo-bridge')`).

3. **Wire the external side to call `/api/v10/parcel/store`** with the standard headers (`apiKey` + `Authorization: Bearer`). Reuse the order-to-parcel mapping shape (see §4.2).

4. **Capture the returned `parcel.id` and `parcel.tracking_id`** on the external side so future cancellations and status syncs have a stable handle.

5. **Implement status pull** — poll `/api/v10/parcel/tracking/{trackingId}` and map status codes (steal the `RUSHLY_TO_LOCAL` map from `app/jobs/rushly-status-sync.server.js`).

6. **Implement cancellation push** — `GET /api/v10/parcel/{id}/status/30` for "returned to merchant" or `/{id}/status/13` for "returned merchant" depending on lifecycle stage.

7. **Add a "Test connection" UI** that hits `/api/v10/general-settings` to confirm credentials before going live.

8. **Document the new integration in this file** — what table holds the config, which user owns the token, which endpoints get hit, how it cancels.

---

## 9. Quick reference: which file does what

```
rushly-saas/
├── app/Http/Controllers/Api/V10/         ← all /api/v10/* endpoints (~60 controllers)
├── app/Http/Middleware/CheckApiKey.php   ← static apiKey gate
├── app/Models/Backend/Parcel.php         ← parcel model
├── app/Enums/ParcelStatus.php            ← canonical status codes (40)
├── routes/api.php                        ← every API route
├── routes/web.php                        ← tenant + central web routes
└── ARCHITECTURE.md                       ← codebase tour

rushly-shopify-app/
├── app/lib/shipment-bridge.server.js     ← canonical createShipmentFromOrder pipeline
├── app/rushly.server.js                  ← Rushly REST client
├── app/jobs/rushly-status-sync.server.js ← status puller + markFulfilledInShopify
├── app/routes/app.shipments.new.jsx      ← single shipment + order picker + customer picker
├── app/routes/app.shipments.bulk.jsx     ← bulk creation from selected orders
├── app/routes/app.settings.jsx           ← per-shop bridge config
├── app/routes/webhooks.orders.*.jsx      ← order webhooks (disabled, pending PCD)
└── shopify.app.toml                      ← scopes + webhook subscriptions

rushly-driver-app/
├── lib/services/api-list.dart            ← endpoint URLs (derived from AppConfig)
├── lib/services/app_config.dart          ← env-driven base URL + apiKey
├── lib/services/push_notification_service.dart  ← FCM lifecycle
└── lib/services/server.dart              ← HTTP client wrapper
```
