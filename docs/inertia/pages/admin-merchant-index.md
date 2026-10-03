# `/admin/merchant/index` — merchants list

`resources/js/Pages/Admin/Merchant/Index.jsx`

Merchant directory with a card view (default) + list view toggle.
Matches the legacy dual-view layout.

## Route + controller

- `GET /admin/merchant/index` → `MerchantController::index`,
  permission-gated by `merchant_read`
- `renderMerchantIndex($paginator, $request)` returns
  `Inertia::render('Admin/Merchant/Index', ...)`

Sibling routes (`merchant.create`, `merchant.edit`, `merchant.view`,
`merchant.invoice.generate`, `merchant.impersonate`, `merchant.apply`)
still hit legacy blades / forms.

## Controller props

Each row carries:

```jsonc
{
  "id", "unique_id", "name", "email", "image", "mobile",
  "business_name", "hub_name",
  "countries": [{ "code", "name" }] (max 3 + countries_more),
  "covers_all_cities": true|false,
  "city_count",
  "services": [...],
  "status": 1|0,
  "wallet_active": true|false,
  "current_balance", "computed_balance",
  "urls": { "view", "edit", "invoice", "impersonate" },
  "impersonate_name": "..."
}
```

`pagination` is the standard meta object. `permissions` carries
`create`, `view`, `update`, `delete`. `urls` includes `apply` so the
header's "Copy apply link" button can write `route('merchant.apply')`
to the clipboard.

## UI

**Top card** — title, client-side search box (filters across name /
email / business / mobile / unique_id / hub), view-toggle (card / list),
"Copy apply link" (clipboard, flashes a checkmark for 1.5 s), Add CTA.

**Card view** (default, responsive 1/2/3/4 cols):
- Header row with avatar (or initials), name, `#unique_id ·
  business_name`, kebab dropdown
- Body rows: mobile (phone icon), email (mail icon), hub
  (building icon), coverage (globe icon + country code badges +
  "all cities" or "N cities" pill)
- Footer: status + wallet badges, computed balance (right-aligned)

**List view** — table with 10 columns: #, ID (mono), Name + email (with
avatar), Hub, Business, Coverage, Phone, Status (active + wallet),
Computed balance, Actions.

**Actions dropdown** items, permission-gated:
- Invoice generate
- View (gated on `merchant_view`)
- Edit (gated on `merchant_update`)
- Impersonate (gated on `merchant_update`) — POSTs to
  `r.urls.impersonate` via a synthetic `<form>` with the CSRF token,
  after `window.confirm(...)`. Server-side this stores
  `impersonator_id` in the session and signs the admin in as the
  merchant user; the "back to admin" banner comes from `HandleInertiaRequests::share()`.

## Search behaviour

The search input is **client-side only** — it filters the rows
currently loaded by the paginator (50 per page). For server-side filter
across all merchants, a `MerchantController::filter()` method would
need to be added (the legacy controller has no `filter()`).

## Deferred

- Delete action — the legacy form was commented out, so it's not
  surfaced here either
- Create / Edit / View / Invoice still hit legacy blades
