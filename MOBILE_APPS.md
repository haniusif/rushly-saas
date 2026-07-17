# Rushly Mobile Apps

Companion doc to `RUSHLY_APPS_OVERVIEW.md`. That file covers the storefront bridges (Salla / Zid / WooCommerce / Shopify). This file covers the **eight Flutter mobile apps** that consume `rushly-saas` `/api/v10/*` endpoints.

## The 8 apps at a glance

| # | Repo | User | State | What it does |
|---|---|---|---|---|
| 1 | `rushly-driver-app` | Delivery drivers (deliverymen) | **Feature-complete** | Assigned parcels, delivery outcomes (delivered/partial/not-delivered with photo), NDR, earnings, cash reconciliation, live tracking map, AWB scan, route-optimised runsheet |
| 2 | `rushly-merchant-app` | Merchants / shop owners | **Feature-complete** | Parcel CRUD, bulk CSV import, tracking map, shops, payments (accounts + requests + statements PDF), invoices, fraud, NDR, store connections, reports |
| 3 | `rushly-admin-app` | Back-office (super_admin, admin, incharge, hub) | **Feature-complete** | Dashboard, parcels, drivers, merchants (including onboarding approval queue), hubs, payouts, support, fraud, driver-assignment map, hub cash, WMS (stock lookup + GRN receiving + cycle count + damage reports), 3PL assign |
| 4 | `rushly-supervisor-app` | Field supervisors | **Scaffold** | Monitor drivers, assign deliveries, live tracking, performance dashboards, exception management |
| 5 | `rushly-warehouse-app` | Warehouse staff | **Scaffold** | Receiving, put-away, picking, packing, inventory counting, barcode scanning, dispatch, returns |
| 6 | `rushly-sorting-app` | Hub sorting operations | **Scaffold** | Shipment sorting, scan-in/scan-out, bag & container management, route assignment |
| 7 | `rushly-fleet-app` | Long-haul fleet drivers | **Scaffold** | Trips, vehicle inspection, fuel logging, GPS tracking, maintenance, checkpoints |
| 8 | `rushly-scanner-app` | Any pipeline staff | **Scaffold** | Dedicated barcode / RFID scanning — shipments, inventory, asset tracking |

All eight live as sibling repositories to `rushly-saas/` (e.g. `/var/www/rushly-supervisor-app`, etc.). The three feature-complete apps ship as `main`-branch repos on GitHub; the five scaffolds are checked into local worktrees only — commit + push whenever you want to publish them.

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
| Secure storage | `flutter_secure_storage` (bearer token + user id) |
| Push | `firebase_messaging` + `flutter_local_notifications` |
| i18n | Hand-rolled `AppLocalizations` — English + Arabic (RTL auto) |
| Fonts | Tajawal (Arabic) + Inter (Latin) via `google_fonts` |
| Theming | Material 3 |

**Common layout** inside each app's `lib/`:

```
lib/
├── main.dart                # ProviderScope boot + Firebase init
├── core/
│   ├── api/                 # DioClient + api_endpoints.dart registry
│   ├── config/              # Env loader (.env)
│   ├── error/               # ApiException (Laravel envelope mapper)
│   ├── push/                # FCM service
│   ├── storage/             # TokenStorage (secure)
│   └── utils/               # JSON coercers, ParcelStatus enum
├── features/                # One folder per feature module
└── shared/
    ├── l10n/                # Localization delegate
    ├── router/              # go_router config + splash
    └── theme/               # AppTheme
```

**Common request envelope**

Every request carries two headers:

```
apiKey: <shared tenant key>          # from .env, currently the same across tenants
Authorization: Bearer <sanctum-token> # set after login
```

`DioClient` normalises Laravel's `{ status, message, data }` envelope so callers receive the inner `data` directly, and converts 401 responses into a token wipe + redirect to login.

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

Controller stack on the backend: `app/Http/Controllers/Api/V10/{Auth,Deliveryman,DeliveryManParcel,DeliverymanController,DeliveryManIncomeExpense,Ndr,Support,GeneralSetting,Settings,PushNotification}Controller.php`.

### Current features
- **Tenant-aware / SaaS-wise install** — first launch prompts for a workspace subdomain (or full URL in advanced mode); pings `/general-settings` before persisting; profile screen shows current workspace + "Change workspace" action. One APK, any tenant.
- Login with driver id + password → Sanctum bearer stored in secure storage
- 5-tab shell: **Dashboard**, **Parcels**, **Earnings**, **Support**, **Profile**
- Parcel list with status badges + detail view. **Detail screen now includes a live tracking map** (`flutter_map` on OSM tiles) plotting pickup + customer + the driver's own latest known position. Same widget shape as the merchant app.
- **AWB barcode scan** — camera scanner in the parcels AppBar (`mobile_scanner`); detected code is matched against the cached assigned list first, then falls back to a server lookup at `/deliveryman/parcel/by-tracking/{tracking}` which guards that the parcel is actually assigned to the caller.
- Delivery outcome flows with photo capture and rejection reasons (delivered / partial / not-delivered)
- **NDR create flow** — "Report NDR" action on parcel details opens a form (failure-reason dropdown from the backend enum + optional notes + optional next-attempt date), posts to `/ndr` and refreshes the list.
- Live location ping via `geolocator` (foreground only)
- Earnings dashboard: income / expense breakdown, payment logs, per-parcel settlement view + coloured summary (income/expense/net) with parcel-deep-link chips and day-grouped Parcels tab.
- **Dashboard KPI cards are clickable** — balance/earnings/COD tiles open the earnings or cash screen; in-progress/delivered tiles open the parcel list pre-filtered to that status. Bucket sections have a "View all" shortcut. `/parcels?status=X&label=Y` and `/earnings` are now first-class deep-linkable routes.
- **Route-optimised runsheet** — new screen accessed from the parcels list AppBar. Grabs the driver's current GPS via `geolocator`, then greedily orders today's assigned parcels using nearest-neighbor haversine. Each stop shows the leg distance in km + a Google Maps "directions" shortcut. Not the optimal TSP solution but plenty good for a half-day 5-30 stop run.
- **Cash reconciliation screen** — profile entry showing outstanding COD (positive-normalised for display) + total handed over + a chronological handover history from `/deliveryman/cash`. Read-only; mirrors what the hub sees in `AdminHubCashController`.
- Support ticket create/reply/view
- NDR list + stats + notify
- Push notifications via FCM (foreground handler wired)

### Known gaps (candidates for new features)
- Background / persistent location tracking (currently foreground only)
- Offline queueing for delivery outcome submissions (photo + status)
- Chat with hub incharge or merchant on a specific parcel

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
| Bulk | 1 | `POST /parcel/bulk-store` — accepts `{rows: [...]}`, validates each row with the same `StoreRequest` rules, returns `{created, error_count, errors: [{row, errors}]}` |
| NDR | 1 | `GET /ndr/merchant` — paginated NDR feed scoped to the caller's merchant (joined on `parcels.merchant_id`) |
| Store connections | 1 | `GET /store-connections` — the merchant's connected Salla / Zid / WooCommerce / Shopify shops with sync + last-event timestamps (secrets hidden by the model) |
| Helpers | 6 | `/hub`, `/general-settings`, `/all-currencies`, `/settings/cod-charges`, `/settings/delivery-charges`, `/news-offer/index` |

### Current features
- **Tenant-aware / SaaS-wise install** — first launch prompts for a workspace subdomain (or full URL in advanced mode); pings `/general-settings` before persisting; profile screen shows current workspace + "Change workspace" action. One APK, any tenant. (Same design as admin and driver apps — all three share the tenant-select pattern.)
- Signup with OTP verification and password reset flows
- Bottom-nav shell: Dashboard, Parcels, Shops, Payments, Support (+ drawer for Fraud, News, Settings, Invoices)
- Dashboard: balance card, pending/delivered counts, analytics via `fl_chart`, "Reports" button in AppBar. **All KPI cards clickable** — balance opens the payments hub; parcel-count tiles open pre-filtered parcel lists (`/parcels?status=X`); status-list rows do the same.
- **Reports screen** — date-range picker + 4 tabs: Overview (KPI cards), By driver, By city, Trend (line chart)
- Parcel create/edit form (recipient, address, items, COD, payment method) + **live charge preview** (delivery + COD + fragile/liquid + VAT + net) computed client-side from the same reference data the server uses
- **Bulk parcel import (CSV)** — file picker → parse → preview with per-row issue highlighting → submit → per-row error report from backend
- Parcel detail with tracking timeline (`/parcel/logs/{id}`) + **live tracking map** (`flutter_map` on OSM tiles): plots pickup point, customer destination, and driver's latest known position from the most recent parcel event's `delivery_lat/long`. Empty-state card renders when no coords are available. Backend `ParcelResource` + `ParcelLogsResource` now expose the geo fields.
- Shops CRUD
- Payment accounts CRUD + withdrawal (payment request) flow
- Statements + account transactions + **PDF export** (Statements tab FAB → `pdf` + `printing` → native share sheet)
- Invoices list + detail view
- Fraud: check a phone number, view / add / delete flagged customers
- News / offers feed
- Support tickets with **client-side search** and **image attachments** on replies (multipart to existing `/support/reply`)
- **NDR (failed attempts) feed** — profile entry opens a merchant-scoped list from `/ndr/merchant`, coloured by status (open/resolved/returned), tap a row to jump to the parcel details.
- **Store connections view** — profile entry showing the merchant's connected Salla / Zid / WooCommerce / Shopify shops with per-provider badge, connection domain, sync + last-event + last-tested timestamps, and a "Default" star.
- Push notifications via FCM

### Known gaps (candidates for new features)
- In-app onboarding tour (`TOURS.md` copy exists but not wired here)
- Wallet top-up / online payment inside the app (currently redirect-only in web)
- WMS visibility (stock levels for merchants using the warehousing service)
- Return / NDR consent screen (currently only in web merchant panel)
- Multi-shop switcher header (currently one flat parcel list)

---

## 4. `rushly-admin-app` — Back-office mobile app

### Purpose
Mobile back-office for the four non-merchant / non-driver user types: `SUPER_ADMIN`, `ADMIN`, `INCHARGE`, `HUB`. Manages parcels, drivers, merchants, hubs, payout approvals, support and fraud.

### API surface
Dedicated `/api/v10/admin/*` namespace — gated by `CheckApiKey` + `auth:sanctum` + `CheckAdminRole` middleware. **26 endpoints**.

| Group | Count | Sample endpoints |
|---|---|---|
| Auth | 3 | `POST /admin/login`, `GET /admin/profile`, `POST /admin/logout` |
| Push | 2 | `POST /admin/fcm-subscribe`, `POST /admin/fcm-unsubscribe` (topic derived server-side from authenticated user email) |
| Map | 2 | `GET /admin/map/parcels` (unassigned + geo), `GET /admin/map/drivers` (last-known location from most recent ParcelEvent) |
| Hub cash | 4 | `GET /admin/hub-cash` (recent entries), `GET /admin/hub-cash/drivers` (with current_balance), `GET /admin/hub-cash/accounts` (caller's deposit accounts), `POST /admin/hub-cash` (record; HUB/INCHARGE only — delegates to ReceivedRepository) |
| WMS | 6 admin + 5 shared | `GET /admin/wms/grns` (open GRNs with received/expected totals), `GET /admin/wms/locations`, `GET /admin/wms/cycle-counts` + `POST` (open a new counting session), `GET /admin/wms/damage-reports` + `POST` (record damage: product/location/qty/cause/action/notes). The scanner flow reuses `/wms/products/lookup`, `/wms/stock/{id}`, `/wms/grn/{id}/scan`, `/wms/grn/{id}/complete`, `/wms/adjustments` from the shared driver-facing API. |
| Dashboard | 2 | `GET /admin/dashboard`, `/admin/dashboard/timeseries` |
| Parcels | 7 | `GET /admin/parcels`, `/parcels/{id}`, `/parcels/{id}/logs`, `POST /parcels/{id}/assign-driver`, `POST /parcels/{id}/status`, `GET /parcels/{id}/3pl` (provider status + past assignments), `POST /parcels/{id}/3pl-assign` (delegates to `ParcelController::ThirdPartyLogistics`) |
| Drivers | 2 | `GET /admin/drivers`, `/drivers/{id}` |
| Merchants | 6 | `GET /admin/merchants`, `/merchants/pending`, `/merchants/{id}`, `POST /merchants/{id}/toggle-active`, `/merchants/{id}/approve`, `/merchants/{id}/reject` |
| Hubs | 2 | `GET /admin/hubs`, `/hubs/{id}` |
| Payment requests | 3 | `GET /admin/payment-requests`, `POST /payment-requests/{id}/approve`, `POST /payment-requests/{id}/reject` |
| Support | 4 | `GET /admin/support`, `/support/{id}`, `POST /support/{id}/reply`, `POST /support/{id}/close` |
| Fraud | 3 | `GET /admin/fraud`, `POST /admin/fraud`, `DELETE /admin/fraud/{id}` |

Controllers: `app/Http/Controllers/Api/V10/Admin/*Controller.php`.

### Current features
- **Tenant-aware / SaaS-wise install** — first launch prompts for a workspace (Slack-style). Two input modes: (a) type a subdomain like `acme` and the app builds `https://acme.<TENANT_HOST_SUFFIX>/api/v10`, or (b) advanced mode accepts a full URL for custom domains and staging. Before persisting, the app pings `/general-settings` to prove the URL is a live Rushly API. The choice is stored in `flutter_secure_storage` and read by `DioClient` at construction; the router's redirect gates every route on `tenant_configured && authed`. Profile screen shows the current workspace host and a "Change workspace" action that wipes tenant + token and returns the user to the tenant-select screen. One APK, any tenant.
- Role-aware login (only ADMIN / SUPER_ADMIN / INCHARGE / HUB user types allowed)
- Dashboard with KPI cards + time-series chart
- Bottom nav: **Dashboard**, **Parcels**, **Drivers**, **Profile** (+ drawer for Merchants, Approvals, Hubs, Support, Fraud)
- **Role-gated navigation**: `Merchants` and `Approvals` only visible to `admin` / `super_admin` (hub users are automatically clamped server-side too)
- Parcel list with filters + detail view + assign-driver + force-status actions. **Detail screen now includes a live tracking map** (`flutter_map` on OSM tiles) plotting pickup + customer + driver's last-known position — same widget shape as the merchant and driver apps.
- Driver list + detail (with GPS + daily stats)
- Merchant list + detail + activate/deactivate toggle
- Hub list + detail
- Payout approvals queue (approve / reject payment requests)
- **Merchant onboarding approval queue** — dedicated drawer entry for `admin` / `super_admin`. Reads `/admin/merchants/pending`, renders KYC (CR, tax, IBAN, national address, uploaded documents) on a review screen, and posts to `/merchants/{id}/approve` or `/merchants/{id}/reject`. Approving sets `merchant.status = 1` + `user.status = 1` + `user.verification_status = 1`; rejecting sets `merchant.status = 2` so the row drops out of the pending list without touching the user record beyond `status = 0`.
- **Driver assignment map** — OSM-tiled `flutter_map` view with two toggleable marker layers: unassigned parcels (from `customer_lat/long`) and drivers (last-known location derived from their most recent `ParcelEvent`). Tap a parcel → bottom sheet lists drivers sorted by haversine distance → tap `Assign` → posts to existing `/admin/parcels/{id}/assign-driver`. Hub-scoped users are auto-clamped by the backend to their own hub.
- **Hub cash reconciliation** — shows drivers holding outstanding COD (sorted by amount owed) alongside the recent-reconciliations feed. HUB/INCHARGE users get a FAB to record a new deposit (driver + account + amount + date + note); admin/super_admin get read-only history across all hubs. Write path delegates to the same `ReceivedRepository` the web panel uses, so hub balances, deliveryman statements, and bank-transaction ledgers all stay in sync.
- **Warehouse (WMS) mobile** — drawer entry opens a hub with four tiles: **Stock lookup** (barcode scanner via `mobile_scanner`, falls back to typed SKU/barcode; shows on-hand / available / reorder point + per-location stock rows), **Receive goods** (list of open GRNs; tap opens a scan session where each scanned item pops a bottom sheet for location + qty + batch + condition, POSTs to `/wms/grn/{id}/scan`, and a Complete action finalises via `/wms/grn/{id}/complete`), **Cycle count** (session list + a bottom-sheet form to open a new session with hub/scope/zone), and **Damage reports** (list of past reports + a form to record product/location/qty/cause/action/notes). Reuses the existing driver-facing WMS API for scan writes; new admin endpoints back the cycle-count + damage flows.
- **3PL assignment from mobile** — parcel detail screen has an "Assign to 3PL" action. The bottom sheet lists all five providers (Panda / Zajel / Aramex / Jet / Logestechs) with a live `configured` flag driven by each provider service's `isConfigured()`. Past assignments are shown with AWB + tappable label URL. The write endpoint delegates to the existing web `ParcelController::ThirdPartyLogistics` so behavior is identical to the web panel — same payload, same `parcels_3pl` audit trail.
- Support tickets (reply + close)
- Fraud flag list (add / delete)
- Foreground push notifications via FCM

### Known gaps (candidates for new features)
- Invoicing / billing views for super_admin
- Cross-tenant view for super_admin (currently one tenant per session)

---

## 5. Cross-cutting new features / roadmap

Features worth building across all three apps at once:

| Feature | Driver | Merchant | Admin | Notes |
|---|:---:|:---:|:---:|---|
| Two-step login (email OTP) — parity with web | ✓ | ✓ | ✓ | Web already has this behind `features.login_otp`. See `login-otp-two-step` memory. |
| Per-tenant API keys (rotate away from shared `apiKey`) | ✓ | ✓ | ✓ | Security debt — every app currently ships the same static key. |
| Deep-linking (open a parcel by AWB from a push notification) | ✓ | ✓ | ✓ | FCM handler → `go_router` push. |
| In-app changelog / release notes | ✓ | ✓ | ✓ | Reuse `news-offer` on merchant side; add a variant for driver/admin. |
| Offline read cache | ✓ | ✓ | ✓ | Parcels list / dashboard should render last-known state offline. |
| Biometric unlock | ✓ | ✓ | ✓ | `local_auth` gate before the app resumes to sensitive screens. |
| App icons + splash screens | ✓ | ✓ | ✓ | Missing across all three — `flutter_launcher_icons` + `flutter_native_splash`. |
| Automated tests (auth / parcel flow) | ✓ | ✓ | ✓ | Skeletons only today. |

Backend prerequisites (for `rushly-saas` to unlock the above):

- Move away from shared `apiKey` header to per-tenant / per-app keys, or drop `CheckApiKey` in favour of Sanctum-only for these apps
- Authenticate `POST /deliveryman/parcel-location-update` (currently open, driver identifier in body)
- Publish OpenAPI / Postman spec for `/api/v10/*` so the three apps stay in sync as endpoints evolve

---

## 6. Build & run (any of the three apps)

```bash
cd /var/www/rushly-<driver|merchant|admin>-app

# 1. environment
cp .env.example .env
# fill: API_BASE_URL, API_KEY, (optional) GOOGLE_MAPS_API_KEY

# 2. assets (fonts required to compile)
# drop Tajawal-Regular/Medium/Bold into assets/fonts/

# 3. firebase (optional; needed for FCM)
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

## 7. Where things live in the backend

Quick pointers back into `rushly-saas` for anyone tracing a mobile call:

| Mobile app | Backend controllers | Route file |
|---|---|---|
| Driver | `app/Http/Controllers/Api/V10/{Auth,Deliveryman*,Ndr,Support,PushNotification}Controller.php` | `routes/api.php` (v10 group) |
| Merchant | `app/Http/Controllers/Api/V10/{Auth,Dashboard,Parcel,Shops,PaymentAccount,PaymentRequest,Statements,Invoice,Fraud,Support,NewsOffer,Settings}Controller.php` | `routes/api.php` (v10 group) |
| Admin | `app/Http/Controllers/Api/V10/Admin/*Controller.php` | `routes/api.php` (v10/admin group) |

WMS mobile endpoints (used by the driver app in the field, and eventually by an admin warehouse view) live in `app/Http/Controllers/Api/V10/Wms/*` and are also mounted inside the `/api/v10` group with `auth:sanctum`.

---

## 8. Scaffolded apps (Supervisor / Warehouse / Sorting / Fleet / Scanner)

Five apps scaffolded in one pass (see `/tmp/scaffold_apps.py`). Each is a working Flutter project sharing the same architecture as the three feature-complete apps:

- **Tenant-aware install** — TenantStorage + `/tenant` route + `/general-settings` ping before persisting. One APK per app, any tenant.
- **Login screen** — email/password → Sanctum token (posts to `/admin/login`, shared endpoint; each app's real user type will be wired when features land).
- **Home shell** — bottom-nav with per-app placeholder tabs (each tab is a `PlaceholderScreen` rendering the feature label + description + a "coming soon" chip).
- **Router redirect** — same 2-gate pattern (tenant → auth) as admin/driver/merchant.
- **Change workspace** — AppBar action confirms + wipes token+tenant → back to `/tenant`.
- **AR/EN l10n** with `LanguageToggleButton` in the login AppBar.
- **Theming** — Material 3, per-app primary seed colour.

### Per-app placeholder tabs

| App | Seed colour | Bottom tabs |
|---|---|---|
| Supervisor | teal 800 | Drivers · Assignments · Reports · Exceptions |
| Warehouse | brown 700 | Receive · Pick & Pack · Inventory · Dispatch |
| Sorting | deep purple 700 | Scan In · Sort · Bags · Routes |
| Fleet | indigo 700 | Trips · Vehicle · Fuel · Maintenance |
| Scanner | deep orange 700 | Scan · History |

### Follow-up work per app

None of these have backend endpoints wired to their tabs yet. Turning a scaffold into a functional app means, per tab:

1. Backend controller under `app/Http/Controllers/Api/V10/<AppName>/` (or reuse admin/driver equivalents).
2. Routes in `routes/api.php` inside the appropriate auth group.
3. Mobile: `features/<tab>/` module with domain + repo + presentation, wire into home shell (replace `PlaceholderScreen` with the real widget).

The scaffolds already have `dio_client`, `providers.dart`, `api_endpoints.dart`, and `auth_repository.dart` in place so each new feature only needs its own repository method + screen — no plumbing.
