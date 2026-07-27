# Rushly — Shared Context Brief (for documentation agents)

> This is a grounding brief compiled from the actual codebase on 2026-07-27. Every
> documentation agent MUST read this first, then VERIFY its own section against real
> source files. **Never guess. Never invent business logic. Cite the source file for
> every non-trivial claim** (e.g. `app/Fulfillment/Services/FulfillmentRouter.php`).
> If something cannot be determined, write literally: **"Not found in the current codebase."**

## Workspace root
`/var/www` — but ONLY the Rushly projects are in scope. Ignore all other folders.

## The Rushly ecosystem (one integrated platform; `rushly-saas` is the SINGLE SOURCE OF TRUTH)

| Project | Path | Type | Role |
|---|---|---|---|
| rushly-saas | `/var/www/rushly-saas` | Laravel 10 (see note) | Backend platform, API, admin web (Inertia/React). SSOT. |
| rushly-admin-app | `/var/www/rushly-admin-app` | Flutter | Back-office / admin mobile (69 dart, 26 screens). Features: approvals, auth, dashboard, drivers, fraud, hub_cash, hubs, map, merchants, parcels, support, tenant, wms |
| rushly-driver-app | `/var/www/rushly-driver-app` | Flutter | Last-mile driver app (55 dart). Features: auth, cash, dashboard, earnings, ndr, notifications, parcels, support, tenant |
| rushly-fleet-app | `/var/www/rushly-fleet-app` | Flutter | Fleet driver app (26 dart). Features: auth, dashboard, fleet, tenant. Tabs: Trips, Vehicle, Fuel, Maintenance |
| rushly-merchant-app | `/var/www/rushly-merchant-app` | Flutter | Merchant portal (71 dart, 27 screens). Features: auth, dashboard, fraud, invoices, ndr, news, parcels, payments, reports, settings, shops, store_connections, support, tenant |
| rushly-scanner-app | `/var/www/rushly-scanner-app` | Flutter | Universal scanner (27 dart). Tabs: Scan, History |
| rushly-sorting-app | `/var/www/rushly-sorting-app` | Flutter | Sorting center (30 dart). Tabs: Scan In, Sort, Bags, Routes |
| rushly-supervisor-app | `/var/www/rushly-supervisor-app` | Flutter | Supervisor (33 dart). Features: assignments, auth, dashboard, drivers, exceptions, reports, tenant |
| rushly-warehouse-app | `/var/www/rushly-warehouse-app` | Flutter | Warehouse ops (36 dart, 10 screens). Tabs: Receive, Pick&Pack, Inventory, Dispatch. Features: fulfillment, wms |
| rushly-store | `/var/www/rushly-store` | Laravel | Storefront / e-commerce system (separate app) |
| rushly-salla | `/var/www/rushly-salla` | Laravel | Standalone Salla↔Rushly bridge app (OAuth, webhooks, order→parcel, AWB writeback, `/track/{tn}`) |

All Flutter apps are CLIENTS of rushly-saas. Always explain how projects work together; never analyze in isolation.

## rushly-saas metrics (ground truth, 2026-07-27)
- 191 migrations · 120 model files · 219 controllers · 60 services · ~94k LOC in `app/`
- Route files & sizes: `web.php` (1545 lines), `superadmin.php` (441), `api.php` (410), `tenant.php` (29), `console.php` (19), `channels.php` (18), `admin.php` (9)
- Frontend: Inertia.js + React (`resources/js/Pages/*.jsx`, 191 pages). Mid-migration Blade→React+Inertia (`docs/inertia/`).

## Stack (from composer.json — VERIFY versions in the doc)
- **Laravel `^10.10`** (NOTE: `README.md` claims "Laravel 12" — this is a doc/code CONFLICT; composer.json is the truth: ^10.10). PHP `^8.1`.
- Multi-tenancy: **stancl/tenancy `^3`** — per-subdomain identification (`{tenant}.rushly.tech`), central domains `127.0.0.1`/`localhost`, `tenant_model = App\Models\Tenant`, UUID tenant IDs.
- API auth: **laravel/sanctum `^3`** (mobile apps). Web guard = `session` (config/auth.php only defines `web`).
- Inertia (`inertiajs/inertia-laravel ^2`), Ziggy (`tightenco/ziggy`), Vite.
- Queue default `sync` (env `QUEUE_CONNECTION`), Cache default `file` (env `CACHE_DRIVER`), Broadcast default `null`.
- Payments libs present: Stripe, PayPal (srmklive), Razorpay, PayTM, Skrill (laraskrill), Cartalyst Stripe.
- SMS: Twilio, Vonage. PDF: mpdf. Barcode: milon/barcode. Excel: maatwebsite/excel. Activity log: spatie/laravel-activitylog. Salla OAuth: salla/ouath2-merchant.
- Feature flags in `config/features.php`: `commerce_layer` (FEATURE_COMMERCE_LAYER, default off), `login_otp` (FEATURE_LOGIN_OTP, default off).

## Module architecture — scoped namespaces under `app/<Module>/`
Each module follows: `Contracts/ + DTOs/ + Providers/ (or Strategies/) + Services/ + Models/ + Events/ + Listeners/`.

- `app/Shipping/` — generic courier abstraction (ShippingProviderFactory, providers, AwbService, ShipmentService, TrackingService, WebhookService, jobs). First provider: Logestechs. Doc: `docs/shipping-architecture.md`.
- `app/Commerce/` — generic storefront ingestion (WebhookIngestService, CommerceProviderFactory, Salla provider). Feature-flag gated. Doc: `COMMERCE.md`.
- `app/Oms/` — canonical Order model + normalization pipeline (OrderNormalizer, mappers, DTOs, OrderService, events OrderReceived/OrderUpdated). Doc: `OMS.md`.
- `app/Fulfillment/` — FulfillmentRouter + Strategies: `WmsFulfillmentStrategy`, `ThreePlDropshipStrategy`, `MerchantSelfStrategy`; `OrderToParcelBridge`; RouteToFulfillmentListener. Doc: `FULFILLMENT.md`.
- `app/Wms/` — warehouse (WmsStockObserver, StockChanged event). WMS **models** live at `app/Models/Backend/Wms/*` (WmsStock, WmsLocation, WmsGrn, WmsGrnItem, WmsProduct, WmsOutbound, WmsOutboundItem, WmsFulfillment, WmsFulfillmentItem, WmsCycleCount, WmsDamageReport). WMS enums at `app/Enums/Wms/*` (PickingStrategy, LocationType, OutboundType, GrnStatus, ProductUnit, FulfillmentStatus, ItemCondition, AdjustmentReason).
- `app/Salla/` — Salla-specific bridge (ApiClient, SallaWmsFulfillmentService, ParcelCreationService, 5 models).
- Accounting sync (per-tenant, live): `app/Qoyod/`, `app/Daftra/`, `app/Odoo/` — each has Services/ApiClient + CustomerSync/InvoiceSync/BillSync/VendorSync/InvoicePaymentSync. Doc: `ACCOUNTING.md`.
- `app/` (Zatca via `app/Services/Zatca/`) — Saudi e-invoicing Phase 1 (TlvEncoder, InvoiceBuilder, QrGenerator, ZatcaService, gateways). Enums `app/Enums/Zatca/*`.
- `app/Services/Performance/` — KPI/analytics (DriverPerformanceService, HubPerformanceService, CustomerPerformanceService, OperatingCompanyPerformanceService, KpiAggregator, PerformanceScoreCalculator, AiInsightsService, HaversineDistance, SlaProxy).
- Legacy per-provider 3PL services in `app/Services/` (Aramex, Jet, Zajel, DeliveryPanda, Logestechs, Zajel) + WooCommerce, Salla, Zid. Doc: `3PL.md`. Being superseded by `app/Shipping/`.
- `app/Logestechs/` — legacy Logestechs settings model.
- `app/Http/Services/` — PushNotificationService, SmsService, PurchaseVerify, ParcelImageService.

## Domain models & enums (rushly-saas)
- Core models in `app/Models/`: User, Tenant, Permission, SuperAdminPermission, MerchantShops, MerchantPayment, PickupRequest, CashReceivedFromDeliveryman, CustomerDomain, PublicTrackingApiKey, Subscribe, Config, Categorys, Backend/*.
- Parcel/shipment models under `app/Models/Backend/*` (e.g. ParcelItem). Enums in `app/Enums/`: ParcelStatus, ParcelType, NdrStatus, NdrAction, NdrFailureReason, DeliveryTime, DeliveryType, UserType, InvoiceStatus, AccountType, AccountHeads, SalaryStatus, StatementType, ApprovalStatus, PayoutSetup, PickupRequestType, Status, SmsSendStatus/SmsSetup, PaymentType, TodoStatus, BooleanStatus, LabelTemplate, AbnormalSeverity, SupportStatus, SectionType. Plus subfolders: Wms/, Zatca/, Wallet/ (WalletStatus, WalletType, WalletPaymentMethod), Merchant_panel/PaymentMethod.

## Standard flows (from README)
- **Storefront → parcel**: Commerce webhook → OMS `OrderReceived` → Fulfillment strategy → Shipping / WMS / vendor.
- **Bulk ops**: `/admin/bulk_action` (Assign 3PL, Change Status, Cancel, Print AWBs, Export XLSX).
- **Tracking sync cron**: `shipping:sync-tracking` (every 5 min, one job per active connection).
- **Log retention crons**: `commerce:prune-logs` (03:00), `shipping:prune-logs` (03:15).

## Existing docs at rushly-saas repo root (PRIMARY sources — read the relevant one, then verify vs code)
`ARCHITECTURE.md`, `ROUTES.md` (244KB, every route), `RUSHLY_APPS_OVERVIEW.md`, `INTEGRATIONS.md`, `GAPS.md`, `3PL.md`, `COMMERCE.md`, `OMS.md`, `FULFILLMENT.md`, `ACCOUNTING.md`, `MERCHANT_DASHBOARD.md`, `super-admin.md`, `VENDOR.md`, `MOBILE_APPS.md`, `KNOWLEDGE_BASE.md`, `TOURS.md`, `docs/shipping-architecture.md`, `docs/inertia/*`.

## Output rules for every doc
- Write to the exact path assigned. Use Markdown with clear headers.
- Use **Mermaid** fenced blocks for diagrams where the phase calls for them.
- Cite source files inline as backticked paths.
- When existing docs conflict with code, add a "⚠️ Doc vs Code" note explaining what changed / is outdated / is current.
- Cross-reference sibling docs by relative link (e.g. `see [06-Database.md](06-Database.md)`).
- End every doc with a "Sources" section listing the key files/dirs you actually read.
- Prefer accuracy over completeness. It is correct to write "Not found in the current codebase."
