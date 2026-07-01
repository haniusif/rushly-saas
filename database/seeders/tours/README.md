# Authoring onboarding tours

This directory holds JSON definitions for system-wide onboarding tours.
One file per tour. The seeder is idempotent — re-running it upserts by
`key` and replaces the tour's steps.

Companion docs:
- `TOURS.md` at repo root — full architecture, DB schema, API surface.
- `resources/js/Tour/` — the frontend engine.
- `/admin/tours` (in-app) — the admin manager UI for per-tenant tours.

---

## Run the seeder

```bash
php artisan db:seed --class="Database\\Seeders\\TourSeeder"
```

Rows are inserted as **system templates** (`company_id = NULL`). A tenant
admin can override any of them via the `/admin/tours` UI by creating a
tour with the **same `key`** — tenant rows win over system rows in
`TourRepository::forUser()`.

---

## Tour JSON schema

```json
{
    "key":           "unique.dotted.identifier",
    "module":        "dashboard",
    "title":         "Shown on the launcher menu",
    "description":   "One-liner shown under the title",
    "role_scope":    [1, 2, 6],
    "version":       1,
    "is_active":     true,
    "auto_start":    false,
    "trigger_route": "route.name.for.autostart",
    "steps": [ { "target": {...}, "placement": "...", "translations": {...} } ]
}
```

| Field | Required | Notes |
|---|---|---|
| `key` | ✅ | Snake-cased dotted string, e.g. `admin.finance.overview`. Unique across system tours. |
| `module` | — | Grouping tag surfaced in analytics + admin UI. Free-form (`dashboard`, `parcels`, `wms`, `finance`, …). |
| `title` | ✅ | Shown in the "Take a tour" dropdown and the welcome modal. Not localized here — use step translations for content. |
| `description` | — | One-line hook shown below the title. |
| `role_scope` | — | Array of `UserType` ints: `[1]=Admin`, `[2]=Merchant`, `[3]=Deliveryman`, `[4]=Incharge`, `[5]=Hub`, `[6]=SuperAdmin`. **Empty or omitted = all roles.** |
| `version` | — | Bump to re-show the tour to users who already completed the previous version. Progress is keyed by `(user_id, tour_key, tour_version)`. |
| `is_active` | — | Default `true`. Toggle off to hide without deleting. |
| `auto_start` | — | If `true`, the welcome modal offers this tour on the user's first login. Only ONE auto-start tour should exist per role. |
| `trigger_route` | — | Route name used by the `TourLauncher` to promote this tour to the top of the launcher menu when the user is on that page. Doesn't gate visibility. |

### Step schema

```json
{
    "target": {
        "type":  "data-tour",   // or "selector" or "route-name"
        "value": "sidebar-nav_parcels"
    },
    "placement": "auto",        // top | bottom | start | end | auto
    "spotlight_padding": 8,
    "translations": {
        "en": { "title": "…", "body": "…" },
        "ar": { "title": "…", "body": "…" }
    },
    "action": null              // reserved for navigate/wait_for; see below
}
```

| Field | Required | Notes |
|---|---|---|
| `target.type` | ✅ | `data-tour` (preferred), `selector` (CSS), or `route-name` (used with `action.navigate` only). |
| `target.value` | ✅ | The value your target type expects. |
| `placement` | — | Where the popover renders relative to the target. `auto` picks the side with the most viewport room and respects RTL. |
| `spotlight_padding` | — | Pixel padding around the highlighted element. Default 8. |
| `translations.{en,ar}.title` | ✅ (at least one locale) | Card title, ≤ 191 chars. |
| `translations.{en,ar}.body` | — | Card body. Newlines preserved. |
| `action` | — | Reserved for later: `{"navigate": "/some/path"}` will teleport the user before the next step. Not shipped in every flow yet. |

### If a target element isn't on the current page

The engine emits an `element_missing` analytics event and centers the
popover on the viewport — the user can still hit **Next** or **Skip**. No
crash. Design your tour so the *first* step's target is always on the
page the user lands on (i.e. `trigger_route`).

---

## Available `data-tour` anchors — full catalog

**Rule:** every sidebar item's `<a>` gets `data-tour="sidebar-${tKey}"`
via the layout render loop. Add new ones by adding a `data-tour="…"`
attribute directly on the target element in the page's `.jsx`.

### Merchant layout (`MerchantLayout.jsx`)

| Group | tKey | Anchor |
|---|---|---|
| Overview | `nav_dashboard` | `sidebar-nav_dashboard` |
| Overview | `nav_knowledge_base` | `sidebar-nav_knowledge_base` |
| Operations | `nav_parcels` | `sidebar-nav_parcels` |
| Operations | `nav_shops` | `sidebar-nav_shops` |
| Operations | `nav_support` | `sidebar-nav_support` |
| Finance | `nav_statements` | `sidebar-nav_statements` |
| Finance | `nav_account_tx` | `sidebar-nav_account_tx` |
| Finance | `nav_invoices` | `sidebar-nav_invoices` |
| Finance | `nav_payment_received` | `sidebar-nav_payment_received` |
| Reports | `nav_total_summary` | `sidebar-nav_total_summary` |
| Reports | `nav_parcel_reports` | `sidebar-nav_parcel_reports` |
| Settings | `nav_cod_charges` | `sidebar-nav_cod_charges` |
| Settings | `nav_delivery_charges` | `sidebar-nav_delivery_charges` |

Plus (elsewhere in the merchant panel):

| Anchor | Where |
|---|---|
| `topbar-notifications` | Bell icon in `MerchantLayout` topbar |
| `dashboard-kpis` | 4-tile KPI row on `/dashboard` |
| `dashboard-amounts` | 3-card amount row on `/dashboard` |
| `dashboard-charts` | Line + donut chart section on `/dashboard` |
| `dashboard-reports` | 12-tile grid at the bottom of `/dashboard` |

### Admin layout (`AdminLayout.jsx`)

All 44 sidebar items follow the same pattern: `sidebar-${tKey}`.

| Group | Anchors |
|---|---|
| Main | `sidebar-menu_dashboard`, `sidebar-menu_performance`, `sidebar-menu_knowledge_base` |
| Parcels | `sidebar-menu_parcel`, `sidebar-menu_bulk_action`, `sidebar-menu_ndr`, `sidebar-menu_abnormal` |
| WMS | `sidebar-menu_wms_dashboard`, `sidebar-menu_products`, `sidebar-menu_stock`, `sidebar-menu_locations`, `sidebar-menu_grn`, `sidebar-menu_fulfillment`, `sidebar-menu_outbound`, `sidebar-menu_adjustments`, `sidebar-menu_cycle_counts`, `sidebar-menu_damage` |
| Operations | `sidebar-menu_deliveryman`, `sidebar-menu_tms`, `sidebar-menu_hubs`, `sidebar-menu_merchants`, `sidebar-menu_pickup_request` |
| Finance | `sidebar-menu_payment_received`, `sidebar-menu_payout`, `sidebar-menu_accounts`, `sidebar-menu_wallet_request` |
| HR | `sidebar-menu_users_roles`, `sidebar-menu_payroll`, `sidebar-menu_assets` |
| Productivity | `sidebar-menu_todo`, `sidebar-menu_support`, `sidebar-menu_news`, `sidebar-menu_push_notification`, `sidebar-menu_fraud` |
| Billing | `sidebar-menu_subscribe`, `sidebar-menu_subscription`, `sidebar-menu_reports` |
| ZATCA | `sidebar-menu_zatca_invoices`, `sidebar-menu_zatca_settings` |
| CMS | `sidebar-menu_front_web` |
| System | `sidebar-menu_logs` |
| Settings | `sidebar-menu_general_settings`, `sidebar-menu_integrations`, `sidebar-menu_delivery_category`, `sidebar-menu_delivery_charge`, `sidebar-menu_delivery_type`, `sidebar-menu_liquid_fragile`, `sidebar-menu_sms_setting`, `sidebar-menu_sms_send_setting`, `sidebar-menu_notification_settings`, `sidebar-menu_googlemap_setting`, `sidebar-menu_social_login_settings`, `sidebar-menu_payment_gateway_setup`, `sidebar-menu_packaging`, `sidebar-menu_assets_category`, `sidebar-menu_invoice_generate` |

Plus:

| Anchor | Where |
|---|---|
| `topbar-notifications` | Bell icon in `AdminLayout` topbar |

---

## Copy-paste templates

### Template 1 — Simple sidebar walkthrough

The 90% case. Point at 3–5 sidebar items in sequence.

```json
{
    "key": "merchant.MYMODULE.overview",
    "module": "MYMODULE",
    "title": "Module tour",
    "description": "What this module does.",
    "role_scope": [2],
    "version": 1,
    "is_active": true,
    "auto_start": false,
    "trigger_route": "merchant-panel.MYMODULE.index",
    "steps": [
        {
            "target": { "type": "data-tour", "value": "sidebar-nav_ITEM" },
            "placement": "end",
            "translations": {
                "en": { "title": "…", "body": "…" },
                "ar": { "title": "…", "body": "…" }
            }
        }
    ]
}
```

### Template 2 — First-login welcome (auto-start)

Only ONE auto-start tour should exist per role. Set `auto_start: true` +
`trigger_route: dashboard.index`.

```json
{
    "key": "merchant.dashboard.welcome",
    "auto_start": true,
    "trigger_route": "dashboard.index",
    "role_scope": [2]
}
```

### Template 3 — Deep tour of a specific page

Add `data-tour="…"` attributes on individual elements inside the page's
`.jsx` file first. See §"Adding new anchors" below.

```json
{
    "steps": [
        { "target": { "type": "data-tour", "value": "parcels-toolbar" }, ... },
        { "target": { "type": "data-tour", "value": "parcels-filter-status" }, ... },
        { "target": { "type": "data-tour", "value": "parcels-row-actions" }, ... }
    ]
}
```

### Template 4 — CSS selector fallback

When you can't easily add a `data-tour` attribute (third-party widget,
generated markup). Fragile — CSS refactors break it. Prefer `data-tour`.

```json
{ "target": { "type": "selector", "value": "#some-specific-id .child" } }
```

---

## Adding new `data-tour` anchors to a page

1. Open the page `.jsx` file, e.g. `resources/js/Pages/Merchant/Invoice/Index.jsx`.
2. Add the attribute on the element you want the spotlight on:

    ```jsx
    <Card data-tour="invoice-table">…</Card>
    <button data-tour="invoice-export-btn">…</button>
    ```

3. Run `npm run build` (or `npm run dev` while iterating).
4. Reference the new anchor in a tour step:

    ```json
    { "target": { "type": "data-tour", "value": "invoice-table" } }
    ```

Naming convention: `<page>-<component>-<detail>` — e.g. `invoice-table`,
`parcels-filter-status`, `dashboard-kpis`. Anchors are global — pick names
that won't collide across pages.

---

## Currently seeded tours (18 total)

### Admin (12, `role_scope: [1, 6]`)

| Key | Steps | Auto-start |
|---|---|---|
| `admin.dashboard.welcome` | 2 | ✅ First login |
| `admin.parcels.overview` | 4 | — |
| `admin.wms.overview` | 10 | — |
| `admin.operations.overview` | 5 | — |
| `admin.finance.overview` | 4 | — |
| `admin.hr.overview` | 3 | — |
| `admin.productivity.overview` | 5 | — |
| `admin.billing.overview` | 3 | — |
| `admin.zatca.overview` | 2 | — |
| `admin.cms.overview` | 1 | — |
| `admin.system.overview` | 1 | — |
| `admin.settings.overview` | 14 | — |

### Merchant (6, `role_scope: [2]`)

| Key | Steps | Auto-start |
|---|---|---|
| `merchant.dashboard.welcome` | 6 | ✅ First login |
| `merchant.operations.overview` | 3 | — |
| `merchant.finance.overview` | 4 | — |
| `merchant.reports.overview` | 2 | — |
| `merchant.settings.overview` | 2 | — |
| `merchant.parcels.overview` | 1 | — (kept as short "focus" tour) |

---

## Idempotency + tenant overrides

- **Re-running the seeder wipes each system tour's step list** and replaces
  it with the JSON's steps. If a tenant admin edited system-tour steps
  via `/admin/tours`, those edits are lost on re-seed. **Mitigation:**
  edit at the tenant level by cloning — the admin manager UI lets a
  tenant create a `company_id`-scoped tour with the same `key`, which
  wins in the resolver (`TourRepository::forUser`).
- **Deleting a JSON file does NOT delete the tour** from the DB. Remove
  it manually with `mysql> DELETE FROM tours WHERE company_id IS NULL
  AND \`key\` = '…'` (steps cascade via the `belongsTo` relation — or
  delete from `tour_steps` first if you're paranoid).
- Bumping `version` on a system tour forces every user who already
  completed v1 to see v2 fresh — the `user_tour_progress.tour_version`
  unique constraint enforces this.

---

## Testing an authored tour

1. `php artisan db:seed --class="Database\\Seeders\\TourSeeder"`
2. `npm run build`
3. Log in as a matching-role user on any tenant subdomain.
4. Click **"Take a tour"** in the topbar → pick your tour.
5. Analytics land in `/admin/tours/analytics` (started/completed/skipped/dropoff/avg-time-per-step).

For pixel-level testing without a real user: `/admin/tours/{id}/preview`
plays the tour against whatever page you're on.
