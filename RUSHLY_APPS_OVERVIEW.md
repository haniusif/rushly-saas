# Rushly Apps — Project Overview

This document summarizes the **rushly-saas** logistics platform and the four storefront-integration bridges (Salla, Zid, WooCommerce, Shopify) that feed orders into it.

The bridges all do the same thing end-to-end:

1. Let a merchant connect their store to Rushly.
2. Receive new orders from the store.
3. Create a Rushly parcel for each order via the v10 API.
4. Write the AWB / tracking number back to the store.
5. Expose a public tracking page for customers.

What differs between them is *how* each platform authenticates, delivers events, and accepts writebacks.

---

## System map

```
                       ┌────────────────────────────────────────────────────┐
                       │  rushly-saas  (main Laravel monolith — multi-tenant)│
                       │   central: rushly.test  +  tenant subdomains       │
                       │   API: /api/v10/*   (Sanctum + apiKey)             │
                       └────────────────────────────────────────────────────┘
                              ▲           ▲           ▲            ▲
                              │           │           │            │
             ┌────────────────┘           │           │            └──────────────┐
             │                            │           │                           │
   ┌───────────────────┐    ┌───────────────────┐    ┌────────────────────┐    ┌──────────────────────┐
   │  rushly-salla/    │    │   rushly-zid/     │    │  WP plugin (per    │    │  rushly-shopify/     │
   │  hosted Laravel   │    │  hosted Laravel   │    │  merchant site) —  │    │  hosted Node/React-  │
   │  bridge, OAuth +  │    │  bridge, OAuth +  │    │  rushly-woocommerce│    │  Router app, OAuth + │
   │  HMAC webhooks    │    │  Basic-Auth wh.   │    │  has no bridge app │    │  Shopify webhooks    │
   └───────────────────┘    └───────────────────┘    └────────────────────┘    └──────────────────────┘
             ▲                       ▲                        ▲                          ▲
             │                       │                        │                          │
         Salla store              Zid store              WooCommerce store          Shopify store
```

All four bridges talk to `rushly-saas` via `/api/v10/*` using the shared `apiKey` header (Shopify also carries a per-shop Sanctum bearer).

---

## Apps at a glance

| App | Path | Topology | Auth model |
|---|---|---|---|
| **rushly-saas (main)** | repo root (`/home/rushlylogistic/public_html/`) | Multi-tenant Laravel monolith — central domain + tenant subdomains | Laravel UI + Sanctum + `apiKey` (API) |
| Rushly × Salla | `rushly-salla/` | Hosted Laravel bridge, one install per Rushly tenant | OAuth 2.0 (Salla SDK) + HMAC-signed webhooks |
| Rushly × Zid | `rushly-zid/` | Hosted Laravel bridge, one install per Rushly tenant | OAuth 2.0 (hand-rolled) + Basic-Auth webhooks |
| Rushly × WooCommerce | `rushly-woocommerce/` (reference only — plugin repo is separate) | **No hosted bridge** — a WP plugin installed per merchant site | Shared `apiKey` + per-site bearer token |
| Rushly × Shopify (Rushly Express) | `rushly-shopify/` | Hosted embedded Shopify app + public tracking, one instance serves every Shopify shop | Shopify OAuth (per-shop offline session) + Rushly `apiKey` + per-shop Sanctum bearer |

---

## 0. Main rushly-saas app (the logistics platform itself)

The repo root is a **multi-tenant logistics / courier management SaaS** built on Laravel 10. A single deployment hosts:

- A **central domain** (marketing site + super-admin) where logistics companies sign up.
- **Tenant subdomains** (one per customer company) running the operational app — admins, hub managers, merchants, delivery men all log into their own subdomain.

Each tenant runs the full courier workflow: parcels, hubs, delivery men, merchants, payments, reporting, accounting, payroll, support, fraud, news.

**Stack**

| Layer | Choice |
|---|---|
| Framework | Laravel 10 (`^10.10`) |
| PHP | `^8.1` (tested on 8.3 — vendor deps break on 8.4) |
| DB | MySQL (`mysqli` + `pdo`) |
| Multi-tenancy | `stancl/tenancy` v3.7 (shared DB, per-tenant cache/filesystem) |
| Frontend | Blade + pre-compiled `public/css`/`public/js` (Vite config exists but unused) |
| Auth | Laravel UI + Sanctum (API) + Socialite (OAuth) |
| Auditing | `spatie/laravel-activitylog` |
| PDF / Excel / Barcodes | `carlos-meneses/laravel-mpdf`, `maatwebsite/excel`, `milon/barcode` |
| Payments | Stripe, PayPal, Razorpay, Paytm, Skrill, bKash, SSLCommerz, AamarPay |
| SMS | Twilio, Vonage |

**Multi-tenancy** — Powered by `stancl/tenancy` with a **shared single database**. Scoping is enforced at the application layer via the `domains` table (host → tenant_id), `InitializeTenancyByDomain` middleware, and per-model `company_id` columns with `scopeCompanywise()`.

**Scale of the codebase**

- `app/Http/Controllers/` — ~125 controllers split across `Auth/`, `Api/V10/`, `Backend/` (admin/ops, with `HubPanel/`, `MerchantPanel/`, `Superadmin/`, `FrontWeb/` sub-panels), `Frontend/`, `Admin/`.
- `app/Models/` — ~87 Eloquent models (`Backend/` holds the domain models: `Parcel`, `Merchant`, `Hub`, `Account`, `Salary`, plus the new `Tour`, `TourStep`, `UserTourProgress`, `TourEvent`).
- `app/Repositories/` — ~48 repositories, interface-bound in `AppServiceProvider` (100+ bindings) and constructor-injected into controllers.
- `app/Services/` — `DeliveryPandaService` (3PL), `SallaService`, `ZidService`, `WooCommerceService` (these push status writebacks back to the bridge apps / plugin), `FollowupNotificationDispatcher`.
- `app/Enums/` — 24 enums; the central one is `ParcelStatus` (34-state lifecycle, paired with `app/Support/ParcelStatusHelper` for the state machine).
- `database/migrations/` — ~96 migrations → ~116 tables across tenancy/auth, parcels & logistics, merchants & billing, accounting, settings, HR, support, geography, CMS, superadmin/plans, and onboarding tours.
- `routes/` — 7 files: `web.php` (main app), `superadmin.php` (central-only), `api.php` (v10 REST), `admin.php`, `tenant.php`, `console.php`, `channels.php`.
- `resources/js/Tour/` — Frontend onboarding engine (Provider, Overlay, Popover, Launcher, WelcomeModal). Mounted globally in `merchant.jsx` (shared entry for admin + merchant panels).

**API v10** — All endpoints under `/api/v10/*`, gated by `CheckApiKeyMiddleware` (validates the shared `apiKey` header) plus `auth:sanctum` for protected routes. Covers merchant/driver auth, parcel ops (CRUD + status), dashboards, invoices, payments, support/ops. Also exposes the `external/{salla,zid,woocommerce}/parcel` endpoints the bridges POST to.

**How the bridges plug in** — Each bridge stores a per-merchant Sanctum token (`SallaMerchant.rushly_merchant_token`, `ZidStore.rushly_merchant_token`) issued by rushly-saas to scope parcel creation to the right merchant. WooCommerce skips this and passes `merchant_id` in the JSON body instead. Status writeback (parcel → storefront) is driven from rushly-saas using link tables (`salla_orders`, `zid_orders`, `woocommerce_orders`) that live in the main DB, not in the bridge apps.

**Onboarding tours** — Every logged-in user is auto-offered a role-appropriate walkthrough on first login. 18 system tours ship out of the box (12 admin + 6 merchant, covering every sidebar module). Tours are JSON-authored (`database/seeders/tours/`), tenant-overridable via `/admin/tours`, EN+AR bilingual with RTL support. Full architecture in `TOURS.md`.

**Authoritative deep-dive**: `ARCHITECTURE.md` at the repo root (directory map, full table list, controller breakdown, middleware, enums, integrations).

---

## 1. Rushly × Salla (`rushly-salla/`)

Standalone Laravel 10 app. Uses the official `salla/ouath2-merchant` SDK.

**Responsibilities**
- OAuth + install via Salla Partner Portal (Easy or Custom mode).
- Webhook receiver for `app.*`, `order.*`, `shipment.creating`, `shipment.cancelled`.
- On `order.created` → mirror locally → create Rushly parcel.
- On `shipment.creating` → POST Rushly tracking number back to Salla as the waybill.
- Public tracking proxy at `/track/{trackingNumber}`.

**Identity** — `salla_merchant_id`. Token: Sanctum bearer (~2 weeks/1 month TTL).

**Key routes** — `/oauth/redirect`, `/oauth/callback`, `/webhooks/salla`, `/dashboard`, `/track`.

**Detailed reference**: `rushly-salla/SALLA_REFERENCE.md`.

---

## 2. Rushly × Zid (`rushly-zid/`)

Standalone Laravel 10 app, structurally similar to the Salla bridge but hand-rolls OAuth (no PHP SDK).

**Responsibilities**
- OAuth against `oauth.zid.sa`, store both `authorization_token` and `access_token` (`X-MANAGER-TOKEN`).
- Subscribe webhooks per store via `SubscribeZidWebhooksJob` immediately after OAuth.
- Webhook receiver for `order.create`, `order.status.update`, `order.payment_status.update`.
- On `order.create` → mirror → create Rushly parcel → PATCH AWB back via `/managers/store/orders/{id}/shipment` as soon as Rushly returns it (no `shipment.creating` event).
- Public tracking proxy at `/track/{trackingNumber}`.

**Identity** — `zid_store_id`. Tokens: pair of bearer tokens, both required on every API call (`ZidApiClient::forStore($store)` wires both).

**How Zid differs from Salla**
- No PHP SDK — OAuth hand-rolled in `OAuthController`.
- No HMAC on webhooks — uses Basic Auth replayed from subscription time.
- No native `app.installed` / `app.uninstalled` — install detected from OAuth callback; uninstall detected when token refresh fails.
- Two tokens per store, not one.
- AWB writeback fires immediately after parcel creation (no `shipment.creating` event to wait for).

**Detailed reference**: `rushly-zid/ZID_REFERENCE.md`.

---

## 3. Rushly × WooCommerce (`rushly-woocommerce/`)

**Reference docs only.** Unlike Salla and Zid, there is no hosted Laravel bridge — every merchant installs the **Rushly WP plugin** on their own WordPress site. The plugin lives in a separate repository (e.g. `haniusif/rushly-woocommerce-plugin`).

**Topology**

```
WP store A ──┐
WP store B ──┼──(direct REST)──► rushly-saas (main)
WP store C ──┘                         ▲
                                       │
                       (writeback per-site)
                                       │
WP store A ◄───────────────────────────┤
WP store B ◄───────────────────────────┤
WP store C ◄───────────────────────────┘
```

**Plugin responsibilities**
- POST orders directly to `https://admin.rushly-logistic.com/api/v10/external/woocommerce/parcel` using the shared `apiKey` and a `site_token` generated at install time.
- Expose a custom REST route `/wp-json/rushly/v1/parcel-status` (Bearer-authenticated) for Rushly to push status updates back.
- Register Rushly's `rushly-*` status namespace (`rushly-picked-up`, `rushly-in-transit`, `rushly-out-for-delivery`, `rushly-returned`) — WooCommerce's 8 native statuses can't represent Rushly's 40-state pipeline.

**Identity** — `site_url + wc_order_id`. Each `woocommerce_orders` link row in rushly-saas stores the per-site `site_url` + `site_token`.

**Detailed reference**: `rushly-woocommerce/WOOCOMMERCE_REFERENCE.md`.

---

## 4. Rushly × Shopify — "Rushly Express" (`rushly-shopify/`)

**The odd one out in the bridge family.** Not Laravel — it's a Node.js + React Router 7 embedded Shopify app, scaffolded from `@shopify/shopify-app-template-react-router`. Public name: **Rushly Express**. Hosted at `https://shopify.rushly-logistic.com`.

**Stack**

| Layer | Choice |
|---|---|
| Runtime | Node.js ≥ 20.19 |
| Framework | React Router 7 |
| Shopify SDK | `@shopify/shopify-app-react-router` v1.x + App Bridge React + Polaris web components |
| DB | Prisma + SQLite (`dev.sqlite`) — single-instance only |
| API version | `2026-07` |
| Auth | Shopify OAuth (offline tokens with refresh, `expiringOfflineAccessTokens` enabled) |

**Responsibilities**

1. Shopify-side install + OAuth.
2. Receive `orders/create` (and `orders/updated`) webhooks, mirror as a local `Shipment` row.
3. POST the order to rushly-saas `POST /api/v10/parcel/store`; store the returned `tracking_id` + numeric `parcel_id` on the Shipment.
4. **Auto-fulfill the Shopify order** via the Admin GraphQL `fulfillmentCreate` mutation, attaching the Rushly tracking number — so the customer gets Shopify's "your order shipped" email immediately.
5. **Poll** rushly-saas `GET /api/v10/parcel/tracking/{id}` (public endpoint, no token) on a cron to detect status changes; map Rushly's `ParcelStatus` enum → the local 7-state vocabulary; on `DELIVERED`, fire `fulfillmentCreate` on Shopify if not already fulfilled.
6. Serve a public tracking page at `/track/:trackingNumber` (custom CSS, not Polaris — works outside the Shopify admin).
7. Demo mode: seeds 5 synthetic shipments per shop on first load so the embedded admin isn't empty.

**Identity** — `shop` (Shopify shop domain). Per-shop config (`rushlyMerchantId`, `rushlyApiBase`, `rushlyApiKey`, `rushlyAuthToken`, `cronSecret`) lives on the `ShopSettings` row, configured in `/app/settings`.

**Routes**

| Path | Purpose |
|---|---|
| `/app` | Dashboard (KPI tiles, recent shipments) |
| `/app/shipments`, `/app/shipments/new`, `/app/shipments/:id`, `/app/shipments/bulk` | Embedded admin |
| `/app/settings` | Per-shop Rushly + carrier settings (rotates `cronSecret`) |
| `/track`, `/track/:trackingNumber` | Public customer tracking |
| `/webhooks/app/uninstalled`, `/webhooks/app/scopes_update` | Active webhooks |
| `/webhooks/orders/create`, `/webhooks/orders/updated` | Wired but **disabled** in `shopify.app.toml` pending PCD ("protected customer data") approval |
| `/api/sync-rushly` | Cron-triggered status poll (global `X-Cron-Key` or per-shop `X-Shop-Key` modes) |
| `/auth/*` | OAuth callbacks |

**Data model (Prisma)**

- `Session` — Shopify OAuth sessions (managed by `@shopify/shopify-app-session-storage-prisma`), now with `refreshToken` + `refreshTokenExpires`.
- `Shipment` — local mirror of every order forwarded to Rushly. Carries `rushlyParcelId`, `trackingNumber` (globally unique), and `lastSyncedAt` / `lastSyncError` for sync-health visibility.
- `ShipmentEvent` — append-only event log per shipment.
- `ShopSettings` — per-shop config including `cronSecret` (rotatable, per-tenant blast-radius limit).

**Status mapping** — `app/jobs/rushly-status-sync.server.js` maps 24+ Rushly `ParcelStatus` codes (`pickup_assign`, `received_warehouse`, `delivery_man_assign`, etc.) onto a 7-state local vocabulary: `LABEL_CREATED → PICKED_UP → IN_TRANSIT → OUT_FOR_DELIVERY → DELIVERED`, plus `EXCEPTION` and `CANCELED`.

**How Shopify differs from Salla / Zid / WooCommerce**

| Aspect | Difference |
|---|---|
| Stack | Node.js + React Router + Prisma (not Laravel + MySQL) |
| AWB writeback | Uses Shopify's `fulfillmentCreate` GraphQL mutation, not a `shipment` PATCH |
| Status sync | **Polling** on a cron (`/api/sync-rushly`), not webhook-driven — rushly-saas does not push to Shopify |
| Public tracking | Served by the bridge itself at `/track/:trackingNumber`, not proxied to rushly-saas |
| Cron auth | Two modes: global `X-Cron-Key` (env-var) or per-shop `X-Shop-Key` (auto-generated, rotatable) |
| Carrier integrations | Currently **stubbed** (demo mode) — RX-prefixed tracking numbers, simulated transitions |
| Webhook subscription | `orders/create`/`orders/updated` are Shopify PCD topics — require Partner Dashboard approval; currently commented out in `shopify.app.toml` |
| Sessions | Long-lived offline tokens (with refresh) stored in `Session`; no per-merchant Sanctum token issued *by* this app — instead it consumes a Rushly Sanctum bearer set in `ShopSettings` |

**Key files**

| File | Purpose |
|---|---|
| `app/rushly.server.js` | Rushly API client. Per-shop config bundle, `POST /parcel/store`, status push, tracking lookup, connection test |
| `app/shipments.server.js` | Local Shipment CRUD + demo seed + cron-secret management |
| `app/jobs/rushly-status-sync.server.js` | Rushly → Shopify status sync + `fulfillmentCreate` |
| `app/routes/webhooks.orders.create.jsx` | `orders/create` handler: local mirror → Rushly parcel → auto-fulfill |
| `app/routes/api.sync-rushly.jsx` | Cron endpoint (global + per-shop modes) |
| `prisma/schema.prisma` | All four models with comments explaining the Rushly bridge fields |
| `shopify.app.toml` | Shopify CLI config — scopes, webhook subscriptions, auth callbacks |
| `FEATURES.md` | End-user feature list of the embedded app + public tracking |

**Detailed reference**: `rushly-shopify/README.md` (template-level) and `rushly-shopify/FEATURES.md` (functional).

---

## Common Laravel layout (Salla & Zid)

```
app/
├── Console/        # artisan commands
├── Http/           # OAuth + webhook controllers, dashboard, tracking
├── Jobs/           # CreateRushlyParcelJob, Subscribe*WebhooksJob, etc.
├── Models/         # SallaMerchant / ZidStore + Order mirrors
├── Services/       # Platform API clients (e.g. ZidApiClient) + RushlyApiClient
└── Webhooks/       # Event handlers per topic
routes/
├── api.php
└── web.php         # /oauth/*, /webhooks/*, /dashboard, /track/*
```

**Stack** — PHP 8.1+, Laravel 10, Sanctum, Guzzle. PHPUnit for tests.

---

## How they all talk to rushly-saas

| Env var | Purpose |
|---|---|
| `RUSHLY_API_BASE` | Tenant subdomain's v10 API (e.g. `https://admin.rushly.test/api/v10`) |
| `RUSHLY_API_KEY` | Shared `apiKey` header required by `CheckApiKeyMiddleware` (same key across all three integrations) |

Per-merchant Sanctum tokens (`SallaMerchant.rushly_merchant_token`, `ZidStore.rushly_merchant_token`) are what scopes parcel-creation calls to the correct merchant in rushly-saas. WooCommerce carries `merchant_id` in the JSON body instead.

Status writeback (parcel state changes pushed back to the storefront) is driven from rushly-saas using link tables (`salla_orders`, `zid_orders`, `woocommerce_orders`) that live in the main rushly-saas database, not in these bridge apps.

---

## Setup (Salla & Zid — identical shape)

```sh
cp .env.example .env
composer install
php artisan key:generate
# fill in {SALLA|ZID}_* and RUSHLY_* values in .env
php artisan migrate
php artisan serve
```

For WooCommerce there is nothing to set up in this repo — work happens in the WP plugin repo and inside rushly-saas's `external/woocommerce/parcel` controller.

---

## Cheat sheet — how the four differ

| Aspect | Salla | Zid | WooCommerce | Shopify |
|---|---|---|---|---|
| Bridge topology | Hosted Laravel app | Hosted Laravel app | **WP plugin per merchant — no hosted bridge** | Hosted Node/React Router app |
| Identity | `salla_merchant_id` | `zid_store_id` | `site_url + wc_order_id` | Shopify `shop` domain |
| Inbound auth | HMAC-SHA256 (or Token) | HTTP Basic auth | Bearer token issued by plugin | Shopify HMAC (handled by SDK) |
| Outbound auth | Sanctum bearer | Partner bearer + `X-MANAGER-TOKEN` | n/a — plugin calls us | Shopify offline OAuth token + Rushly Sanctum bearer |
| OAuth | Yes (Easy + Custom), via SDK | Yes (Custom only), hand-rolled | None — `apiKey` only | Yes, via Shopify CLI / SDK |
| Token TTL | 2 weeks / 1 month | 1 year / 1 year | n/a (fixed per-site bearer) | Offline tokens w/ refresh (expiringOfflineAccessTokens) |
| AWB writeback trigger | `shipment.creating` webhook | Immediately after parcel create | Status push from rushly-saas | `fulfillmentCreate` GraphQL on parcel create + on `DELIVERED` poll |
| Status sync direction | Storefront → Rushly via webhook | Storefront → Rushly via webhook | Rushly → WP via per-site bearer | **Rushly → Shopify via polling** (`/api/sync-rushly`) |
| Status enum | 16 values | 6 values | 8 native + custom `rushly-*` | Shopify Fulfillment + 7-state local mirror |
| Returns | `shipment.creating` w/ `type=return` | Separate reverse-waybill endpoint | Order status update | Mapped to `EXCEPTION` / `CANCELED` local states |
| Stack | PHP 8.1 + Laravel 10 | PHP 8.1 + Laravel 10 | PHP (WP plugin) | Node 20+ + React Router 7 + Prisma |

---

## Related docs in this repo

- `ARCHITECTURE.md` — full rushly-saas architecture (the main app).
- `INTEGRATIONS.md` — cross-cutting integration notes.
- `rushly-salla/README.md` + `SALLA_REFERENCE.md` — Salla bridge.
- `rushly-zid/README.md` + `ZID_REFERENCE.md` — Zid bridge.
- `rushly-woocommerce/WOOCOMMERCE_REFERENCE.md` — WooCommerce plugin contract (plugin itself lives in a separate repo).
- `rushly-shopify/README.md` + `FEATURES.md` — Shopify embedded app (Rushly Express) + public tracking.
- `ACCOUNTING.md` — accounting/ledger subsystem of the main rushly-saas app.
- `TOURS.md` — onboarding "Take a Tour" system (backend + engine + admin manager + analytics).
- `KNOWLEDGE_BASE.md` — admin knowledge-base handbook system (static reference docs, parallel to interactive tours).
- `MERCHANT_DASHBOARD.md` — merchant-panel dashboard prop contract + widget anchors.
- `database/seeders/tours/README.md` — day-to-day authoring cheatsheet for tour JSON files with the complete `data-tour` anchor catalog.
