# Rushly SaaS

Multi-tenant logistics + order-management platform. Laravel 12 monolith with a scoped-namespace module architecture (`app/<Module>/`) — each module documented in its own .md file at the repo root.

## Documentation index

### Core

| Doc | What's inside |
|---|---|
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Codebase tour — stack, tenancy, directory map, controllers, database (~112 tables), key services |
| [`ROUTES.md`](ROUTES.md) | Every registered route across `routes/web.php`, `superadmin.php`, `api.php`, `admin.php` |
| [`RUSHLY_APPS_OVERVIEW.md`](RUSHLY_APPS_OVERVIEW.md) | System-of-systems view: this app + Salla / Zid / Woo / Shopify bridge apps + driver app |
| [`INTEGRATIONS.md`](INTEGRATIONS.md) | External-perspective view: `/api/v10/*` surface, webhooks, auth model, bridge patterns |
| [`GAPS.md`](GAPS.md) | Known bugs / open items / recent closures |

### Modules

| Doc | Module | Status |
|---|---|---|
| [`docs/shipping-architecture.md`](docs/shipping-architecture.md) | `app/Shipping/` — generic courier abstraction | Production. First provider: Logestechs (verified end-to-end). |
| [`COMMERCE.md`](COMMERCE.md) | `app/Commerce/` — generic storefront abstraction | Scaffold + Salla provider. Feature-flag gated. |
| [`OMS.md`](OMS.md) | `app/Oms/` — canonical orders + normalization pipeline | Wired. Consumers: Fulfillment listener. |
| [`FULFILLMENT.md`](FULFILLMENT.md) | `app/Fulfillment/` — router + strategies (WMS / 3PL / vendor / merchant-self) | Wired. Strategies dispatch to Shipping + WMS modules. |
| [`3PL.md`](3PL.md) | Legacy per-provider 3PL services (Aramex / Jet / Zajel / Panda) + Logestechs migration notes | Legacy live; new providers use the Shipping module instead. |
| [`ACCOUNTING.md`](ACCOUNTING.md) | Qoyod / Daftra / Odoo accounting sync | Live per-tenant. |

### UI + operator surfaces

| Doc | Audience |
|---|---|
| [`MERCHANT_DASHBOARD.md`](MERCHANT_DASHBOARD.md) | Merchant-facing dashboard tour |
| [`super-admin.md`](super-admin.md) | SaaS owner (super-admin) surfaces |
| [`VENDOR.md`](VENDOR.md) | Vendor / supplier management surface |
| [`MOBILE_APPS.md`](MOBILE_APPS.md) | Driver mobile app (Flutter) |
| [`KNOWLEDGE_BASE.md`](KNOWLEDGE_BASE.md) | In-app knowledge base engine |
| [`TOURS.md`](TOURS.md) | Onboarding tours |

### Inertia migration

`docs/inertia/` contains the ongoing Blade → React + Inertia migration guide + setup notes.

---

## Quick module map

```
app/
├── Shipping/          → outbound couriers (docs/shipping-architecture.md)
├── Commerce/          → storefront ingestion (COMMERCE.md)
├── Oms/               → canonical order model (OMS.md)
├── Fulfillment/       → routing + strategies (FULFILLMENT.md)
├── Salla/             → Salla-specific bridge (INTEGRATIONS.md §4)
├── Wms/               → warehouse management (observers for StockChanged)
├── Zatca/             → Saudi e-invoicing Phase 1 generator
├── Qoyod/, Daftra/, Odoo/  → per-tenant accounting sync (ACCOUNTING.md)
├── Logestechs/        → legacy Logestechs settings model (superseded by Shipping module — see 3PL.md)
├── Http/              → controllers, middleware, resources
├── Models/            → Eloquent models
├── Repositories/      → data access
├── Services/          → per-provider legacy 3PL services (Aramex, Jet, Zajel, Panda)
├── Exports/           → Excel exports (Maatwebsite)
├── Jobs/              → app-wide queued jobs
├── Console/Commands/  → artisan commands + scheduled tasks
└── Providers/         → service providers (Route, Event, Tenancy, etc.)
```

Every module follows the same shape: `Contracts/` + `DTOs/` + `Providers/` (or `Strategies/`) + `Services/` + `Models/` + `Events/` + `Listeners/`. Adding a new capability is a "drop a class in, add a config row" exercise — no business-logic changes.

---

## Standard flows

- **Storefront → parcel**: Commerce webhook → OMS OrderReceived → Fulfillment strategy → Shipping / WMS / vendor
- **Bulk operations**: `/admin/bulk_action` — Assign 3PL, Change Status, Cancel, Print AWBs, Export XLSX
- **Shipping tracking sync**: `shipping:sync-tracking` cron, dispatches one job per active connection every 5 min
- **Log retention**: `commerce:prune-logs` (03:00) + `shipping:prune-logs` (03:15) daily

---

## Getting started

Standard Laravel 12 application. See [`ARCHITECTURE.md`](ARCHITECTURE.md) §3 for the full local + tenant setup.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate         # central + tenant tables
npm run dev                 # or `npm run build` for prod
php artisan serve
```

Multi-tenancy uses [stancl/tenancy](https://tenancyforlaravel.com/) with per-subdomain identification (`{tenant}.rushly.tech`). See `config/tenancy.php` for the resolver config.
