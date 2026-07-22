# GAPS.md

Log of issues found and fixes applied during the 2026-06-19 log triage.

## Source
`storage/logs/laravel-2026-06-19.log` — 21 ERROR entries, falling into 8 distinct
classes. Investigated each, fixed the one with a clear root cause in current
code, confirmed the others were either already mitigated by the working-tree
changes or are one-shot anomalies not reproducible against the live schema.

---

## Fixed

### Parcel.php — `in_array(): haystack null` on `parcels_id`
- **Files:** `app/Models/Backend/Parcel.php` (lines 391, 402)
- **Trigger:** Recurring (2× in log) — admin parcel index for user 14.
- **Root cause:** `Invoice::parcels_id` is JSON-cast to `array`, but rows where
  the column is `NULL` come back as `null`, not `[]`. `in_array($id, null)` is a
  hard `TypeError` in PHP 8.x.
- **Fix:** Coerce to array at the call site —
  `in_array($this->id, (array) ($in->parcels_id ?? []))` in both
  `getParcelInvoiceAttribute()` and `getAdminParcelInvoiceAttribute()`.

---

## Already mitigated in working tree (no further change needed)

### `DashbordController::index` — `array_values()` on int
- **Log:** 2× at 17:00:30, 17:00:36 then silent.
- **Working tree** introduces `asSeries()` / `asScalar()` helpers (lines 340-355)
  that short-circuit when the repo returns a `sum()` scalar instead of an array.
  No further occurrences in the log after the helpers landed.

### `Backend\ParcelController::renderParcelIndex` — `Unknown column 'title'`
- **Log:** 2× at 17:06, then silent.
- **Working tree** trims the select at line 130 to `->get(['id', 'business_name'])`;
  the `title` column has never existed on `merchants` (verified via
  `Schema::getColumnListing('merchants')`).

---

## Single-occurrence, root cause not reproducible

### `Backend\ParcelController::create` — `Attempt to read property "id" on int`
- **Log:** 1× at 17:38:51, user 78. No recurrence.
- **Trace:** `collect($merchants)->map(fn ($m) => ['id' => $m->id, …])` —
  one item in the collection presented as `int`.
- **Repro attempt:** Direct `Merchant::where('status', 1)->get()` in tinker
  returns Eloquent models for every row; `companywise` scope, model casts and
  the global eager-load list show nothing that would substitute an int.
- **Decision:** No speculative defensive shim added. Re-investigate if it
  recurs — likely a transient deploy / opcache race during the 15:40 edit window.

---

## Resolved by deploy (vite/inertia bootstrap)

The earlier batch of 6 errors (15:23 – 15:35) was all bootstrap noise from an
incomplete frontend build:

- `View [app] not found` — Inertia root view path mismatch.
- `Class "Tighten\Ziggy\Ziggy" not found` — Ziggy not autoloaded.
- `file_get_contents(.../public/build/manifest.json)` — Vite assets unbuilt.

All three classes stop appearing after 15:35:27 — a `composer install` +
`npm run build` evidently completed before user traffic returned at 17:00.
No code change needed.

---

## Other artisan noise

`The "--columns" option does not exist.` (16:00) — someone ran
`php artisan route:list --columns=…`; the option was removed upstream. The
current `route:list` supports `--sort=<column>` / `--json` instead. Not a code
bug; flagged so the operator updates whichever wrapper invoked it.

---

## Health-check results

- `migrate:status`: all 200+ migrations Ran on every tenant connection (1-20).
- `config:clear`, `view:clear`, `cache:clear`: all succeeded.
- `route:list --except-vendor`: routes resolve.
- `about`: production env, drivers nominal.

**Note (not a fix, flag only):** `APP_DEBUG=true` in production. Stacktraces
including DB queries are being rendered to authenticated users on errors. This
is the expected source of the SQL-string disclosure inside the log itself.
Should be `APP_DEBUG=false` for production; left alone here because flipping it
is an operational decision, not a code change.

---

## Subsequent architectural closures (2026-06-30 → 2026-07-22)

Not from log triage; recording here for continuity so the "known gaps" list
stays honest as modules land.

### Shipping module (see `docs/shipping-architecture.md` §12)
- ✅ **AWB log-prune job** — `shipping:prune-logs` scheduled daily at 03:15
  (Kernel). Enforces `config('shipping.logging.retention_days')`.
- ✅ **HTTP-level retry** — `AbstractProvider::http()` wraps calls in
  `->retry($tries, $sleepMs)` filtered to `ConnectionException`. Absorbs 1-2
  transient failures per job attempt.
- ✅ **Bulk-assign UX** — `/admin/bulk_action` picks a saved connection instead
  of asking for email/password every submission.
- ✅ **Route-order bug** — `POST /admin/shipping/connections/test` was
  swallowed by wildcard `POST /connections/{provider}`; literal routes now
  precede the wildcard.
- ✅ **Edit-page test flow** — `__keep__` sentinel sent as plaintext password
  to Logestechs. Backend now hydrates from the row via `connection_id`.
- ✅ **`shipmentType` enum** — `ShipmentRequestMapper` was sending `'NORMAL'`
  for non-COD, rejected by Logestechs with `Invalid Parameter 'model.shipmentType'
  null`. Always sends `'COD'`; `pkg.cod` value distinguishes COD vs non-COD.
- ✅ **`ShipmentDTO::fromParcel` `country` reference** — `Parcel` has no
  `country` relationship; threw `RelationNotFoundException`. Removed.

### Commerce module (see `COMMERCE.md`)
- ✅ **API-log retention** — `commerce:prune-logs` daily at 03:00.

### Bulk-action page (`/admin/bulk_action`)
- ✅ **Coverage expansion** — added Print AWBs and Export to Excel. Existing
  Assign 3PL / Change Status / Cancel unchanged. (Add Note + Send SMS pills
  were added and later removed from the UI — the underlying `add_note` /
  `send_sms` action_type branches remain on the apply endpoint in case a
  future UI wants to bring them back.)
- ✅ **Live preview table** — pasting shipment IDs into the textarea calls
  `parcel.check_bulk_action` (350 ms debounce) and renders matched parcels
  in a 30-per-page table with clickable status badges that filter the
  preview client-side. Colours come from the same
  `ParcelStatusHelper::color()` palette used on `/admin/parcel/index`.
- ✅ **`/admin/parcel/index` bulk-action parity** — the two placeholder
  actions ("Received by hub", "Assign return to merchant") now route
  through the same `/admin/parcels/bulk/apply` endpoint (change_status
  with the appropriate `ParcelStatus` target); the print button was
  renamed to "Print AWBs" for terminology parity with `/admin/bulk_action`.

### Docs
- ✅ **Per-module doc coverage** — `COMMERCE.md`, `OMS.md`, `FULFILLMENT.md`
  written. `README.md` replaced with a module index. `ARCHITECTURE.md` +
  `RUSHLY_APPS_OVERVIEW.md` refreshed with module list.
