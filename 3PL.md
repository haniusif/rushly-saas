# 3PL Integrations

Current state of the third-party logistics integrations. As of 2026-05-29:

- **Live** (production-ready code): **DeliveryPanda**, **Zajel**, **Aramex**, **J&T (Jet)**, **Logestechs**.
- **Logestechs** is now also live end-to-end (createShipment verified against company 496 — returned a real AWB + label URL on 2026-05-29).
- **Stub only** (config + integrations card, no code): **iMile**.

> ⚠️ The Panda flow has several known security and multi-tenant issues (see [Known issues](#known-issues)). Zajel, Aramex, and Jet were built fresh and avoid most of them, but inherit the shared `Parcels_3pl` data-model problem (no `company_id`).

---

## Surface

| Layer | Panda | Zajel | Aramex | Jet |
|------|------|------|------|------|
| Transport | REST (HTTP JSON) | REST (HTTP JSON) | SOAP (SoapClient) | REST (form-urlencoded + JSON for tracking) |
| Service (HTTP client) | `app/Services/DeliveryPandaService.php` | `app/Services/ZajelService.php` | `app/Services/AramexService.php` | `app/Services/JetService.php` |
| Single-parcel assign | `ThirdPartyLogistics` `panda` branch | `ThirdPartyLogistics` `zajel` branch | `ThirdPartyLogistics` `aramex` branch | `ThirdPartyLogistics` `jet` branch |
| Bulk assign | `apply()` (inline) | `assignZajelBulk()` | `assignAramexBulk()` | `assignJetBulk()` |
| Webhook receiver | — | `Webhooks\ZajelWebhookController` | — | — |
| Status sync | `DeliveryPandaController::schudule_tracking` (cron-pull) | webhook (push) | `aramex:sync-tracking` (cron-pull, every 15 min) | `jet:sync-tracking` (cron-pull, every 15 min) |
| Public API controller | `DeliveryPandaController` | — | — | — |

Shared model: `app/Models/Backend/Parcels_3pl.php` (stores assignments for all providers, distinguished by `parcel_3pl_name`).

### Routes

**Admin (`routes/web.php`):**
- `POST /admin/parcel/details/{id}/3pl` → `ParcelController@ThirdPartyLogistics` (`parcel.3pl_details`)
  - Permission: `parcel_read` ❌ (should be `parcel_status_update` or a dedicated assign permission)
- `/admin/bulk_action` → `assign_3pl` action: `company` ∈ `{panda, zajel, aramex, jet, logestechs}`. Logestechs additionally requires a `logestechs_company_id` field per submission.

**API (`routes/api.php`):**

| Method | Path | Auth | Status |
|---|---|---|---|
| GET | `/api/panda/schudule_tracking` | ❌ none | Live (typo: should be `schedule`) |
| GET | `/api/panda/schudule_tracking_temp` | ❌ none | Route exists, controller method missing |
| POST | `/api/delivery/create` | ❌ none | Live |
| POST | `/api/delivery/agent-create` | ❌ none | Live |
| POST | `/api/delivery/customer-to-customer` | ❌ none | Live |
| POST | `/api/delivery/track` | ❌ none | Live |
| POST | `/api/zajel/webhook` | ✅ shared secret | Live |

**Console (`app/Console/Kernel.php`):**
- `aramex:sync-tracking` — every 15 minutes, with `withoutOverlapping()`. Pulls TrackShipments for all non-terminal Aramex AWBs and syncs status into the parcel timeline.
- `jet:sync-tracking` — every 15 minutes, with `withoutOverlapping()`. Iterates J&T's per-AWB tracking endpoint for non-terminal Jet AWBs.

### Config

```php
// config/services.php
'deliverypanda' => [
    'key'      => env('DELIVERY_PANDA_API_KEY'),
    'base_url' => 'https://app.deliverypanda.me/webservice/',
    'timeout'  => 30,  // ⚠️ not actually used by the service
],

'zajel' => [
    'key'             => env('ZAJEL_API_KEY'),
    'customer_code'   => env('ZAJEL_CUSTOMER_CODE'),
    'base_url'        => env('ZAJEL_BASE_URL', 'https://api-stg.zajel.com/services/integration'),
    'service_type_id' => env('ZAJEL_SERVICE_TYPE_ID', 'DDN'),
    'webhook_secret'  => env('ZAJEL_WEBHOOK_SECRET'),
    'timeout'         => 30,
],

'aramex' => [
    'username'             => env('ARAMEX_USERNAME'),
    'password'             => env('ARAMEX_PASSWORD'),
    'version'              => env('ARAMEX_VERSION', 'v1.0'),
    'account_number'       => env('ARAMEX_ACCOUNT_NUMBER'),
    'account_pin'          => env('ARAMEX_ACCOUNT_PIN'),
    'account_entity'       => env('ARAMEX_ACCOUNT_ENTITY', 'DXB'),
    'account_country_code' => env('ARAMEX_ACCOUNT_COUNTRY_CODE', 'AE'),
    'wsdl'                 => env('ARAMEX_WSDL', 'https://ws.dev.aramex.net/...?wsdl'),
    'product_group'        => env('ARAMEX_PRODUCT_GROUP', 'DOM'),    // DOM / EXP
    'product_type'         => env('ARAMEX_PRODUCT_TYPE', 'OND'),     // OND, ONP, PDX, …
    'payment_type'         => env('ARAMEX_PAYMENT_TYPE', 'P'),       // P=Prepaid, C=Collect, 3=Third Party
    'timeout'              => 60,
],

'jet' => [
    // URLs come from the customer dashboard after the agreement is signed.
    'username'            => env('JET_USERNAME'),
    'api_key'             => env('JET_API_KEY'),
    'secret_key'          => env('JET_SECRET_KEY'),                  // signature secret
    'eccompanyid'         => env('JET_ECCOMPANYID'),                 // tracking endpoint
    'track_password'      => env('JET_TRACK_PASSWORD'),              // Basic auth for tracking
    'cus_name'            => env('JET_CUS_NAME'),                    // tariff endpoint
    'order_url'           => env('JET_ORDER_URL'),
    'track_url'           => env('JET_TRACK_URL'),
    'tariff_url'          => env('JET_TARIFF_URL'),
    'cancel_url'          => env('JET_CANCEL_URL'),
    'default_origin_code' => env('JET_DEFAULT_ORIGIN_CODE', 'JKT'),
    'service_type'        => (int) env('JET_SERVICE_TYPE', 1),       // 1=Pickup, 6=Drop Off
    'express_type'        => env('JET_EXPRESS_TYPE', '1'),           // "1" = EZ (Regular)
    'timeout'             => 30,
],
```

### Env reference

```
# Panda
DELIVERY_PANDA_API_KEY=

# Zajel
ZAJEL_API_KEY=
ZAJEL_CUSTOMER_CODE=ZACS-CUS-000xxx
ZAJEL_BASE_URL=https://api-stg.zajel.com/services/integration   # or prod: https://api.zajel.com:8443/services/integration
ZAJEL_SERVICE_TYPE_ID=DDN
ZAJEL_WEBHOOK_SECRET=<random secret you generate; share with Zajel>

# Aramex (SOAP)
ARAMEX_USERNAME=
ARAMEX_PASSWORD=
ARAMEX_VERSION=v1.0
ARAMEX_ACCOUNT_NUMBER=
ARAMEX_ACCOUNT_PIN=
ARAMEX_ACCOUNT_ENTITY=DXB
ARAMEX_ACCOUNT_COUNTRY_CODE=AE
ARAMEX_WSDL=https://ws.dev.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl    # test
# prod: https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl
ARAMEX_PRODUCT_GROUP=DOM
ARAMEX_PRODUCT_TYPE=OND
ARAMEX_PAYMENT_TYPE=P

# J&T (Jet) — URLs come from your dashboard after signing the agreement
JET_USERNAME=
JET_API_KEY=
JET_SECRET_KEY=
JET_ECCOMPANYID=
JET_TRACK_PASSWORD=
JET_CUS_NAME=
JET_ORDER_URL=
JET_TRACK_URL=
JET_TARIFF_URL=
JET_CANCEL_URL=
JET_DEFAULT_ORIGIN_CODE=JKT
JET_SERVICE_TYPE=1          # 1=Pickup, 6=Drop Off
JET_EXPRESS_TYPE=1          # 1=EZ Regular

# Logestechs — outbound handoff (Rushly -> Logestechs). STUB pending Postman docs.
LOGESTECHS_BASE_URL=
LOGESTECHS_API_KEY=

# iMile — STUB. Reserved env keys; service class not yet built.
IMILE_API_KEY=
IMILE_CUSTOMER_CODE=
IMILE_BASE_URL=
IMILE_COUNTRY=AE
```

The `/admin/integrations` page shows a card per 3PL with status badge, masked credential, parcels assigned, and provider-specific extras (Zajel: customer code + webhook URL; Aramex: account number, entity, product group/type).

---

## Data model

`parcels_3pl` table (MyISAM, per `DBs are MyISAM` note in the bulk action controller):

| Column | Purpose |
|---|---|
| `id` | PK |
| `parcel_id` | FK → `parcels.id` (no formal FK constraint) |
| `parcel_3pl_name` | `panda` / `zajel` / `aramex` / `jet` / `logestechs` |
| `target_company_id` | Caller-picked routing id on the receiving platform (used by Logestechs). nullable, indexed. Added by migration `2026_05_29_000001_add_target_company_id_to_parcels_3pl.php`. |
| `awb_number` | Provider reference (Panda AWB / Zajel `referenceNumber` / Aramex `ID` / Jet `awb_no`); **may be null** on failed assigns |
| `awb_pdf` | URL to AWB PDF / Aramex `ShipmentLabel.LabelURL` (Jet doesn't return one) |
| `response` | JSON of the provider API response (cast to array) |
| `created_at` / `updated_at` | |
| `current_status` | written by Panda cron / Zajel webhook / Aramex sync job — column not in `$fillable` |
| `status_datetime` | same |

Indexes: only `PRIMARY` on `id`. **No** `company_id`, no unique constraint.

Relations:
- `Parcels_3pl::parcel()` → `belongsTo(Parcel)`
- `Parcel::lastParcel3pl()` → `hasOne(Parcels_3pl)->latest()` (returns the **most recent row**, including failure rows — see issue #7)

---

## Flows

### Panda

#### 1. Assign a parcel to Panda (single)

Trigger: `POST /admin/parcel/details/{id}/3pl` with `company=panda`.

`ParcelController::ThirdPartyLogistics($id, $request)` builds a hardcoded payload (UAE / Dubai / AED) from the parcel and calls `DeliveryPandaService::createCustomerToCustomer`. Returns the raw Panda response. **Does not** persist a `Parcels_3pl` row.

#### 2. Assign in bulk

Trigger: `/admin/bulk_action` → "Assign to 3PL" → company = panda.

`ParcelBulkActionController::apply()` enters the `assign_3pl` branch:

1. Pre-flight: every selected parcel must be in `RECEIVED_WAREHOUSE`, else bail.
2. For each parcel, call `PandaThirdParty($p->id)` which builds the same UAE/Dubai/AED payload and calls `createCustomerToCustomer`.
3. Branch on response (success/failure → `Parcels_3pl::create` either way).
4. Always merges `delivery_man_id = 12` into the request and calls `deliverymanAssign($p->id, ...)`. ❌ Hardcoded driver — see issue #4.

#### 3. Tracking sync

Trigger: `GET /api/panda/schudule_tracking` (cron-pull, **unauth, untenanted**).

Pulls AWBs from `Parcels_3pl` where `parcel_3pl_name='panda'`, calls Panda's `GetTracking`, updates rows. If `current_status == 'DELIVERED'` AND parcel is in `DELIVERY_MAN_ASSIGN`, calls `parcelDelivered($parcel_id, ...)`.

A dead `schudule_tracking22` method also exists in the controller (not routed).

### Zajel

#### 1. Assign a parcel to Zajel (single)

Trigger: `POST /admin/parcel/details/{id}/3pl` with `company=zajel`.

`ParcelController::ThirdPartyLogistics`:

1. Guards on `ZajelService::isConfigured()` (key + customer_code + base_url present), 400 if not.
2. Builds the payload via `ZajelService::buildShipmentPayload($parcel)`.
3. Calls `createShipment`.
4. On failure → inserts `Parcels_3pl` with `awb_number = null` and the full response; returns 422.
5. On success → fetches the label URL via `getShipmentLabel($referenceNumber)`, inserts `Parcels_3pl` with `awb_number = referenceNumber` and `awb_pdf = label URL`, returns the response.

#### 2. Assign in bulk

Trigger: `/admin/bulk_action` → "Assign to 3PL" → company = zajel.

`assignZajelBulk()` — same per-parcel logic as the single flow; no hardcoded driver assignment.

#### 3. Status sync (webhook)

Trigger: `POST /api/zajel/webhook`. Zajel pushes events; no poll job needed.

`ZajelWebhookController::handle($request)`:

1. **Auth**: shared secret in `X-AUTH-API-KEY` matched via `hash_equals`.
2. Parses `reference_number`, `status`, `description`, `event_date_time`.
3. Finds the linked parcel via `Parcels_3pl`.
4. Refreshes `current_status` and `status_datetime`.
5. Maps Zajel status → local `ParcelStatus`:
   - `outfordelivery` → `DELIVERY_MAN_ASSIGN`
   - `pickup_awaited` → `PICKUP_ASSIGN`
   - `pickup_completed` → `RECEIVED_BY_PICKUP_MAN`
   - `inscan_at_hub`, `reachedathub` → `RECEIVED_BY_HUB`
   - `rto` → `RETURN_TO_COURIER`
   - `rto_delivered` → `RETURN_RECEIVED_BY_MERCHANT`
   - `delivered` → routes through `ParcelRepository::parcelDelivered()`
   - `cancelled` → `CANCELLED` (cancel reason threaded into the timeline via `Parcel::$cancellationReason`)
   - `attempted`, `on_hold`, `rto_attempted`, `softdata_*` → log-only

### Aramex

#### 1. Assign a parcel to Aramex (single)

Trigger: `POST /admin/parcel/details/{id}/3pl` with `company=aramex`.

`ParcelController::ThirdPartyLogistics`:

1. Guards on `AramexService::isConfigured()` (username + password + account number + WSDL), 400 if not.
2. Builds the Shipment payload via `AramexService::buildShipmentPayload($parcel)` — Shipper / Consignee parties, Details (Dimensions, ActualWeight, ProductGroup, ProductType, PaymentType, CashOnDeliveryAmount, CustomsValueAmount). COD is enabled by setting `Services: "CODS"` when `cash_collection > 0`.
3. Calls `createShipments([$shipment])`.
4. Aramex's response has a `Shipments.ProcessedShipment` (single object OR array) with `ID` (the AWB) and `ShipmentLabel.LabelURL`. The controller normalizes both shapes.
5. Saves `Parcels_3pl` with `awb_number = ID`, `awb_pdf = LabelURL` on success, or null on failure (with the full response in `response`).

#### 2. Assign in bulk

Trigger: `/admin/bulk_action` → "Assign to 3PL" → company = aramex.

`assignAramexBulk()` — same per-parcel logic as the single flow; pre-flight `RECEIVED_WAREHOUSE` check; no hardcoded driver. Per-parcel error messages are extracted from `ProcessedShipment.Notifications.Notification[0].Message`.

#### 3. Status sync (poll job)

Trigger: `php artisan aramex:sync-tracking` (or scheduled every 15 min via `Console\Kernel`).

`AramexSyncTracking::handle`:

1. Pulls all Aramex AWBs whose linked parcel is NOT in a terminal status (delivered / partial-delivered / cancelled / return-received-by-merchant). Caps at 500 per run (`--limit=N`).
2. Calls `AramexService::trackShipments($awbs, lastUpdateOnly: true)`.
3. The response is a clumsy `TrackingResults.KeyValueOfstringArrayOfTrackingResultmFAtaEAk` array of `{Key: awb, Value: {TrackingResult: [events]}}` — normalized into a flat loop.
4. Maps the latest `UpdateCode` / `UpdateDescription` → local `ParcelStatus` (description-based, since Aramex has many codes — `delivered` → `DELIVERED`, `out for delivery` → `DELIVERY_MAN_ASSIGN`, `picked up` → `RECEIVED_BY_PICKUP_MAN`, etc.).
5. For `DELIVERED`, routes through `ParcelRepository::parcelDelivered()` so balances / notifications fire.
6. For `CANCELLED`, sets `Parcel::$cancellationReason` and lets the model's `updated` hook log the timeline event.
7. Idempotent — already-at-target status still logs the ping but doesn't re-fire side effects.

### Jet (J&T Indonesia)

#### 1. Assign a parcel to Jet (single)

Trigger: `POST /admin/parcel/details/{id}/3pl` with `company=jet`.

`ParcelController::ThirdPartyLogistics`:

1. Guards on `JetService::isConfigured()` (username + api_key + secret + order_url).
2. Builds the order body via `JetService::buildOrderPayload($parcel)`. The payload includes Indonesian-phone normalization (`+62…`), `goodsdesc` / `item_name` stripped of special characters, COD as integer IDR, default pickup window of "now → now+8h", `orderid` prefix `RUSHLY-` truncated to 20 chars.
3. Calls `createOrder($payload)` — the service wraps the payload as `{"detail":[<order>]}`, signs it (`base64(md5(data_param + secret))`), and POSTs form-urlencoded.
4. Parses the response: `success=true` AND `detail[0].status='Sukses'` AND `detail[0].awb_no` non-empty → success.
5. Saves `Parcels_3pl` with `awb_number = detail[0].awb_no` on success; `awb_number = null` + the full `response` (including `detail[0].reason`) on failure.

#### 2. Assign in bulk

Trigger: `/admin/bulk_action` → "Assign to 3PL" → company = jet.

`assignJetBulk()` — same per-parcel logic as the single flow; pre-flight `RECEIVED_WAREHOUSE` check; per-parcel error messages extracted from `detail[0].reason`.

#### 3. Status sync (poll job)

Trigger: `php artisan jet:sync-tracking` (or scheduled every 15 min via `Console\Kernel`).

`JetSyncTracking::handle`:

1. Pulls all Jet AWBs whose linked parcel is not in a terminal status. Caps at 200 per run (`--limit=N`).
2. Iterates one AWB at a time (J&T's `trackOrder` takes one AWB per call).
3. Reads the `history[]` array and maps the **last** entry's `status_code` (with description disambiguation for code 100) to local `ParcelStatus`:
   - `200` → `DELIVERED` (routed through `ParcelRepository::parcelDelivered()` so balances / notifications fire)
   - `162`, `163` → `CANCELLED` (model `updated` hook auto-logs the timeline event with the J&T description)
   - `401` → `RETURN_TO_COURIER`, `402` → `RETURN_RECEIVED_BY_MERCHANT`
   - `101` → `PENDING` (Manifes — order created)
   - `150` / `151` / `152` → `ABNORMAL` (problem / pickup-failed / delivery-problem)
   - `100` + description containing:
     - "telah diterima oleh" → `RECEIVED_BY_PICKUP_MAN` (picked up)
     - "akan dikirimkan ke" → `TRANSFER_TO_HUB`
     - "telah sampai di" → `RECEIVED_BY_HUB`
     - "akan dikirim ke alamat penerima" → `DELIVERY_MAN_ASSIGN` (out for delivery)
   - anything else → log-only

### Logestechs (scaffold complete, partial-live)

**Status as of 2026-05-29**: scaffold + service rewritten against the real endpoints (from a reference `LogesTechsController` shared by the operator). `getVillages` is **live-tested** against company 496. `createShipment` schema is **still unknown** — Logestechs's server returns generic `500 "Unknown error"` for any probe payload, so field discovery is blocked until a captured working request arrives.

**Shape**: Logestechs is a logistics platform itself, not a courier. Each shipment Rushly sends carries a `target_company_id` chosen by the admin at assign time — that's the receiving Logestechs account/company on their side. Scoping is **per-shipment**, not per-tenant.

**Confirmed via live testing**:
- **Auth**: a single `company-id` header. **No API key, no signature** — Logestechs's guest endpoints are gated by knowing the target company id alone. (Confirmed via `getVillages` test against company 496.)
- **Base URL**: `https://apisv2.logestechs.com/api`
- **Backend stack**: Java/Spring with Jackson serializer. Their internal product name is "Palship" (leaked via `com.palship.web.common.data.messages.integration.external.ShippingRequestInfoDto` in a Jackson type-mismatch error).
- **Test company `496`**: configured for Saudi Arabia (`prefix: "SA"`). Regions returned include Bahah, Asir, Eastern Province, Qassim, Riyadh, Jeddah, Makkah, Tabuk.

**Endpoint paths** (from the reference `LogesTechsController`; only `getVillages` is end-to-end confirmed):

| Method | Path | Purpose | Tested |
|--------|------|---------|--------|
| POST | `/ship/request/by-email` | Create shipment | ❌ schema unknown — returns 500 |
| GET | `/guests/{companyId}/packages/tracking?barcode=&id=` | Track | not yet |
| GET | `/guests/packages/status?barcode=&id=` | High-level status | not yet |
| PUT | `/guests/{companyId}/packages/{shipmentId}/cancel` | Cancel | not yet |
| POST | `/guests/{companyId}/packages/pdf` | Print AWB(s) (returns PDF binary) | not yet |
| GET | `/addresses/villages?search=` | Village/area lookup | ✅ live against company 496 |
| GET | `/guests/companies/info-by-domain?domain=` | Resolve company by domain | not yet |

**`getVillages` response shape** (live, from company 496):
```json
{
  "data": [
    {
      "id": 99689, "name": "Riyadh", "englishName": "Riyadh", "arabicName": "الرياض",
      "cityId": 36189, "cityName": "Riyadh", "regionId": 1204, "regionName": "Riyadh",
      "prefix": "SA", "longitude": 0, "latitude": 0,
      "isReceivingPoint": false, "isSelected": true
    }
  ],
  "moneyInfo": null, "totalRecordsNo": 0, "driverName": null
}
```

Logestechs uses **`villageId`** as the location key — local `parcels.city_id`/`area_id` can't be sent verbatim. A mapping layer is needed (resolve via `getVillages` then cache the `villageId`).

**Storage**: column `parcels_3pl.target_company_id` (varchar(64), nullable, indexed). Migration: `database/migrations/2026_05_29_000001_add_target_company_id_to_parcels_3pl.php`. Applied.

**Single-parcel assign** (`/admin/parcel/details/{id}/3pl`):
- Body must include `company=logestechs` AND `logestechs_company_id=<their id>`.
- Returns 422 if `logestechs_company_id` is missing.
- Persists `Parcels_3pl` with `target_company_id` set.

**Bulk assign** (`/admin/bulk_action`):
- Picks `Logestechs` in the company dropdown → a `logestechs_company_id` input becomes visible and required.
- `required_if:company,logestechs` enforced server-side; SweetAlert pre-check enforces client-side.
- All parcels in the batch route to the same `target_company_id`. For multi-target batches, run the action twice.

**createShipment payload — schema unlocked via the Postman collection** (2026-05-29). The body is nested and embeds the customer's login credentials per call:

```json
{
  "email": "<customer email>",
  "password": "<customer password>",
  "pkg": {
    "receiverName": "...",
    "cod": 0,
    "notes": "",
    "supplierInvoice": "...",
    "packageItemsToDeliverList": [{ "name": "...", "cod": 0 }],
    "senderName": "",
    "businessSenderName": "",
    "senderPhone": "",
    "receiverPhone": "...",
    "receiverPhone2": "",
    "serviceType": "STANDARD",
    "shipmentType": "COD",       // or "NORMAL" when no COD
    "quantity": 1,
    "description": "...",
    "integrationSource": "API"   // ours; reference example used "WOOCOMMERCE"
  },
  "destinationAddress": {
    "village": "Ar Riyadh Dist.",
    "cityId": 36134,
    "regionId": 1199,
    "addressLine1": "...",
    "addressLine2": ""
  },
  "pkgUnitType": "METRIC"
}
```

**Success response** (real, captured against company 496):

```json
{
  "id": 46510855,
  "companyId": 496,
  "customerId": 206818,
  "barcode": "100465108553",
  "barcodeImage": "https://elasticbeanstalk-eu-central-1-...amazonaws.com/barcodes/100465108553.png",
  "expectedDeliveryDate": "2026-05-29",
  "originAddressId": 80186920,
  "destinationAddressId": 80186921,
  "originVillageId": 99689,
  ...
}
```

Controller parses `barcode` as the AWB and `barcodeImage` as the `awb_pdf` label URL.

**Per-request credentials**: the admin enters Logestechs account `email` + `password` next to `logestechs_company_id` at assign time. UI inputs are conditional on `company === 'logestechs'`. Server validation: `required_if:company,logestechs` on all three.

**Village resolution**: `LogestechsService::resolveVillage($companyId, $query)` calls `/addresses/villages?search=` and returns `{id, englishName, arabicName, cityId, regionId, cityName, regionName, prefix}`. The controller branch passes the parcel's area/city `en_name` as the query and threads the resolved `{cityId, regionId}` into `destinationAddress`. Future work: cache resolutions per local area, or seed a `cities.logestechs_*` mapping column.

**Sync**: still not implemented. Decide poll vs webhook based on what Logestechs surfaces — `getPackageStatus` (`/guests/packages/status?barcode=`) and `trackShipment` (`/guests/{companyId}/packages/tracking?barcode=`) both exist for polling.

### iMile (planned — stub only)

**Status**: config block + `/admin/integrations` card only. No service class, no controller branch, no sync command. The card always shows **Needs config** until the integration is built.

**Why a stub?** iMile's direct shipping API is gated behind an NDA — they don't expose a public developer portal. The page commonly cited (`https://www.aftership.com/carriers/imile/api`) is AfterShip's *tracking* integration for iMile, not iMile's own create-shipment API. AfterShip lets you track existing iMile shipments via AfterShip's universal API but doesn't create them.

**To finish this integration**, get the iMile API docs (typically supplied by your iMile account manager) and paste them into a session. The build path mirrors Zajel / Aramex / Jet:

1. `app/Services/ImileService.php` — REST client matching their auth scheme.
2. `aramex`-like branch in `ParcelController::ThirdPartyLogistics` (`imile`).
3. `assignImileBulk()` in `ParcelBulkActionController` + `'imile'` added to the company validation + the bulk action UI dropdown.
4. Either `app/Console/Commands/ImileSyncTracking.php` (if iMile uses poll) OR `Webhooks\ImileWebhookController` (if they push).
5. `3PL.md` updated to remove the "planned" marker.

**Reserved env keys** (already in `config/services.php`'s `imile` block, awaiting real values):
`IMILE_API_KEY`, `IMILE_CUSTOMER_CODE`, `IMILE_BASE_URL`, `IMILE_COUNTRY`.

### Service-only endpoints (no UI yet)

`ZajelService` also exposes: `createInternationalShipment`, `trackShipment`, `cancelShipment`, `getCities` (cached 24h), `getAreas` (cached 24h).

`AramexService` also exposes: `printLabel`, `createPickup`, `cancelPickup`, `fetchCountries` (cached 24h), `fetchCities` (cached 24h).

`JetService` also exposes: `cancelOrder(orderid, remark)` (use before J&T picks up the package), `checkTariff(weight, sendSite, destArea)`, `trackOrder(awb)`.

---

## Known issues

Severity grouped. Items tagged **[Panda]**, **[Zajel]**, **[Aramex]**, **[Jet]**, or **[all]**.

### 🔴 Security

1. **[Panda]** `/api/delivery/*` and `/api/panda/schudule_tracking[_temp]` are unauthenticated. Anyone with the URL can create Panda shipments or pull tracking data.
2. **[all]** `parcel.3pl_details` only requires `parcel_read`. Read shouldn't dispatch to a courier. Move to `parcel_status_update` or a new `parcel_3pl_assign` permission.

### 🔴 Multi-tenant data leak

3. **[all]** `Parcels_3pl` has no `company_id`. All creates write a tenant-less row; the Panda tracking job and the Aramex sync command query unscoped; the Zajel webhook resolves by `awb_number` only. One tenant's cron run can call `parcelDelivered($parcel_id, ...)` on **another tenant's parcel** if the AWB collides. Needs:
   - Migration adding `company_id` (with index).
   - `companywise` scope on the model.
   - All create sites + the Panda job + the Aramex job + the Zajel webhook scoped by tenant.

### 🟠 Logic bugs

4. **[Panda]** Hardcoded `delivery_man_id = 12` in the bulk `assign_3pl` branch (`ParcelBulkActionController.php`, ~line 333).
5. **[all]** Hardcoded country / city defaults — Panda hardcodes UAE/Dubai/AED; Zajel falls back to `"DXB"`; Aramex falls back to `"DUBAI"` and uses `ARAMEX_ACCOUNT_COUNTRY_CODE` (defaults to `AE`). Pull country/currency from `settings()`; map cities through the provider's `/GetCities` / `FetchCities` and your local seed data.
6. **[Panda]** Dead code in `ThirdPartyLogistics` — unreachable `return response()->json(json_decode($response, true))`.
7. **[all]** Failure-path inserts a `Parcels_3pl` row with `awb_number = null`. Then `Parcel::lastParcel3pl()` returns the failure row as the "current 3PL" in the UI. Either skip inserting on failure, or have `lastParcel3pl` filter `whereNotNull('awb_number')`.

### 🟠 Robustness

8. **[Panda, Aramex, Jet]** Auto-deliver race in the cron paths. Read parcel status, then call `parcelDelivered` — no row lock. Concurrent admin edits can lose. Wrap in a `whereStatus(DELIVERY_MAN_ASSIGN)` conditional update.
9. **[Panda]** No HTTP error handling on the Panda service. Zajel and Aramex services both wrap calls and return an `_error` envelope; Panda silently returns null on transport failures.
10. **[all]** No unique index on `(parcel_id, parcel_3pl_name, awb_number)`. Retried assigns duplicate rows.

### 🟡 Cleanup

11. **[Panda]** Duplicate methods in `DeliveryPandaService`: `getTracking` and `getListTracking` are byte-identical.
12. **[Panda]** Stale copy of the tracking handler: `schudule_tracking22` in the controller. Not routed, but updates `current_status` unscoped if ever called.
13. **[Panda]** No request validation on the public Panda create endpoints.
14. **[Panda]** `config('services.deliverypanda.timeout')` is read but never applied. (Zajel and Aramex services both honor their `timeout`.)
15. **[Panda]** Misspelled route `schudule_tracking`.
16. **[Panda]** Wrong FK comment in `Parcels_3pl::parcel()`.

### 🟡 Provider-specific TODOs

17. **[Zajel]** Local cancel doesn't call `Zajel::cancelShipment`. AWB stays open at Zajel.
18. **[Zajel, Aramex]** City/area mapping is en_name-based. Set up local `cities` table with provider-compatible codes, or add `zajel_city_code` / `aramex_city_name` columns and seed from `/GetCities` / `FetchCities`.
19. **[Zajel]** International shipment endpoint is not wired into a UI.
20. **[Aramex]** Aramex doesn't expose a post-create CancelShipment in the public SOAP API. `AramexService::cancelShipment()` returns an error envelope explaining that. Pre-pickup cancellation goes through `cancelPickup($pickupGuid)`; post-pickup must go through the Aramex account manager.
21. **[Aramex]** Status code mapping is description-based (e.g. `str_contains($desc, 'delivered')`). Aramex has many `UpdateCode`s; the description is more stable but not exhaustive. Verify mapping coverage against a real account's actual codes — `app/Console/Commands/AramexSyncTracking.php` `mapStatus()`.
22. **[Aramex]** Dimensions are hardcoded at `10×10×10 cm` in `buildShipmentPayload`. Pull from the parcel if you start collecting dimensions per-parcel.
23. **[Jet]** Local cancel doesn't call `Jet::cancelOrder`. J&T only accepts cancellation **before** they pick the package up. Hook this into the model `cancelShipment()` flow so cancellations propagate while still cancellable.
24. **[Jet]** Area-code mapping is unsolved. J&T expects 3-letter `origin_code`/`destination_code` (e.g. `JKT`) and 10-char `receiver_area` district codes (e.g. `JKT001`) — they're not in our `cities`/`areas` tables. The builder falls back to `JET_DEFAULT_ORIGIN_CODE` and uppercased `en_name`, which J&T will reject in production. Add `jet_code` columns to `cities` and `areas` and populate them via J&T's mapping process (integration flow step 4).
25. **[Jet]** Currency is implicitly IDR (`cod` and `goodsvalue` are integer IDR per the docs). If the tenant runs in another currency, conversion happens nowhere. Either pin Jet to IDR-only tenants, or add a convert-to-IDR step in `buildOrderPayload`.
26. **[Jet]** `orderdate` and pickup window are computed in `+07:00` (Jakarta). Verify this matches your tenant timezone; J&T validates the time format strictly.
27. **[Jet]** `awb_pdf` isn't captured. J&T returns AWB number only; label printing is not part of the documented API surface — you'd need their separate label endpoint (not covered here) or print via your own AWB template.

---

## Suggested fix order

Apply roughly in this sequence; each step unblocks the next.

1. **Lock the public Panda routes.** `/api/delivery/*` and `/api/panda/schudule_tracking*` should be behind auth (issue #1).
2. **Add `company_id` to `Parcels_3pl`** + `companywise` scope; patch every create site + Panda cron + Aramex sync + Jet sync + Zajel webhook (issue #3). Closes the multi-tenant leak for all four providers in one move.
3. **Drop Panda's hardcoded `delivery_man_id = 12`** and the country/currency hardcoding everywhere (issues #4, #5).
4. **Remove Panda's dead/duplicate code** — `schudule_tracking22`, the unreachable `return`, and the duplicate `getListTracking` (issues #6, #11, #12).
5. **Hook Zajel + Jet cancellation into the local cancel flow** (issues #17, #23).
6. **City/area code mapping** — populate Zajel, Aramex, and Jet city/area codes via their respective lookup endpoints (issues #18, #24).
7. **Tighten the rest** — unique index, transport-error envelope on Panda, `parcel.3pl_details` permission upgrade, Aramex status-code mapping verification, Aramex dimensions, Jet currency handling, Jet AWB label (issues #2, #9, #10, #21, #22, #25, #27).
