# Rushly Mobile Apps

Companion doc to `RUSHLY_APPS_OVERVIEW.md`. That file covers the storefront bridges (Salla / Zid / WooCommerce / Shopify). This file covers the **eight Flutter mobile apps** that consume `rushly-saas` `/api/v10/*` endpoints.

## The 8 apps at a glance

| # | Repo | User | State | What it does |
|---|---|---|---|---|
| 1 | `rushly-driver-app` | Delivery drivers (deliverymen) | **Feature-complete** | Assigned parcels, delivery outcomes (delivered/partial/not-delivered with photo), NDR, earnings, cash reconciliation, live tracking map, AWB scan, route-optimised runsheet |
| 2 | `rushly-merchant-app` | Merchants / shop owners | **Feature-complete** | Parcel CRUD, bulk CSV import, tracking map, shops, payments (accounts + requests + statements PDF), invoices, fraud, NDR, store connections, reports |
| 3 | `rushly-admin-app` | Back-office (super_admin, admin, incharge, hub) | **Feature-complete** | Dashboard, parcels, drivers, merchants (including onboarding approval queue), hubs, payouts, support, fraud, driver-assignment map, hub cash, WMS (stock lookup + GRN receiving + cycle count + damage reports), 3PL assign |
| 4 | `rushly-supervisor-app` | Field supervisors | **Feature-complete** | Live drivers list + detail, unassigned parcels + assign, per-driver performance reports with date range, aggregated exceptions feed (open NDRs + stuck parcels + returning to courier) |
| 5 | `rushly-warehouse-app` | Warehouse staff | **Feature-complete** | Receive (GRN scan + stock lookup), Pick & Pack (fulfillment queue with SLA + per-item pick + pack), Inventory (cycle count + damage + adjust), Dispatch (READY queue + AWB scan → hand-off) |
| 6 | `rushly-sorting-app` | Hub sorting operations | **Feature-complete** | Scan In (parcel lookup), Sort (auto-drop into destination-hub bag), Bags (session-scoped bag list + contents), Routes (grouped-by-hub dispatch → bulk `TRANSFER_TO_HUB`) |
| 7 | `rushly-fleet-app` | Long-haul fleet drivers | **Feature-complete** | Trips (start/end with odometer + pre-trip inspection), Vehicle (assigned info), Fuel (log fill-ups), Maintenance (report + view issues) |
| 8 | `rushly-scanner-app` | Any pipeline staff | **Feature-complete** | Universal AWB scanner with status-aware action strip (e.g. TRANSFER_TO_HUB → "Received by hub"), device-local scan history (SharedPreferences, capped at 100) |

All eight live as sibling repositories to `rushly-saas/` (e.g. `/var/www/rushly-supervisor-app`, etc.) and ship as `main`-branch repos on GitHub under `haniusif/`.

**In-tenant directory page** — the eight apps are also surfaced inside every tenant admin at **Settings → Mobile Apps** (`/admin/settings/mobile-apps`). See §13 for controller / view / permission wiring.

---

## 1. Shared architecture

All apps share the same tech baseline:

| Concern | Choice |
|---|---|
| Framework | Flutter 3.19+ / Dart 3.3+ |
| Platforms | Android + iOS (single codebase) |
| State | Riverpod 2 (`Notifier` / `AsyncNotifier`) |
| HTTP | Dio 5.5 + `pretty_dio_logger` |
| Routing | `go_router` 14 with auth-guarded redirects |
| Secure storage | `flutter_secure_storage` (bearer token + tenant URL) |
| Local storage | `shared_preferences` (scan history, ephemeral device state) |
| Barcode | `mobile_scanner` 5.2 (warehouse / sorting / scanner apps) |
| Maps | `flutter_map` + `latlong2` on OSM tiles (driver / merchant / admin / supervisor) |
| Push | `firebase_messaging` + `flutter_local_notifications` (driver / merchant / admin) |
| i18n | Hand-rolled `AppLocalizations` — English + Arabic (RTL auto) |
| Fonts | Tajawal (Arabic) + Inter (Latin) via `google_fonts` |
| Theming | Material 3 |

**Common layout** inside each app's `lib/`:

```
lib/
├── main.dart                # ProviderScope boot + (optional) Firebase init
├── core/
│   ├── api/                 # DioClient + api_endpoints.dart registry
│   ├── config/              # Env loader (.env)
│   ├── error/               # ApiException (Laravel envelope mapper)
│   ├── storage/             # TokenStorage + TenantStorage (secure)
│   └── utils/               # json_x coercers (asInt / asDouble / asString / ...)
├── features/                # One folder per feature module
│   └── <feature>/
│       ├── domain/          # Immutable models + enums
│       ├── data/            # Repository + Riverpod providers
│       └── presentation/    # Screens / tabs / sheets
└── shared/
    ├── l10n/                # Localization delegate
    ├── router/              # go_router config + splash + auth-gate
    └── theme/               # AppTheme (light + dark)
```

**Common request envelope**

Every request carries two headers:

```
apiKey: <shared tenant key>          # from .env, currently the same across tenants
Authorization: Bearer <sanctum-token> # set after login
```

`DioClient` normalises Laravel's `{ status, message, data }` envelope so callers receive the inner `data` directly, and converts 401 responses into a token wipe + redirect to login.

**Tenant-aware install** — all eight apps use the same 2-gate boot flow:
1. `/tenant` screen — user enters a workspace subdomain (simple) or full API URL (advanced); ping `/general-settings` before persisting; save to `TenantStorage`.
2. `/login` screen — `POST /admin/login` (or `/deliveryman/login` on the driver app) → Sanctum token stored in `TokenStorage`.

Router redirect gates every non-public route on `tenant_configured && authed`. "Change workspace" wipes both and returns to `/tenant`.

---

## 2. `rushly-driver-app` — Driver mobile app

### Purpose
Delivery drivers use it to see assigned parcels, mark deliveries (delivered / partial / not-delivered with photo & reason), track earnings, view NDR (Non-Delivery Reports), and open support tickets.

### API surface
Driver-specific slice of `/api/v10/*`. All routes gated by `CheckApiKey` (static shared secret) + `auth:sanctum` after login.

| Group | Count | Sample endpoints |
|---|---|---|
| Auth | 7 | `POST /deliveryman/login`, `POST /otp-verification`, `POST /resend-otp`, `POST /password/email`, `POST /password/reset`, `POST /fcm-subscribe`, `POST /fcm-unsubscribe` |
| Driver dashboard | 7 | `GET /deliveryman/dashboard`, `/profile`, `/cash` (COD balance + handover history), `/payment-logs`, `/parcel-payment-logs`, `/parcel-status`, `/income-expense` |
| Parcels | 7 | `GET /deliveryman/parcel/index`, `/details/{id}`, `/by-tracking/{tracking}` (server-side lookup for scanned AWBs), `POST /delivered/{id}`, `POST /partial-delivered/{id}`, `POST /deliveryman/parcel-location-update` (now inside auth:sanctum — driver derived from the token, no more spoofable `deliveryID` in the body) |
| NDR | 6 | `GET /ndr/index`, `/stats`, `/{id}`, `/parcel/{parcelId}`, `POST /` (create — driver reports a failed attempt), `POST /notify` |
| Support | 7 | `GET /support/index`, `POST /support/create`, `/store`, `/view/{id}`, `/edit/{id}`, `/update/{id}`, `/reply`, `DELETE /delete/{id}` |
| Helpers | 5 | `GET /hub`, `/general-settings`, `/all-currencies`, `/settings/cod-charges`, `/settings/delivery-charges` |

### Current features
- Tenant-aware / SaaS-wise install (see §1)
- Login with driver id + password → Sanctum bearer stored in secure storage
- 5-tab shell: **Dashboard**, **Parcels**, **Earnings**, **Support**, **Profile**
- Parcel list with status badges + detail view. **Detail screen now includes a live tracking map** (`flutter_map` on OSM tiles) plotting pickup + customer + the driver's own latest known position.
- **AWB barcode scan** — camera scanner in the parcels AppBar (`mobile_scanner`); detected code is matched against the cached assigned list first, then falls back to `/deliveryman/parcel/by-tracking/{tracking}` which guards that the parcel is actually assigned to the caller.
- Delivery outcome flows with photo capture and rejection reasons
- **NDR create flow** — "Report NDR" action on parcel details opens a form and posts to `/ndr`
- Live location ping via `geolocator` (foreground only)
- Earnings dashboard: income / expense breakdown, payment logs, per-parcel settlement view + day-grouped Parcels tab
- **Dashboard KPI cards are clickable** — deep-links to `/parcels?status=X&label=Y` and `/earnings`
- **Route-optimised runsheet** — nearest-neighbor haversine ordering of today's assigned parcels with per-leg distance + Google Maps "directions" shortcut
- **Cash reconciliation screen** — outstanding COD + total handed over + chronological handover history
- Notifications inbox (SharedPreferences, capped at 100 FIFO)
- Push notifications via FCM (foreground handler wired)

---

## 3. `rushly-merchant-app` — Merchant mobile app

### Purpose
Merchants use it to create and track parcels, manage their shops, request payouts, view invoices/statements, run fraud checks on customer phone numbers, browse news/offers, and open support tickets.

### API surface
Merchant slice of `/api/v10/*`. ~70 endpoints across 9 groups.

| Group | Count | Sample endpoints |
|---|---|---|
| Auth | 9 | `POST /register`, `/signin`, `/otp-verification`, `/resend-otp`, `/password/email`, `/password/reset`, `GET /profile`, `GET /refresh`, `POST /sign-out` |
| Parcels | 13 | `GET /parcel/index`, `/details/{id}`, `/edit/{id}`, `/logs/{id}`, `/filter`, `/all-status`; `POST /parcel/create`, `/store`, `/status-update`; `PUT /parcel/update/{id}`; `DELETE /parcel/delete/{id}` |
| Shops | 5 | `GET /shops/index`, `POST /shops/store`, `GET /shops/edit/{id}`, `PUT /shops/update/{id}`, `DELETE /shops/delete/{id}` |
| Payments | 10 | `/payment-accounts/*` (5), `/payment-request/*` (5), `/account-transaction/index`, `/statements/index` + filters |
| Invoices | 2 | `GET /invoice-list/index`, `/invoice-details/{id}` |
| Dashboard | 5 | `GET /dashboard`, `/dashboard/filter`, `/dashboard/balance-details`, `/dashboard/available-parcels`, `/analytics` |
| Fraud | 6 | `GET /fraud/index`, `POST /fraud/store`, `GET /fraud/edit/{id}`, `PUT /fraud/update/{id}`, `POST /fraud/check`, `DELETE /fraud/delete/{id}` |
| Support | 7 | list, create, store, view/{id}, edit, update, reply (supports `attached_file` multipart), delete |
| Reports | 1 | `GET /reports/shipments?from=&to=` — totals + by_driver + by_city + by_status + daily timeseries |
| Bulk | 1 | `POST /parcel/bulk-store` — accepts `{rows: [...]}`, returns `{created, error_count, errors: [{row, errors}]}` |
| NDR | 1 | `GET /ndr/merchant` — paginated NDR feed scoped to the caller's merchant |
| Store connections | 1 | `GET /store-connections` — Salla / Zid / WooCommerce / Shopify shops with sync + last-event timestamps |

### Current features
- Tenant-aware / SaaS-wise install (see §1)
- Signup with OTP verification and password reset flows
- Bottom-nav shell: Dashboard, Parcels, Shops, Payments, Support (+ drawer for Fraud, News, Settings, Invoices)
- Dashboard with clickable KPI cards deep-linking to filtered parcel lists
- **Reports screen** — date-range picker + 4 tabs: Overview / By driver / By city / Trend (line chart)
- Parcel create/edit with live charge preview
- **Bulk parcel import (CSV)** — file picker → parse → preview → submit → per-row error report
- Parcel detail with tracking timeline + **live tracking map** (`flutter_map` on OSM tiles)
- Shops CRUD, payment accounts CRUD + withdrawal, statements + **PDF export** (`pdf` + `printing`)
- Fraud lookup + management
- **NDR feed** scoped to the merchant
- **Store connections view** with provider badges, sync + last-event timestamps
- Support tickets with client-side search + image attachments on replies
- Push notifications via FCM

---

## 4. `rushly-admin-app` — Back-office mobile app

### Purpose
Mobile back-office for the four non-merchant / non-driver user types: `SUPER_ADMIN`, `ADMIN`, `INCHARGE`, `HUB`. Manages parcels, drivers, merchants, hubs, payout approvals, support and fraud.

### API surface
Dedicated `/api/v10/admin/*` namespace — gated by `CheckApiKey` + `auth:sanctum` + `CheckAdminRole` middleware. **26+ endpoints**.

| Group | Count | Sample endpoints |
|---|---|---|
| Auth | 3 | `POST /admin/login`, `GET /admin/profile`, `POST /admin/logout` |
| Push | 2 | `POST /admin/fcm-subscribe`, `POST /admin/fcm-unsubscribe` |
| Map | 2 | `GET /admin/map/parcels` (unassigned + geo), `GET /admin/map/drivers` |
| Hub cash | 4 | `GET /admin/hub-cash`, `/hub-cash/drivers`, `/hub-cash/accounts`, `POST /admin/hub-cash` |
| WMS | 6 admin + 5 shared | `GET /admin/wms/grns`, `/locations`, `/cycle-counts` + `POST`, `/damage-reports` + `POST`. Shared: `/wms/products/lookup`, `/wms/stock/{id}`, `/wms/grn/{id}/scan`, `/wms/grn/{id}/complete`, `/wms/adjustments` |
| Dashboard | 2 | `GET /admin/dashboard`, `/admin/dashboard/timeseries` |
| Parcels | 7 | `GET /admin/parcels`, `/parcels/{id}`, `/parcels/{id}/logs`, `POST /parcels/{id}/assign-driver`, `POST /parcels/{id}/status`, `GET /parcels/{id}/3pl`, `POST /parcels/{id}/3pl-assign` |
| Drivers | 2 | `GET /admin/drivers`, `/drivers/{id}` |
| Merchants | 6 | `GET /admin/merchants`, `/merchants/pending`, `/merchants/{id}`, `POST /merchants/{id}/toggle-active`, `/approve`, `/reject` |
| Hubs | 2 | `GET /admin/hubs`, `/hubs/{id}` |
| Payment requests | 3 | `GET /admin/payment-requests`, `POST /payment-requests/{id}/approve`, `POST /payment-requests/{id}/reject` |
| Support | 4 | `GET /admin/support`, `/support/{id}`, `POST /support/{id}/reply`, `POST /support/{id}/close` |
| Fraud | 3 | `GET /admin/fraud`, `POST /admin/fraud`, `DELETE /admin/fraud/{id}` |

### Current features
- Tenant-aware / SaaS-wise install (see §1)
- Role-aware login (only ADMIN / SUPER_ADMIN / INCHARGE / HUB user types allowed)
- Dashboard with KPI cards + time-series chart
- Bottom nav: **Dashboard**, **Parcels**, **Drivers**, **Profile** (+ drawer for Merchants, Approvals, Hubs, Support, Fraud)
- **Role-gated navigation** — `Merchants` / `Approvals` only visible to `admin` / `super_admin`
- Parcel list + detail with assign-driver, force-status, and live tracking map
- **Merchant onboarding approval queue** — KYC review + approve / reject
- **Driver assignment map** — OSM-tiled with unassigned parcels + drivers, sorted-by-distance driver picker
- **Hub cash reconciliation** — outstanding COD + FAB to record deposits (HUB/INCHARGE only)
- **Warehouse (WMS) mobile** — stock lookup, GRN receiving, cycle count, damage reports
- **3PL assignment** — bottom sheet with 5 providers (Panda / Zajel / Aramex / Jet / Logestechs) + configured badge + past assignments
- Payout approvals, support tickets, fraud flag management
- Foreground push notifications via FCM

---

## 5. `rushly-supervisor-app` — Field supervisor app

### Purpose
Field supervisors monitor a hub's drivers, assign unassigned parcels, review per-driver performance, and triage exceptions.

### API surface
Reuses several admin endpoints + adds two dedicated ones.

| Group | Endpoint | Purpose |
|---|---|---|
| Live | `GET /admin/map/drivers` | Driver list with last-known GPS |
| Live | `GET /admin/drivers/{id}` | Driver detail + today's stats |
| Assign | `GET /admin/map/parcels` | Unassigned parcels with geo |
| Assign | `POST /admin/parcels/{id}/assign-driver` | Assign parcel to driver |
| Reports (new) | `GET /admin/reports/drivers?from=&to=&hub_id=` | Per-driver aggregates: parcels, delivered, cod, delivery-rate %. Hub-clamped. |
| Exceptions (new) | `GET /admin/exceptions?stuck_days=` | Aggregated attention feed: open NDRs + stuck parcels + returning-to-courier |

### Current features
- Seed colour: teal 800
- **Drivers** — live list with status pill, load, last-seen relative time, GPS indicator. Tap → driver detail (today's assigned/delivered, GPS + directions link).
- **Assignments** — unassigned parcels; tap opens a driver-picker sheet sorted by haversine distance; assign posts to `/admin/parcels/{id}/assign-driver`.
- **Reports** — date-range picker + totals header + per-driver rows with delivery-rate progress bar (green ≥80%, orange ≥50%, red otherwise).
- **Exceptions** — sectioned feed grouped by type (Open NDRs / Stuck / Returning) with counts + friendly "all clear" empty state.

---

## 6. `rushly-warehouse-app` — Warehouse WMS app

### Purpose
Warehouse-floor operations: receiving GRNs, stock lookup, picking & packing fulfillments, cycle counting, damage reporting, stock adjustment, and courier hand-off.

### API surface
Reuses the shared WMS API and admin WMS endpoints. Adds two dispatch endpoints.

| Group | Endpoint | Purpose |
|---|---|---|
| Receive | `GET /admin/wms/grns` | Open GRNs |
| Receive | `GET /wms/products/lookup?barcode=&sku=` | Product resolution |
| Receive | `GET /wms/stock/{productId}` | Per-location on-hand + reservations |
| Receive | `POST /wms/grn/{id}/scan` | Put item into GRN (product + location + qty + batch + condition) |
| Receive | `POST /wms/grn/{id}/complete` | Finalise the GRN |
| Pick & Pack | `GET /wms/fulfillment/my-tasks` | Picker's queue (PENDING/PICKING for me or unassigned) |
| Pick & Pack | `POST /wms/fulfillment/{id}/pick` | Confirm pick (item + qty) |
| Pick & Pack | `POST /wms/fulfillment/{id}/pack` | Flip PACKING → READY |
| Inventory | `GET /admin/wms/cycle-counts` + `POST` | Cycle count list + open session |
| Inventory | `GET /admin/wms/damage-reports` + `POST` | Damage report list + submit |
| Inventory | `POST /wms/adjustments` | Stock adjustment (auto-flags ≥20 % delta for supervisor approval) |
| Dispatch (new) | `GET /wms/fulfillment/ready-to-dispatch` | READY queue |
| Dispatch (new) | `POST /wms/fulfillment/{id}/dispatch` | Flip READY → DISPATCHED, release reservations, hand off to delivery-man workflow. Routes to `WmsFulfillmentApiController::confirmDispatch` — not `dispatch`, which collides with the base `Illuminate\Routing\Controller::dispatch($job)` signature and fatals on class load. |

### Current features
- Seed colour: brown 700
- **Receive** — stock-lookup entry card + open-GRNs list, tap → GRN scan screen with per-item put-away sheet (location + qty + batch + condition) + Complete action
- **Pick & Pack** — fulfillment queue with SLA badge + progress bar. Detail screen shows next-item card with per-item pick dialog (defaults to remaining qty). Pack button enabled when `items_remaining == 0`.
- **Inventory** — 2×2 grid: Stock Lookup / Cycle Count / Damage Reports / Adjust. Adjust bottom sheet resolves product by scan/type, picks a location, submits `POST /wms/adjustments` with a note about pending supervisor approval when the delta triggers it.
- **Dispatch** — READY queue + AWB scan FAB. Confirm dialog fires the dispatch endpoint which releases reservations, debits stock, and flips the linked parcel to `DELIVERY_MAN_ASSIGN`.

---

## 7. `rushly-sorting-app` — Hub sorting center app

### Purpose
Sorting-center operators scan parcels, group them into bags by destination hub, then dispatch bags in bulk to the outbound truck.

### API surface
Server-side surface is minimal — bags and routes are ephemeral device-side state; only the handover action touches server data.

| Group | Endpoint | Purpose |
|---|---|---|
| Lookup (new) | `GET /admin/sorting/lookup/{tracking}` | Resolve AWB to parcel with current + destination hub |
| Handover (new) | `GET /admin/sorting/hubs` | Destination-hub picker |
| Handover (new) | `POST /admin/sorting/handover` | Bulk `TRANSFER_TO_HUB`; HUB / INCHARGE users are clamped to their own hub |

### Current features
- Seed colour: deep purple 700
- **Scan In** — `mobile_scanner` FAB + manual entry, resolves AWB to a parcel card showing destination hub, current hub, customer/city/area, merchant, status, COD.
- **Sort** — scan → auto-drops parcel into the bag matching its destination hub (creates one if needed) with undo snackbar. Session-scoped bag list backed by a Riverpod `StateNotifier`.
- **Bags** — active + closed bags list, new-bag sheet with hub picker, close/remove menu, detail screen with per-parcel remove.
- **Routes** — bags grouped by destination hub with expansion tiles; Dispatch button posts `/admin/sorting/handover` (bulk `TRANSFER_TO_HUB` + `ParcelEvent` per parcel) then clears the local bags for that hub.

---

## 8. `rushly-fleet-app` — Fleet driver app

### Purpose
Fleet drivers log trips, run pre-trip vehicle inspections, record fuel fill-ups, and raise maintenance issues.

### API surface
Dedicated `/admin/fleet/*` namespace with 8 endpoints. `driver_id` is `Auth::id()` throughout so any authed user (admin, hub staff, or deliveryman) can use the app.

| Group | Endpoint | Purpose |
|---|---|---|
| Vehicle | `GET /admin/fleet/vehicle` | Assigned vehicle + any active trip |
| Trips | `GET /admin/fleet/trips` | Recent trip history |
| Trips | `POST /admin/fleet/trips` | Start trip (odometer + inspection JSON). Prevents concurrent in-progress trips per driver. |
| Trips | `POST /admin/fleet/trips/{id}/end` | End trip (end_odometer ≥ start_odometer). Auto-updates vehicle's `current_odometer`. |
| Fuel | `GET /admin/fleet/fuel` | Fuel log history |
| Fuel | `POST /admin/fleet/fuel` | Log fill-up (liters, cost, odometer, receipt URL) |
| Maintenance | `GET /admin/fleet/maintenance` | Report history |
| Maintenance | `POST /admin/fleet/maintenance` | Submit issue (type + severity + description) |

### Backend tables
Migration `2026_07_17_100000_create_fleet_tables.php` creates all four (`fleet_vehicles`, `fleet_trips`, `fleet_fuel_logs`, `fleet_maintenance_reports`), all company-scoped via the standard `Companywise` scope pattern.

### Current features
- Seed colour: indigo 700
- **Trips** — active-trip card at top + history list. Start-trip sheet includes a pre-trip inspection checklist (tires / brakes / lights / fluids / body) + odometer capture. End-trip sheet enforces end ≥ start; note that vehicle `current_odometer` is auto-updated on end.
- **Vehicle** — assigned-vehicle card with plate/make/model/year, current odometer, coloured status chip.
- **Fuel** — fill-up log list with FAB → log sheet (liters, cost, odometer, receipt URL, notes).
- **Maintenance** — severity-coloured report list (critical / high / medium / low) with FAB → report sheet (issue type + severity dropdown + description).

---

## 9. `rushly-scanner-app` — Universal scanner app

### Purpose
A lightweight universal scanner for anyone in the pipeline — camera scan an AWB, see the parcel, and apply the status transition that makes sense from where you're standing.

### API surface
Zero new endpoints — reuses the sorting lookup + existing force-status endpoint.

| Endpoint | Purpose |
|---|---|
| `GET /admin/sorting/lookup/{tracking}` | Resolve AWB (returns parcel + current + destination hub) |
| `POST /admin/parcels/{id}/status` | Set status (with note) — same endpoint used by the admin app |

### Current features
- Seed colour: deep orange 700
- **Scan** — `mobile_scanner` FAB with torch + flip-camera controls. Resolves AWB → parcel card with a chip showing the human-readable status label. Then shows a **status-aware action strip**: e.g. `TRANSFER_TO_HUB` → "Received by hub" button, `RECEIVED_BY_PICKUP_MAN` → "At warehouse", `DELIVERY_MAN_ASSIGN` → "Delivered", `RETURN_TO_COURIER` → "Return received". Confirm dialog before firing the status change, then re-lookup to refresh.
- **History** — device-local scan history backed by SharedPreferences (JSON, capped at 100 FIFO, 30 s dedupe window on same tracking). Shows tracking + status label + action taken (or "Lookup only" / "Not found"). FAB clears history.
- Status → action mapping lives in `lib/features/scanner/domain/action_catalog.dart` so the Scan buttons and History labels stay in sync.

---

## 10. Cross-cutting new features / roadmap

Features worth building across the apps at once:

| Feature | Notes |
|---|---|
| Two-step login (email OTP) — parity with web | Web already has this behind `features.login_otp`. See `login-otp-two-step` memory. |
| Per-tenant API keys (rotate away from shared `apiKey`) | Security debt — every app currently ships the same static key. |
| Deep-linking (open a parcel by AWB from a push notification) | FCM handler → `go_router` push. |
| In-app changelog / release notes | Reuse `news-offer` on merchant side; add a variant for driver/admin. |
| Offline read cache | Parcels list / dashboard should render last-known state offline. |
| Biometric unlock | `local_auth` gate before the app resumes to sensitive screens. |
| App icons + splash screens | Missing across most apps — `flutter_launcher_icons` + `flutter_native_splash`. |
| Automated tests (auth / feature happy-paths) | Skeletons only today. |

Backend prerequisites:

- Move away from shared `apiKey` header to per-tenant / per-app keys, or drop `CheckApiKey` in favour of Sanctum-only.
- Publish OpenAPI / Postman spec for `/api/v10/*` so the eight apps stay in sync as endpoints evolve.
- Fleet: add supervisor UI for maintenance resolution (currently mobile-only submission; resolution is web-side manual).

---

## 11. Build & run (any app)

```bash
cd /var/www/rushly-<driver|merchant|admin|supervisor|warehouse|sorting|fleet|scanner>-app

# 1. environment
cp .env.example .env
# fill: API_BASE_URL (or leave blank for tenant-select flow), API_KEY

# 2. assets (fonts required to compile)
# drop Tajawal-Regular/Medium/Bold into assets/fonts/

# 3. firebase (only for driver / merchant / admin — FCM push)
# android: android/app/google-services.json
# ios:     ios/Runner/GoogleService-Info.plist

# 4. install + run
flutter pub get
flutter run --dart-define-from-file=.env

# release builds
flutter build apk --release        # Android APK
flutter build appbundle --release  # Android AAB (Play Store)
flutter build ipa --release        # iOS IPA (macOS + Xcode)
```

---

## 12. Where things live in the backend

Quick pointers back into `rushly-saas` for anyone tracing a mobile call:

| Mobile app | Backend controllers | Route file |
|---|---|---|
| Driver | `app/Http/Controllers/Api/V10/{Auth,Deliveryman*,Ndr,Support,PushNotification}Controller.php` | `routes/api.php` (v10 group) |
| Merchant | `app/Http/Controllers/Api/V10/{Auth,Dashboard,Parcel,Shops,PaymentAccount,PaymentRequest,Statements,Invoice,Fraud,Support,NewsOffer,Settings,MerchantReports,MerchantStoreConnections}Controller.php` | `routes/api.php` (v10 group) |
| Admin | `app/Http/Controllers/Api/V10/Admin/*Controller.php` | `routes/api.php` (v10/admin group) |
| Supervisor | `app/Http/Controllers/Api/V10/Admin/{AdminReports,AdminExceptions,AdminMap,AdminParcel}Controller.php` | `routes/api.php` (v10/admin group) |
| Warehouse | `app/Http/Controllers/Api/V10/Wms/{WmsFulfillment,WmsGrn,WmsProduct,WmsStock,WmsAdjustment}ApiController.php` + `app/Http/Controllers/Api/V10/Admin/AdminWmsController.php` | `routes/api.php` (v10/wms + v10/admin groups) |
| Sorting | `app/Http/Controllers/Api/V10/Admin/AdminSortingController.php` | `routes/api.php` (v10/admin group) |
| Fleet | `app/Http/Controllers/Api/V10/Fleet/FleetDriverApiController.php` | `routes/api.php` (v10/admin group) |
| Scanner | Reuses `AdminSortingController::lookup` + `AdminParcelController::forceStatus` | `routes/api.php` (v10/admin group) |

---

## 13. Settings → Mobile Apps directory page (in-tenant)

A read-only catalog of the eight apps rendered inside every tenant admin so staff can discover which app to install for their role without leaving the platform.

**URL** — `https://<tenant>.<APP_URL>/admin/settings/mobile-apps` (tenant-scoped; the route is registered inside the `if ($domain) :` gate in `routes/web.php:175` so it's invisible to `php artisan route:list` — see the `routes-tenant-domain-gate` memory).

**Discoverability**
- **Sidebar** — under *Settings* group, right after *Public Tracking API* (`resources/views/backend/partials/sidebar.blade.php:726-731`)
- **Settings hub** — card in the *Identity & branding* group at `/admin/settings` (`app/Http/Controllers/Backend/SettingsHubController.php:30`)

**Files**

| File | Purpose |
|---|---|
| `app/Http/Controllers/Backend/MobileAppsController.php` | Static catalog — returns 8 app rows (key / title / audience / description / icon / gradient / repo) via a private `apps()` method. No DB access. |
| `resources/views/backend/settings/mobile-apps.blade.php` | Blade page. Uses the new-UI conventions (Tailwind CDN with `tw-` prefix, `@section('maincontent')`, breadcrumb, brand-50 icon badge, `tw-rounded-xl tw-shadow-card` white surface). Responsive 1→2→3→4 column grid of app tiles with per-app inline-styled gradient icon. |
| `lang/{en,ar}/mobile_apps.php` | Titles, audience labels, descriptions, subtitle, footer note. Bilingual (RTL auto-flips via the master layout). |
| `database/migrations/2026_07_22_230000_seed_mobile_apps_permission.php` | Idempotent backfill. Inserts the `mobile_apps` row into `permissions` + `super_admin_permissions`, then grants `mobile_apps_read` to any role/user that already has `general_settings_read`, plus always to the `super-admin` role. Mirrors the pattern from `2026_05_24_000004_seed_integrations_permissions.php`. |

**Route registration** — `routes/web.php:790-792`

```php
Route::get('settings/mobile-apps', [MobileAppsController::class, 'index'])
    ->middleware('hasPermission:mobile_apps_read')
    ->name('mobile-apps.index');
```

**Permission** — `mobile_apps_read`. Registered in both attribute maps of `PermissionSeeder.php` (tenant `attributes` + `super_admin_attributes`) and included in the Admin + User role permission lists in `RoleSeeder.php` / `UserSeeder.php`. Fresh tenants get it via the seeder; existing tenants get it via the backfill migration above.

**UI reference pages** — Redesigned in commit `f88b897` and later; consistent with `resources/views/backend/database-backup/index.blade.php` and `resources/views/backend/super-admin/plan/index.blade.php`. Uses brand palette + `shadow-card` tokens defined in `resources/views/backend/partials/header.blade.php:33-56`.

**Non-goals (intentional)**
- No per-app download URLs (Play Store / App Store / APK) — there's no `mobile_app_links` config or settings table yet; if you want download buttons, add them either as `config/mobile_apps.php` keys or as a new per-tenant `mobile_app_links` table.
- No dynamic feature flags per app (e.g. hide Fleet card if `features.fleet` is off) — the catalog is fully static; gate at the controller if you need feature-flagged visibility later.

WMS mobile endpoints (used by the driver + warehouse apps) live in `app/Http/Controllers/Api/V10/Wms/*` and are mounted inside the `/api/v10/wms` group with `auth:sanctum`.
