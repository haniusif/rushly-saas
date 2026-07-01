# Rushly SaaS — Architecture Reference

A field guide to the codebase. Lists what lives where, what it does, and how the pieces wire together. Aimed at an engineer new to the project who needs to find their way around without grep'ing 70+ controllers.

---

## 1. What This App Is

**Rushly** is a multi-tenant **logistics / courier management SaaS**, built on Laravel 10. It is sold as an installable platform for delivery/logistics companies. A single deployment hosts:

- A **central domain** (the SaaS marketing site + super-admin) where logistics companies sign up
- **Tenant subdomains** (one per customer company) running the actual operational app — admins, hub managers, merchants, delivery men all log into their own subdomain

Each tenant runs the full courier workflow: parcels, hubs, delivery men, merchants, payments, reporting, accounting, payroll, support, fraud, news.

---

## 2. Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 10 (`^10.10`) |
| PHP | `^8.1` (this codebase **does not run on 8.4** — old vendor deps trigger fatal deprecations; use PHP 8.3) |
| DB | MySQL (`mysqli` + `pdo` extensions required) |
| Multi-tenancy | `stancl/tenancy` v3.7 (shared DB, per-tenant cache/filesystem) |
| Frontend | Blade views with pre-compiled `public/css` + `public/js` (no `@vite` / `mix()` references — Vite config exists but is unused) |
| Auth | Laravel UI + Sanctum (API) + Socialite (OAuth) |
| Auditing | `spatie/laravel-activitylog` |
| PDF | `carlos-meneses/laravel-mpdf` |
| Excel | `maatwebsite/excel` |
| Barcodes | `milon/barcode` |
| Payments | Stripe, PayPal, Razorpay, Paytm, Skrill, bKash, SSLCommerz, AamarPay |
| SMS | Twilio, Vonage |

---

## 3. Multi-Tenancy Model

Powered by `stancl/tenancy` with a **shared single database** — all tenants live in one DB. Tenant scoping is enforced at the **application layer** via:

1. The `domains` table — maps a request hostname to a `tenant_id`
2. Stancl middleware (`InitializeTenancyByDomain`) — sets the current tenant from the host
3. Per-model `company_id` columns + `scopeCompanywise()` query scopes — prevent cross-tenant leakage on shared tables

### Routing flow

```
Request → nginx (Valet) → public/index.php
       → Laravel router
       → if subdomain matches a `domains` row → "tenant" routes (web.php groups gated by InitializeTenancyByDomain)
       → else                                  → "central" routes (superadmin.php + central groups in web.php)
```

### Local tenant setup (this dev env)

Central: `https://rushly.test`
Tenants currently registered in `domains`:

| domain | tenant_id |
|---|---|
| `admin.rushly.test` | `rushly-logistic` |
| `tolgaplusa.rushly.test` | `TolgaPlusa` |

Tenants in `tenants` table without domain rows: `oms`, `admin` (not currently routable locally).

> ⚠️ `config/tenancy.php` lists central domains as `127.0.0.1, localhost`. If you change the central domain (e.g. to `rushly.test`), you must add it to the central-domains list there too — otherwise Stancl will treat it as a tenant lookup.

---

## 4. Directory Map

```
app/
├── Console/Commands/        # invoice:generate, DatabaseAutoBackup
├── Enums/                   # ParcelStatus, UserType, Status, PaymentType, etc. (~24)
├── Exceptions/Handler.php
├── Exports/                 # Excel exporters (parcels, shipments, reports)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # ParcelController (read-only details)
│   │   ├── Api/V10/         # REST API (sanctum-protected + public)
│   │   ├── Auth/            # Login, Register, password reset, verify
│   │   ├── Backend/         # ~70 admin/operations controllers (the bulk of the app)
│   │   │   ├── HubPanel/    # Hub manager dashboard
│   │   │   ├── MerchantPanel/  # Merchant self-service
│   │   │   ├── Superadmin/  # Tenant company + plan management
│   │   │   └── FrontWeb/    # Public marketing-site CMS
│   │   ├── Frontend/        # Public pages (home, tracking, blog, contact)
│   │   └── (root)           # Legacy + cross-cutting: Home, Dashbord, Webhook, Installer, Localization, etc.
│   ├── Helper/Helper.php    # Global helper functions (autoloaded via composer)
│   └── Middleware/          # CheckApiKey, PermissionCheck, CompanyActivation, IsInstalled, XSS, etc.
├── Imports/                 # Excel importers (parcels)
├── Library/SslCommerz/      # Custom SSLCommerz payment integration
├── Mail/                    # MerchantSignup, CompanySignup, InvoicePDFSend, ContactMail
├── Models/                  # Tenant, User, Permission, Config, etc.
│   └── Backend/             # Domain models (Parcel, Merchant, Hub, Account, Salary, etc.)
│       ├── FrontWeb/        # CMS models (Blog, Faq, Page, Section, ...)
│       ├── Merchantpanel/   # Merchant-scoped models (Invoice, PaymentAccount)
│       ├── Payroll/         # SalaryGenerate
│       └── Superadmin/      # Plan
├── Providers/               # App, Tenancy, Route, Auth, View, Event, Broadcast
├── Repositories/            # ~48 repos behind interfaces, bound in AppServiceProvider
├── Services/                # DeliveryPandaService (3PL integration)
├── Support/                 # ParcelStatusHelper (status state machine)
└── Traits/                  # ApiReturnFormatTrait, PaymentTrait (bKash), TrackingTrait

config/                      # Standard Laravel + tenancy.php
database/migrations/         # 96 migrations → ~116 tables
database/seeders/tours/      # System onboarding tour definitions (JSON, seeder-driven)
public/                      # Pre-compiled css/js + uploads + installer assets
resources/js/Tour/           # Onboarding tour engine (Provider, Overlay, Popover, Launcher)
resources/views/             # Blade (admin/, backend/, frontend/, auth/, installer/, errors/)
routes/                      # 7 route files (below)
```

---

## 5. Database (~112 tables, 90 migrations, 87 Eloquent models)

Grouped by domain. Each row is `Model → table`. Models in `app/Models/Backend/` unless noted.

### Tenancy & Auth

| Model | Table | Notes |
|---|---|---|
| `Models/Tenant` | `tenants` | Stancl HasDatabase + HasDomains |
| (Stancl-managed) | `domains` | host → tenant_id |
| `Models/User` | `users` | hub, department, designation, merchant, role, deliveryman, company; **LogsActivity** |
| `Models/Permission` | `permissions` | keywords array |
| `Models/SuperAdminPermission` | `super_admin_permissions` | |
| `Role` | `roles` | LogsActivity |
| `Models/CustomerDomain` | `customer_domains` | |
| (auto) | `personal_access_tokens` | Sanctum |
| (auto) | `password_reset_tokens`, `sessions`, `failed_jobs` | |

### Parcels & Logistics

| Model | Table | Notes |
|---|---|---|
| `Parcel` | `parcels` | Core entity. Relations: merchant, merchantShop, deliveryCategory, packaging, hub, transferhub, city, area, images, parcelEvent, deliverymanStatement |
| `ParcelEvent` | `parcel_events` | State transitions; ties parcel ↔ deliveryman/pickupman/hub/user |
| `ParcelLogs` | `parcel_logs` | Lower-level activity trail |
| `ParcelImage` | `parcel_images` | |
| `Hub` | `hubs` | Distribution warehouses |
| `HubInCharge` | `hub_incharges` | Hub-manager assignment |
| `HubPayment` | `hub_payments` | Hub-level payouts |
| `HubStatement` | `hub_statements` | |
| `DeliveryMan` | `delivery_man` | Driver profile; pivots into pickups + deliveries |
| `DeliveryCharge` | `delivery_charges` | Standard rate card |
| `Deliverycategory` | `deliverycategories` | Weight/dimension class |
| `DeliverymanStatement` | `deliveryman_statements` | Per-parcel earnings/dues |
| `Models/PickupRequest` | `pickup_requests` | |
| `Packaging` | `packagings` | |
| `Parcels_3pl` | `parcels_3pl` | Third-party fulfillment shadow |
| `RejectedParcel` | `rejected_parcels` | + `RejectionReason → rejection_reasons` |

### Merchants & Billing

| Model | Table | Notes |
|---|---|---|
| `Merchant` | `merchants` | Owns shops, parcels, payments; computed balance |
| `Models/MerchantShops` | `merchant_shops` | |
| `Models/MerchantPayment` | `merchant_payments` | |
| `MerchantDeliveryCharge` | `merchant_delivery_charges` | Per-merchant pricing overrides |
| `MerchantStatement` | `merchant_statements` | |
| `Payment` | `payments` | merchant ↔ account, with reference file |
| `Merchantpanel/Invoice` | `invoices` | Cast `parcels` array |
| `InvoiceParcel` | `invoice_parcels` | |
| `Merchantpanel/PaymentAccount` | `payment_accounts` | |
| `MerchantOnlinePayment` | `merchant_online_payments` | |
| `MerchantOnlinePaymentReceived` | `merchant_online_payment_receiveds` | |
| `MerchantSetting` | `merchant_settings` | |
| `Wallet` | `wallets` | merchant/user wallet balances |

### Accounting

| Model | Table | Notes |
|---|---|---|
| `Account` | `accounts` | Chart of accounts |
| `AccountHead` | `account_heads` | Categories (Income/Expense/Asset/...) |
| `Expense` | `expenses` | Linked to account, merchant, deliveryman, parcel |
| `Income` | `incomes` | Linked to account, merchant, deliveryman, hub, parcel, bank transaction |
| `FundTransfer` | `fund_transfers` | from/to Account |
| `BankTransaction` | `bank_transactions` | + cash-from-delivery, hub |
| `Salary` | `salaries` | + `Payroll/SalaryGenerate → salary_generates` |
| `Models/CashReceivedFromDeliveryman` | `cash_received_from_deliverymen` | |
| `VatStatement` | `vat_statements` | |
| `CourierStatement` | `courier_statements` | |

> Triple-ledger system: company-level + merchant-level + deliveryman-level statements, all hanging off `AccountHeads`.

### Settings / Platform

| Model | Table | Notes |
|---|---|---|
| `GeneralSettings` | `general_settings` | Per-company main settings (logo, currency, plan, subscription) |
| `Models/Config` | `configs` | Key-value, company-scoped |
| `Setting` | `settings` | Key-value, company-scoped |
| `Currency` | `currencies` | Multi-currency |
| `SmsSetting` | `sms_settings` | Provider creds (Twilio/Vonage/custom) |
| `SmsSendSetting` | `sms_send_settings` | Per-event SMS trigger rules |
| `NotificationSettings` | `notification_settings` | FCM config |
| `PushNotification` | `push_notifications` | Sent-campaign log |
| `Addon` | `addons` | Premium module flags |
| `Upload` | `uploads` | File metadata (original + 3 variants) |
| `GoogleMapSetting` | `google_map_settings` | |
| `IntegrationSetting` | `integration_settings` | Per-platform storefront-bridge config (Salla/Zid/Shopify/WooCommerce) |

### Storefront integrations (link tables)

| Model | Table | Notes |
|---|---|---|
| `SallaOrderLink` | `salla_orders` | parcel ↔ Salla `(salla_merchant_id, salla_order_id)` |
| `ZidOrderLink` | `zid_orders` | parcel ↔ Zid `(zid_store_id, zid_order_id)` (Zid IDs are strings) |
| `WooCommerceOrderLink` | `woocommerce_orders` | parcel ↔ WC `(site_url, wc_order_id)` — carries per-site writeback bearer |

> See `INTEGRATIONS.md` and the workspace-level `../INTEGRATIONS.md` for the full storefront-bridge contract.

### HR / Org

| Model | Table |
|---|---|
| `Department` | `departments` |
| `Designation` | `designations` |
| `Asset` | `assets` |
| `Assetcategory` | `assetcategories` |

### Support / Ops

| Model | Table | Notes |
|---|---|---|
| `Support` | `supports` | + `SupportChat → support_chats` |
| `To_do` | `to_dos` | |
| `Fraud` | `frauds` | |
| `NewsOffer` | `news_offers` | |
| (Spatie) | `activity_log` | All `LogsActivity`-tagged model changes |

### Onboarding tours

| Model | Table | Notes |
|---|---|---|
| `Backend/Tour` | `tours` | Tour definitions (system + per-tenant). `company_id` nullable — system template if null. `LogsActivity` + `scopeCompanywise()` merges tenant overrides on top of system rows |
| `Backend/TourStep` | `tour_steps` | Ordered steps per tour. `target` JSON descriptor, `translations` JSON keyed by locale |
| `Backend/UserTourProgress` | `user_tour_progress` | Per-user state, keyed by `(user_id, tour_key, tour_version)` — bumping version re-shows the tour |
| `Backend/TourEvent` | `tour_events` | Append-only analytics stream (`started`, `step_forward/back`, `skipped`, `completed`, `element_missing`) |
| — | `users.first_login_at` | Added column; set by `LoginController::authenticated()` on the very first sign-in. Drives the welcome-modal auto-start |

Authored via `database/seeders/tours/*.json` (see the co-located `README.md` for the schema + anchor catalog) and edited per-tenant via `/admin/tours` (Inertia CRUD, gated by `tour_manage` permission). See `TOURS.md` at the repo root for the full architecture.

### Geography

| Model | Table |
|---|---|
| `City` | `cities` |
| `Area` | `areas` |
| `Emirate` | `emirates` |

### Frontend CMS (`Backend/FrontWeb/`)

`Blog`, `Faq`, `Page`, `Partner`, `Section`, `Service`, `SocialLink`, `WhyCourier` → tables of the same name.

### Superadmin / Plans

| Model | Table |
|---|---|
| `Superadmin/Plan` | `plans` |
| `Subscription` | `subscriptions` |

---

## 6. Routes (7 files, ~1,664 lines)

| File | Purpose | Domain | Lines |
|---|---|---|---|
| **web.php** | Main app: installer, frontend, auth, admin panel, merchant/hub panels, backend management, reports, payments. | Tenant + central | 1082 |
| **superadmin.php** | Central-only: company management, plans, frontend CMS, super-admin dashboard, roles, users, settings. | Central | 293 |
| **api.php** | REST API v10: merchant/delivery auth, parcel ops, dashboards, invoices, payments. Sanctum + `CheckApiKey`. | Both | 214 |
| **admin.php** | Minimal: parcel detail view for admin. | Both | 10 |
| **tenant.php** | Empty / commented placeholder. | Tenant | – |
| **console.php** | Default. | CLI | – |
| **channels.php** | Broadcast channel auth. | Both | – |

**Middleware stacks commonly seen:**
- `PreventAccessFromCentralDomains` + `InitializeTenancyByDomain` + `CompanyActivationMiddleware` → tenant subdomain init
- `auth` + `subscriptionCheck` + `hasPermission:{key}` → gated backend routes
- `auth:sanctum` + `CheckApiKey` → API endpoints
- `XSS`, `LanguageManager`, `Cors`, `IsInstalled` / `IsNotInstalled` → cross-cutting

---

## 7. Controllers (~125 total)

### Auth (`app/Http/Controllers/Auth/`)
`LoginController`, `RegisterController`, `ForgotPasswordController`, `ResetPasswordController`, `VerificationController`, `ConfirmPasswordController` — standard Laravel UI stack + Socialite hook.

### Root (`app/Http/Controllers/`)
| Controller | Purpose |
|---|---|
| `HomeController` | Legacy landing |
| `DashbordController` | Admin + merchant dashboard (note: typo retained) |
| `CategoryController` | Parcel category CRUD |
| `MapParcelController` | Parcel-on-map tracking view |
| `DeliveryPandaController` | 3PL booking/tracking |
| `MerchantPaymentAccountController` | Store merchant payment methods |
| `MerchantmanagePaymentController` | Admin processing of merchant payments |
| `AamarpayController` | AamarPay gateway callback |
| `WebhookController` | Inbound webhook receiver |
| `InstallerController` | First-time install wizard |
| `LocalizationController` | Locale switch |

### Backend — Parcels & Shipments
`ParcelController` (full lifecycle), `ParcelBulkActionController`, `DeliveryManController`, `PickupRequestController`, `MapParcelController`, `ShipmentExportController`.

### Backend — Hubs
`HubController`, `HubInChargeController`, `HubPaymentController`, `HubPanel/HubPaymentRequestController`, `HubPanel/ReceivedFromDeliverymanController`.

### Backend — Merchants
`MerchantController`, `MerchantProfileController`, `MerchantShopsController`, `MerchantDeliveryChargeController`, `MerchantPaymentAccountController`, `MerchantInvoiceController`.

### Backend — Delivery & Logistics
`DeliveryTypeController`, `DeliverycategoryController`, `DeliveryChargeController`, `TMSController` (transport mgmt — runsheets), `WMSController` (warehouse mgmt — products/inventory/receiving), `LiquidFragileController`.

### Backend — Finance & Accounting
`IncomeController`, `ExpenseController`, `BankTransactionController`, `AccountController`, `AccountHeadsController`, `PayoutController` (Stripe/PayPal/Razorpay/SSL), `PayoutSetupController`, `FundTransferController`, `SalaryController`, `SalaryGenerateController`.

### Backend — Users & Permissions
`UserController`, `RoleController`, `ProfileController`, `DesignationController`, `DepartmentController`.

### Backend — Payment Gateway Adapters
Admin-side: `AdminSslCommerzController`, `AdminBkashController`, `AdminSkrillController`, `AdminAamarpayController`.
Merchant-side: `SslCommerzPaymentController`, `BkashController`, `SkrillController`, `AamarpayController`.

### Backend — Operations & Settings
`ReportsController`, `TotalSummeryReportController`, `ActiveLogController`, `TodoController`, `SupportController`, `FraudController`, `NewsOfferController`, `AssetController`, `AssetcategoryController`, `PackagingController`, `CurrencyController`, `GeneralSettingsController`, `SmsSettingsController`, `SmsSendSettingsController`, `NotificationSettingsController`, `GoogleMapSettingsController`, `PushNotificationController`, `SocialLoginController`, `WebNotificationController`, `DatabaseBackupController`, `AddonController`, `IntegrationsController` (super-admin Integrations page at `/admin/integrations`), `TourManagerController` (onboarding tour CRUD + analytics at `/admin/tours`).

### Backend — Onboarding
`Api/V10/TourController` — JSON endpoints consumed by the frontend tour engine (`GET /tours/for-me`, `POST /tours/{key}/progress`, `POST /tours/{key}/event`). Session-auth'd, mounted inside the tenant middleware group in `web.php` (not under sanctum). Companion admin CRUD lives at `Backend/TourManagerController` (above).

### Api / V10 / External
`SallaParcelController`, `ZidParcelController`, `WooCommerceParcelController` — one per storefront bridge, all mounted at `/api/v10/external/<platform>/parcel`. Each accepts the bridge's normalised order payload, idempotently creates a `Parcel`, and writes a row in the matching link table.

### Backend / MerchantPanel/ (`Backend/MerchantPanel/`)
Self-service surface for merchants on the tenant app:
`MerchantParcelController`, `PaymentAccountController`, `AccountTransactionController`, `StatementsController`, `SettingsController`, `ShopsController`, `PaymentRequestController`, `InvoiceController`, `MerchantReportsController`, `ReportsController`, `NewsOfferController`, `SupportController`, `FraudController`, `WalletController`, `MerchantOnlinePaymentSetupController`, `OnlinePaymentController`, `PickupRequestController`.

### Backend / HubPanel/
`HubPaymentRequestController`, `ReceivedFromDeliverymanController`.

### Backend / Superadmin/
`CompanyController` (tenant company CRUD, subscription switching), `PlanController` (plans, modules, pricing).

### Backend / FrontWeb/ (Public marketing-site CMS)
`BlogController`, `FaqController`, `PageController`, `SectionController`, `ServiceController`, `SocialLinkController`, `PartnerController`, `WhyCourierController`.

### Frontend (`app/Http/Controllers/Frontend/`)
`FrontendController` — public pages: home, tracking, blog, FAQs, contact, about, privacy, terms.

### Admin (`app/Http/Controllers/Admin/`)
`ParcelController` — read-only parcel detail.

---

## 8. API (v10) Surface

All under `/api/v10/*`, gated by `CheckApiKey`. Auth flow uses Sanctum tokens.

### Public (no token required)
| Endpoint | |
|---|---|
| `POST /register` | Company/merchant signup |
| `POST /signin` | User login (issues sanctum token) |
| `POST /deliveryman/login` | Driver login |
| `POST /otp-verification` | OTP verify |
| `POST /password/reset` | Password reset |
| `GET  /hub` | Public hub list |
| `GET  /general-settings` | Public system settings |
| `GET  /parcel/tracking/{id}` | Public parcel tracking |
| `POST /contact-us` | Contact form |
| `POST /subscribe` | Newsletter |
| `GET  /delivery-charges` | Public rate card |
| `GET  /rejection_reasons` | List |

### Authenticated (`auth:sanctum`)
**Merchant parcel ops** — index, store, update, details, delete, filter, status change.
**Driver parcel ops** — index, mark delivered, mark partial-delivered, GPS update.
**Merchant finance** — payment accounts CRUD, transactions, statements, dashboard balance, payment requests.
**Dashboards & reports** — main dashboard, filtered dashboard, analytics, invoice list, statement reports.
**Merchant infra** — shops CRUD.
**Support & ops** — support tickets + replies, fraud reporting, news offers, push notification subscription, user settings.

### Onboarding tours (session-auth'd, tenant-scoped, mounted in `web.php`)
| Endpoint | |
|---|---|
| `GET /tours/for-me` | Tours applicable to the current user (role + tenant filter) + `first_login` flag |
| `POST /tours/{key}/progress` | `{status, current_step, version}` — persists per-user state |
| `POST /tours/{key}/event` | `{event, step_index?, duration_ms?, meta?}` — analytics stream into `tour_events` |

### Webhooks
| Endpoint | |
|---|---|
| `GET/POST /api/olivery/webhook` | Olivery delivery-partner callback |
| `GET /api/v10/panda/schudule_tracking` | DeliveryPanda tracking |
| `POST /api/delivery/create` | External shipment create |

---

## 9. Middleware (custom)

| Class | Job |
|---|---|
| `CheckApiKeyMiddleware` | Validates `apiKey` header (gate for API v10) |
| `PermissionCheckMiddleware` | `hasPermission:{key}` — checks user permission array |
| `CompanyActivationMiddleware` | Blocks request if tenant domain not activated |
| `IsInstalledMiddleware` / `IsNotInstalledMiddleware` | Gate the installer wizard |
| `subscriptionCheckMiddleware` | Blocks if tenant subscription inactive |
| `XSS` | Sanitize input |
| `LanguageManager` | Set locale |
| `APIlog` | Log API requests |
| `Cors` | CORS headers |
| `ModifyHeaderMiddleware` | Custom header handling |

Plus stancl's `InitializeTenancyByDomain` and `PreventAccessFromCentralDomains`.

---

## 10. Repositories (~48, in `app/Repositories/`)

Repositories are bound interface → implementation in `AppServiceProvider` (100+ bindings) and constructor-injected throughout controllers. Grouped by domain:

| Domain | Repositories |
|---|---|
| Parcel | `ParcelRepository`, `MerchantParcelRepository` |
| Merchant | `MerchantRepository`, `MerchantShopsRepository`, `MerchantProfileRepository`, `MerchantDeliveryChargeRepository`, `MerchantPaymentRepository`, `MerchantOnlinePaymentSetupRepository` |
| Delivery | `DeliveryManRepository`, `DeliveryTypeRepository`, `DeliveryChargeRepository`, `DeliveryCategoryRepository` |
| Hub | `HubRepository`, `HubInChargeRepository`, `HubPaymentRequestRepository` |
| Finance | `AccountRepository`, `AccountHeadsRepository`, `BankTransactionRepository`, `PayoutSetupRepository`, `WalletRepository`, `FundTransferRepository`, `ExpenseRepository`, `IncomeRepository`, `InvoiceRepository`, `CashReceivedFromDeliverymanRepository` |
| HR/Payroll | `SalaryRepository`, `DepartmentRepository`, `DesignationRepository` |
| Assets | `AssetRepository`, `AssetCategoryRepository` |
| Settings | `GeneralSettingsRepository`, `NotificationSettingsRepository`, `GoogleMapSettingsRepository`, `SmsSettingRepository`, `SmsSendSettingRepository`, `SocialLoginSettingsRepository`, `CurrencyRepository` |
| Reports / Dashboard | `DashboardRepository`, `ReportsRepository` |
| Support / Ops | `SupportRepository`, `FraudRepository`, `TodoRepository`, `NewsOfferRepository`, `PushNotificationRepository` |
| Users / RBAC | `UserRepository`, `ProfileRepository`, `RoleRepository` |
| Merchant Panel | `PaymentAccountRepository`, `PaymentRequestRepository`, `PickupRequestRepository` |
| Frontend CMS | `BlogsRepository`, `PagesRepository`, `FaqRepository`, `SectionRepository`, `ServiceRepository`, `PartnerRepository`, `WhyCourierRepository`, `SocialLinkRepository` |
| Onboarding | `Tour/TourRepository` — resolves tours applicable to `(user, role, tenant)` with per-request memoization; records `UserTourProgress` + `TourEvent` |

---

## 11. Services, Library, Traits, Support

| | |
|---|---|
| **Services** (`app/Services/`) | `DeliveryPandaService` — 3PL booking/tracking · `SallaService` / `ZidService` / `WooCommerceService` — push parcel status writeback to the matching bridge or WP plugin · `FollowupNotificationDispatcher` — outbound FCM dispatcher |
| **Observers** (`app/Observers/`) | `ParcelSallaObserver` / `ParcelZidObserver` / `ParcelWooCommerceObserver` — fire on `Parcel.status` changes and delegate to their service |
| **Library** (`app/Library/`) | `SslCommerz/` — Bangladesh payment-gateway adapter (`AbstractSslCommerz`, `SslCommerzNotification`, `SslCommerzInterface`) |
| **Traits** (`app/Traits/`) | `ApiReturnFormatTrait` (standard JSON envelope); `PaymentTrait` (bKash token gen); `TrackingTrait` (parcel tracking-ID generator) |
| **Support** (`app/Support/`) | `ParcelStatusHelper` — central state-machine helper for the 34-state parcel lifecycle (i18n keys, badge classes, cancel/return detection) |

---

## 12. Global Helpers (`app/Http/Helper/Helper.php`)

Autoloaded via `composer.json` `files`. ~60 helper functions grouped:

| Group | Highlights |
|---|---|
| Settings | `settings()`, `settingHelper($key)`, `globalSettings($key)`, `smsSettings($key)`, `MerchantSettings($key)`, `googleMapSettingKey()` |
| Auth / Perms | `isSuperadmin()`, `hasPermission($p)`, `user($id)`, `singleUser($id)` |
| Parcels | `parcelStatus($p)`, `StatusParcel($id)`, `getParcelStatusLabel($s)`, `parcelExpense($id)`, `parcelExpenseTotal($ids)`, `parcelsStatus($ps)`, `idWiseParcels($ps)` |
| Finance | `merchantPayments($id)`, `totalParcelsCashcollection($ps)`, `salaryPayments($uid)`, `MerchantParcels($id)` |
| Dashboard rollups | `dayIncomeCount`, `dayExpenseCount`, `dayMerchantRevIncomeCount`, `dayMerchantRevExpenseCount`, `dayDeliverymanRev*` |
| Hubs | `hubs()`, `hubIncharge()` |
| Notifications | `notifications()`, `notificationSettings()`, `calendarnewsoffer($date)` |
| SMS / FCM | `SmsSendSettingHelper($status)` |
| URLs / assets | `static_asset($path)`, `scheme_name($domain)`, `get_host()` |
| Subscriptions | `subscriptionCheck($user)` |
| Misc | `pluck($arr, $v, $k)`, `setEnv($k, $v)`, `specialCharactersReplace($s)`, `paginate_redirect($req)`, `oldLogDetails($old, $new)`, `dateFormat($d)`, `withoutUser($ids)`, `unpaidUser($ids)`, `section($type, $key)` |

---

## 13. Service Providers

| Provider | Job |
|---|---|
| `AppServiceProvider` | Binds **~100+** repository interfaces → implementations. Configures Bootstrap-5 pagination + schema default string length |
| `TenancyServiceProvider` | Stancl events (TenantCreated/Deleted), middleware priority, tenancy bootstrappers, tenant-lifecycle jobs |
| `RouteServiceProvider` | Route namespacing + model binding |
| `AuthServiceProvider` | Gates/policies |
| `ViewServiceProvider` | View composers / global view data |
| `EventServiceProvider` | Event-listener wiring; registers parcel observers for Salla / Zid / WooCommerce |
| `BroadcastServiceProvider` | Channel auth |
| `IntegrationConfigServiceProvider` | Overlays `integration_settings` rows onto `config('services.<platform>.*')` at boot so existing code reads DB-managed values transparently |

---

## 14. Mail, Exports, Imports, Console

### Mail (`app/Mail/`)
| Class | When |
|---|---|
| `MerchantSignup` | Welcome merchant on registration |
| `CompanySignup` | Hub/company-owner confirmation |
| `InvoicePDFSend` | Email merchant invoice |
| `ContactMail` | Contact-form ack |

### Exports (`app/Exports/`)
`MerchantParcelExport`, `ParcelSampleExport`, `ShipmentTemplateExport`, `ShipmentExport`, `ShipmentsWorkbookExport`, `DeliverymanReports`, `HubReports`, `MerchantReports`, `InvoiceExport`, `ReportExports`, `CitiesSheetExport`, `AreasSheetExport`.

### Imports (`app/Imports/`)
`ParcelImport`, `ParcelImport2`, `MParcelImport`.

### Console Commands
| Command | Purpose |
|---|---|
| `invoice:generate` | Scheduled merchant-invoice generation |
| `DatabaseAutoBackup` | Scheduled DB backup |

---

## 15. Enums (`app/Enums/`)

24 enums. The important ones:

- **`ParcelStatus`** — 34 states from `PENDING` through `PICKUP_ASSIGN`, `RECEIVED_WAREHOUSE`, `DELIVERY_MAN_ASSIGN`, `DELIVERED`, `PARTIAL_DELIVERED`, `RETURN_TO_COURIER`, `RETURN_ASSIGN_TO_MERCHANT`. Used together with `ParcelStatusHelper` for the state machine.
- **`UserType`** — `SUPER_ADMIN`, `ADMIN`, `HUB_MANAGER`, `DELIVERYMAN`, `MERCHANT`, `CUSTOMER`.
- **`PaymentType`** — `CASH`, `ONLINE`, `WALLET`, `BANK_TRANSFER`.
- **`AccountHeads` / `AccountType`** — accrual-accounting categories.
- **`Status`**, `ApprovalStatus`, `SalaryStatus`, `InvoiceStatus`, `TodoStatus`, `SupportStatus`, `BooleanStatus`, `DeliveryType`, `DeliveryTime`, `StatementType`, `SmsSendStatus`, `SmsSetup`, `PayoutSetup`, `PickupRequestType`, `SectionType`.
- **`Wallet/WalletType`**, `Wallet/WalletStatus`, `Wallet/WalletPaymentMethod`.
- **`Merchant_panel/PaymentMethod`** — `STRIPE`, `PAYPAL`, `RAZORPAY`, `BKASH`, `SSLCOMMERZ`.

---

## 16. Third-Party Integrations

| Service | Use case | Package / Location |
|---|---|---|
| Stripe | Online cards (global) | `cartalyst/stripe-laravel`, `stripe/stripe-php` |
| PayPal | Alt online payment | `srmklive/paypal` |
| Razorpay | India payments | `razorpay/razorpay` |
| Skrill | Wallet | `obydul/laraskrill` |
| Paytm | India wallet | `anandsiddharth/laravel-paytm-wallet` |
| bKash | Bangladesh MM | Custom `PaymentTrait` |
| SSLCommerz | Bangladesh gateway | `app/Library/SslCommerz/` |
| AamarPay | Bangladesh gateway | `AamarpayController` |
| Twilio | SMS / voice | `twilio/sdk` |
| Vonage / Nexmo | SMS | `vonage/client` |
| Socialite | Google / Facebook OAuth | `laravel/socialite` |
| FCM | Push notifications | Direct via `FCM_SECRET_KEY` env |
| Olivery / DeliveryPanda | 3PL | `DeliveryPandaService` + webhook routes |
| Excel | Import/export | `maatwebsite/excel` |
| PDF | Invoice PDFs | `carlos-meneses/laravel-mpdf` |
| Barcode | Labels | `milon/barcode` |
| Activity log | Audit | `spatie/laravel-activitylog` |
| Tenancy | Multi-tenancy | `stancl/tenancy` |

---

## 17. Cross-Cutting Patterns

**Repository pattern.** All non-trivial data access goes through an interface bound in `AppServiceProvider`. Controllers receive them via constructor injection. Makes substitution / testing feasible.

**Tenant isolation by `company_id` + scope.** Since the DB is shared, every domain table carries `company_id` and uses `scopeCompanywise()`. **When adding new models, follow this convention** — forgetting will leak data across tenants.

**Parcel state machine.** 34 states in `ParcelStatus` enum; transitions, badge classes, and i18n keys centralized in `ParcelStatusHelper`. Never set `parcel.status` by raw value — go through the helper.

**Triple-ledger accounting.** Company-level (`incomes` / `expenses` / `accounts`), merchant-level (`merchant_statements`), and deliveryman-level (`deliveryman_statements`) ledgers — all anchored to `AccountHeads`. Reports join across all three.

**Permission-gated routes.** Most backend routes carry `hasPermission:{key}` middleware checking against the user's `permissions` array (JSON column on `users`). Permissions seeded by `RoleController`/`UserController`.

**Event-driven tenancy lifecycle.** `TenancyServiceProvider` wires `TenantCreated` and `TenantDeleted` events to provisioning / cleanup jobs.

**Domain-named subfolders for panels.** Merchant self-service lives under `Backend/MerchantPanel/`, hub-manager self-service under `Backend/HubPanel/`, super-admin under `Backend/Superadmin/`, public CMS under `Backend/FrontWeb/`. Match this when adding new role-specific surface.

---

## 18. Local Development Setup (current dev env)

| | |
|---|---|
| Working dir | `/Users/haniyousif/dev/rushly-space/rushly-saas` |
| PHP | 8.3 (`/opt/homebrew/opt/php@8.3/bin`) — **not 8.4**, fatal deprecations from old vendor code |
| DB | MySQL local, database `rushly`, user `root`, no password |
| Schema source | `/Users/haniyousif/dev/rushly-space/rushlylogistic_main_app.sql` (already patched so `domains` rows point at `.test`) |
| Web server | Laravel Valet, nginx, dnsmasq |
| Vite | Not used at runtime — pre-compiled assets in `public/css` + `public/js` |

### Live URLs

| URL | What it serves |
|---|---|
| `https://rushly.test/` | Central — SaaS marketing site + super-admin |
| `https://admin.rushly.test/` | Tenant `rushly-logistic` (operations console) |
| `https://tolgaplusa.rushly.test/` | Tenant `TolgaPlusa` |

### Reset workflow (when you want a clean DB)

```sh
mysql -uroot -e "DROP DATABASE rushly; CREATE DATABASE rushly CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot rushly < /Users/haniyousif/dev/rushly-space/rushlylogistic_main_app.sql
PATH="/opt/homebrew/opt/php@8.3/bin:$PATH" php artisan cache:clear
```

(The `.sql` has already been edited to insert `admin.rushly.test` and `tolgaplusa.rushly.test` into the `domains` table — no post-import SQL needed.)

### Pitfalls

- **PHP 8.4 is fatal.** Old vendor packages emit deprecations that Laravel's `HandleExceptions` upgrades to ErrorException. Use 8.3.
- **`imagick` warning** is printed to stdout by the global Homebrew PHP config — strip it (e.g. `php -d extension= ...` or filter output) when capturing PHP output into shell variables.
- **HTTP on `*.test` returns 403** for `valet secure`'d sites — use `https://`.
- **Login redirects to `admin.rushly-logistic.com`** in code where the host is hardcoded — re-targeting to `admin.rushly.test` (or any local equivalent) requires patching the redirect site (look in `LoginController` / `Backend/Superadmin/CompanyController`).
- **`config/tenancy.php` central_domains** currently lists `127.0.0.1, localhost`. If you change central to `rushly.test`, add it there too.

---

## 19. Where to Start

| If you need to… | Start here |
|---|---|
| Add a new parcel status | `app/Enums/ParcelStatus.php` + `app/Support/ParcelStatusHelper.php` + migrations if needed |
| Add a new tenant | INSERT into `tenants` + `domains` tables (or via super-admin Company controller) |
| Add a new payment gateway | Mirror SSLCommerz: controller pair (Admin + Merchant) + library or trait + add to `Merchant_panel/PaymentMethod` enum + payout setup |
| Add a new API endpoint | `routes/api.php` + new method in relevant `Api/V10/*Controller` + use `ApiReturnFormatTrait` for the response |
| Wire a new background job on tenant create/delete | `TenancyServiceProvider` event map |
| Add a permission-gated screen | Add permission key to seeder, attach `->middleware('hasPermission:my_key')` on the route, expose UI in `RoleController` edit screen |
| Customize the marketing site | `app/Http/Controllers/Backend/FrontWeb/` (admin CRUD) + `Frontend/FrontendController` (rendering) + Blade views in `resources/views/frontend/` |
| Add a new storefront integration | Mirror Salla/Zid/WooCommerce: migration for `<platform>_orders` + `<Platform>OrderLink` model + `<Platform>Service` (writeback) + `Parcel<Platform>Observer` (registered in `EventServiceProvider`) + `External/<Platform>ParcelController` + route under `/api/v10/external/<platform>/` + entry in `IntegrationsController::PLATFORMS`. See `../INTEGRATIONS.md` §8 for the full checklist. |
| Add an onboarding tour | Drop a JSON file in `database/seeders/tours/*.json` (schema + anchor catalog in `database/seeders/tours/README.md`) → `php artisan db:seed --class="Database\\Seeders\\TourSeeder"`. For per-tenant tours, use `/admin/tours` (gated by `tour_manage`). Full arch in `TOURS.md`. |

---

*Doc generated from a full read of routes (1,664 lines), 83 model classes, ~125 controllers, 47 repositories, 24 enums, 7 providers, and the tenancy config.*
