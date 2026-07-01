# Merchant Dashboard — Inertia/React Port

The merchant `/dashboard` was ported from Blade (`backend.merchant_panel.dashboard`) to Inertia/React on 2026-06-27. This document captures the contract between the controller and the React component, and where to make common edits.

---

## Files

| Role | Path |
|---|---|
| Controller | `app/Http/Controllers/DashbordController.php` — `index()` MERCHANT branch (~ line 100–225) plus `merchantDashboardLabels()` (helper, near `adminDashboardLabels()`) |
| React page | `resources/js/Pages/Merchant/Dashboard/Index.jsx` |
| Layout | `resources/js/Layouts/MerchantLayout.jsx` |
| Translations | `lang/en/dashboard.php`, `lang/ar/dashboard.php`, `lang/*/merchant.php`, `lang/*/levels.php` |
| Old Blade (still on disk, not rendered) | `resources/views/backend/merchant_panel/dashboard.blade.php`, `resources/views/backend/merchant_panel/dashboard-chart.blade.php` |

The route is unchanged: `GET /dashboard` → `DashbordController::index` → dispatches by `Auth::user()->user_type`. When the user is a merchant (`UserType::MERCHANT`), `Inertia::render('Merchant/Dashboard/Index', ...)` is returned instead of the old `view()`.

---

## Props contract

The controller hands the component a flat shape. Anything missing on the page should be added here first — the React component reads each block by name.

```php
return Inertia::render('Merchant/Dashboard/Index', [
    'currency'      => string,                  // e.g. "د.إ"
    'merchant'      => ['id' => int, 'business_name' => string] | null,
    'services'      => array<string>,           // ['last_mile', 'fulfillment', 'storage']
    'request_date'  => string|null,             // current filter value
    'parcel_kpis'   => [
        'total'      => int,
        'delivered'  => int,
        'returned'   => int,
        'in_transit' => int,
    ],
    'active_amounts' => [
        'cash_collection' => float,
        'selling_price'   => float,
        'net_profit'      => float,
    ],
    'fees_amounts' => [
        'liquid_fragile' => float,
        'packaging'      => float,
        'vat'            => float,
    ],
    'delivery_amounts' => [
        'delivery_charge' => float,
        'cod'             => float,
        'delivery_total'  => float,
    ],
    'reports' => [                              // 12-tile grid at the bottom
        'total_sales'         => float,
        'total_delivery_fees' => float,
        'total_vat'           => float,
        'net_profit'          => float,
        'current_balance'     => float,
        'opening_balance'     => float,
        'merchant_vat'        => float,
        'payment_processing'  => float,
        'paid_amount'         => float,
        'total_shop'          => int,
        'total_parcel_bank'   => int,
        'total_payment_req'   => int,
    ],
    'series' => [                               // line chart, last 8 days
        'dates'        => array<string>,        // ["2026-06-20", ...]
        'totals'       => array<int>,
        'pendings'     => array<int>,
        'delivers'     => array<int>,
        'par_delivers' => array<int>,
        'returns'      => array<int>,
    ],
    'pie' => [                                  // donut chart slices
        'pending'           => int,
        'delivered'         => int,
        'partial_delivered' => int,
        'returned'          => int,
    ],
    'urls' => [
        'filter'            => string,          // merchant-panel.dashboard.filter
        'parcels'           => string,          // merchant-panel.parcel.index
        'parcels_delivered' => string,          // status-filtered list
        'parcels_returned'  => string,
    ],
    't' => array<string, string>,               // see Translations below
]);
```

Numeric props are always coerced (`(int)` / `(float)`) before reaching Inertia, so the React side never has to defend against strings or nulls.

---

## Page anatomy

| Section | Component(s) | Source data |
|---|---|---|
| Header + date filter | inline form posting to `urls.filter` | `request_date` |
| Services badges | inline span list, color-coded by `SERVICE_TONE` | `services` |
| 4 parcel KPI tiles | `KpiTile` | `parcel_kpis` + `urls.*` (each tile links to a parcel list) |
| 3 amount cards | `AmountCard` + `AmountRow` | `active_amounts`, `fees_amounts`, `delivery_amounts` |
| Line chart (last 8 days) | `LineChart` | `series` |
| Donut breakdown | `DonutChart` | `pie` |
| 12-tile "All reports" grid | `ReportTile` (mapped from local `reportTiles` array) | `reports` |

All helper components live inside `Index.jsx` — no separate component files. Tone classes follow Tailwind's `bg-* text-*` pattern (e.g. `bg-emerald-50 text-emerald-600`).

---

## Charts — inline SVG, no library

Both charts are hand-rolled SVG inside `Index.jsx`:

- **`LineChart({ series, dates, height })`** — multi-series polyline with gridlines and date ticks. `series` is `[{ name, data, color }]`. Auto-scales to `max(data)`.
- **`DonutChart({ slices, size, thickness })`** — `slices` is `[{ name, value, color }]`. Renders a track ring, one arc per slice, and a center total with a legend on the side.

Reasoning: this matches `Pages/Admin/Dashboard/Index.jsx` (uses an inline `Sparkline` for the same reason). Adding ApexCharts / Recharts would add ~80 KB gzipped for two basic charts.

If you need richer interactivity later (tooltips, zoom, time-series brushing), the entry point is to replace `LineChart` and `DonutChart` with the library equivalents — the data shape on `series` / `pie` is already library-friendly.

---

## Translations

The controller exposes a flat `t` object via `merchantDashboardLabels()`. Each key falls back to inline English when the underlying lang key is missing, so a missing translation never leaks into the UI (the old Blade page printed `dashboard.parcels_chart` literally — that's no longer possible).

Pattern for adding a new translatable label:

```php
// in merchantDashboardLabels()
'my_new_label' => __('dashboard.my_new_label') ?: 'My new label',
```

```jsx
// in Index.jsx
<h3>{t.my_new_label}</h3>
```

Keys that are intentionally added in this port (in case you're scanning a translations PR):

- `dashboard.parcels_chart` / `parcels_breakdown` / `series_total` — added to `lang/en/dashboard.php` and `lang/ar/dashboard.php`.

All the other keys (`dashboard.total_parcel`, `total_delivery_charge`, etc.) were already in the lang files and are reused.

---

## Common edits

### Add a parcel KPI tile

1. Add a counter in the controller's MERCHANT branch and include it on `parcel_kpis`.
2. Add a `<KpiTile />` in `Index.jsx` under the existing 4. Pick an icon from `lucide-react` and a `bg-*/text-*` tone.

### Add a row to an amount card

1. Compute the value in the controller and add it to the relevant `active_amounts` / `fees_amounts` / `delivery_amounts` block.
2. Add an `<AmountRow label value currency />` inside the matching `<AmountCard>`.

### Add a tile to "All reports"

1. Add the value to `reports` in the controller.
2. Append a row to the `reportTiles` array near the top of the `Index` function. Each row is `{ icon, label, value, unit, tone }`.

### Add another chart series

The line chart accepts arbitrary series. Add the new data array to `series` in the controller, then add a row to the `series=[...]` prop on `<LineChart />` with a color. The legend below the chart is hand-built from the same list — keep them in sync (or extract them into a `const` at the top of the file).

---

## Filter flow

The date filter posts to `merchant-panel.dashboard.filter` (handled by `DashbordController::merchantDashboardFilter`). That endpoint still server-renders the legacy Blade view for now — it isn't part of this port. **If you need filtered date ranges on the new page**, the cleanest move is:

1. Make `merchantDashboardFilter` return `Inertia::render('Merchant/Dashboard/Index', ...)` with the same prop shape, or
2. Inline the filter into `index()` by reading `$request->date` directly (the controller already wires `request_date` through to the page).

Option 2 is one line; option 1 keeps the controller method that powers the legacy POST form available for backwards-compat URLs.

---

## What was retired

- `cdn.tailwindcss.com?plugins=forms` runtime CDN script tag — Tailwind is now bundled via Vite like everything else.
- Two `bootstrap` script tags loading the same library twice — neither is needed by the new page.
- `apexcharts.js` + `dashboard-chart.blade.php` — replaced by the inline SVG components.
- 23 → 3 script tags loaded on the page.

The old Blade view and chart partial are intentionally left on disk so this PR is a contained UI change; they can be deleted in a follow-up once the new page has lived in production for a release.

---

## Onboarding tour hooks

The dashboard is the entry point for the merchant onboarding tour system.
See `TOURS.md` and `database/seeders/tours/README.md` for the full picture.

**Auto-start:** any merchant with `first_login_at IS NULL` sees the welcome
modal on `/dashboard` offering the `merchant.dashboard.welcome` tour (6
steps). The stamp is set by `LoginController::authenticated()` on the
very first successful login so it doesn't fire twice.

**`data-tour` anchors on the page** — steps target these to spotlight
widgets:

| Anchor | Wraps |
|---|---|
| `dashboard-kpis` | The 4-tile KPI row (total / delivered / returned / in-transit) |
| `dashboard-amounts` | The 3-card amount row (active shipments, fees, delivery/COD) |
| `dashboard-charts` | Line + donut chart section |
| `dashboard-reports` | 12-tile grid at the bottom |

If you add a new widget and want it tourable, wrap it (or its container)
in `<div data-tour="dashboard-newthing">…</div>` — the naming convention
is `<page>-<thing>`. See `database/seeders/tours/README.md` §"Adding new
anchors" for the full recipe.

**Take-a-tour launcher** — the topbar's "Take a tour" dropdown
(rendered by `TourLauncher` in `MerchantLayout`) lists every applicable
tour for the current user. Tours whose `trigger_route` matches the
current URL bubble to the top of the list.
