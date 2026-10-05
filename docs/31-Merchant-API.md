# 31 — Merchant API (rushly-api façade)

> Status: implemented (v1). The public API lives in the sibling repo
> **`rushly-api`**; the internal endpoints it calls live here in `rushly-saas`.

## What it is

A standalone, versioned **Merchant/Partner API** for external integrations,
exposed at `api.rushly.tech/v1` — **without** exposing the internal v10 API or
the shared `apiKey`. `rushly-saas` remains the Single Source of Truth; the
façade owns no shipment/parcel/COD/hub/accounting logic.

```
External merchant / ERP / partner
        │  Authorization: Bearer rly_live_… / rly_test_…
   api.rushly.tech/v1
        │
   rushly-api        auth · scopes · idempotency · rate limit · status mapping · OpenAPI
        │  Authorization: Bearer <internal service token>
        ▼
   rushly-saas  /api/internal/v1/merchant/*   ← SSOT: Parcel domain, cancellation guard, ParcelEvent
```

## URLs

| | Production | Staging |
|---|---|---|
| API | `https://api.rushly.tech/v1` | `https://staging-api.rushly.tech/v1` |
| Docs | `https://api.rushly.tech/docs` | `https://staging-api.rushly.tech/docs` |
| Spec | `https://api.rushly.tech/openapi.json` | `https://staging-api.rushly.tech/openapi.json` |

## Public endpoints

| Method | Path | Scope |
|---|---|---|
| GET | `/health` | — |
| GET | `/v1/statuses` | `statuses:read` |
| GET | `/v1/shipments` | `shipments:read` |
| POST | `/v1/shipments` | `shipments:create` (+ `Idempotency-Key`) |
| GET | `/v1/shipments/{tracking_number}` | `shipments:read` |
| POST | `/v1/shipments/{tracking_number}/cancel` | `shipments:cancel` |
| GET | `/v1/shipments/{tracking_number}/tracking` | `tracking:read` |
| GET | `/v1/shipments/{tracking_number}/history` | `tracking:read` |

## API key management

Keys are per **merchant + company + environment**, with a fixed scope set,
stored hashed (prefix + SHA-256-HMAC; the secret is shown once). Mint with:

```bash
# in rushly-api
php artisan api:client:create --merchant=<id> --company=<id> --env=test \
  --scopes=shipments:create,shipments:read,shipments:cancel,tracking:read,statuses:read
```

A `rly_test_*` key only works on staging; `rly_live_*` only on production.

## Status mapping

The façade's `PublicStatusMapper` maps every one of the 41 internal
`App\Enums\ParcelStatus` values onto 11 stable public codes
(`created, pickup_scheduled, picked_up, at_hub, in_transit, out_for_delivery,
delivered, delivery_failed, returning, returned, cancelled`), plus a defensive
`unknown`. Highlights:

- `PENDING(1) → created`, `DELIVERY_MAN_ASSIGN(7) → out_for_delivery`,
  `DELIVERED(9)`/`PARTIAL_DELIVERED(32) → delivered`,
  `RETURN_TO_COURIER(24) → delivery_failed`,
  `RETURN_RECEIVED_BY_MERCHANT(30)`/`RETURNED_MERCHANT(13) → returned`,
  `CANCELLED(41) → cancelled`.
- `_CANCEL` reversal statuses map to the public phase they revert to (not
  `cancelled`).
- `ABNORMAL(36) → unknown` (logged). No status is silently dropped.

**Every `ParcelStatus` value has a public mapping** (guarded by a unit test).

## Idempotency, errors, rate limiting

- `POST /v1/shipments` requires `Idempotency-Key`. Replays of the same key+body
  return the stored response; a different body is `409 IDEMPOTENCY_CONFLICT`;
  transient 5xx releases the key for retry.
- Stable error codes (`AUTH_*`, `VALIDATION_ERROR`, `SHIPMENT_*`,
  `IDEMPOTENCY_*`, `RATE_LIMIT_EXCEEDED`, `UPSTREAM_*`, `INTERNAL_ERROR`) in a
  consistent envelope. No internal details are ever leaked.
- Per-key rate limit (default 120/min) with `X-RateLimit-*` + `Retry-After`.

## Internal integration (this repo)

Added here:

- **`VerifyInternalServiceToken`** middleware — strong env-backed token via
  `hash_equals` (NOT the shared `apiKey`). Set `RUSHLY_INTERNAL_API_TOKEN`.
- **Route group** `/api/internal/v1/merchant/*` (`routes/api.php`).
- **`App\Services\InternalApi\MerchantShipmentService`** — resolves `shop_id`
  (merchant default shop), `city_id` (by name — errors if unmatched, never
  invented), `category_id`/`delivery_type_id` (configurable defaults in
  `config/internal_api.php`); creates via the domain model with the owning
  company's tracking prefix; reuses `Parcel::cancelShipment()` (PENDING-only
  guard); **every query is explicitly scoped to the validated
  company_id + merchant_id** (never the fail-open `settings()`), with
  merchant-belongs-to-company validation.
- **Resilience:** the three parcel status-writeback observers (Salla/Zid/Woo)
  are now wrapped so a writeback failure never breaks a status change.

### Internal contract

All endpoints take `company_id` + `merchant_id` (validated, explicitly scoped).
Responses return the internal status integer; the façade maps it to a public
code. `POST /shipments` → `201 {shipment}`; `cancel` → `200 {shipment}` /
`409 {error.code: NOT_CANCELLABLE, details.current_status}` / `404`;
`tracking`/`history` return PII-safe projections (status + label + timestamp,
actor/hub by name only — no internal ids or GPS).

## Deployment

See `rushly-api/deploy/`. Set `RUSHLY_INTERNAL_API_TOKEN` identically on each
rushly-api environment and its matching rushly-saas environment; keep staging
and production tokens distinct.

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| `503 UPSTREAM_UNAVAILABLE` | `RUSHLY_INTERNAL_API_URL`/`TOKEN` unset or wrong on rushly-api |
| `401` from internal API | `RUSHLY_INTERNAL_API_TOKEN` mismatch between the two apps |
| `422 CITY_NOT_FOUND` on create | Destination city not in `cities` (by `name`/`en_name`) |
| `422 NO_DEFAULT_SHOP` | Merchant has no active default shop |
| `401 AUTH_INVALID` with a valid-looking key | Wrong environment (test key on live or vice versa) |
