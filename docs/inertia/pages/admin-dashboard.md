# `/dashboard` — admin variant

`resources/js/Pages/Admin/Dashboard/Index.jsx`

The dashboard URL `GET /dashboard` is served by `DashbordController::index`
and branches by user type:

- `UserType::SUPER_ADMIN` → `backend.super-admin.dashboard` blade (unchanged)
- `UserType::MERCHANT`    → `backend.merchant_panel.dashboard` blade (unchanged)
- else (admin, hub, etc.) → `Inertia::render('Admin/Dashboard/Index', ...)`

Only the admin/hub branch is Inertia.

## Controller

`app/Http/Controllers/DashbordController.php`

The admin branch builds the same totals as before but shapes them for the
React side. Two helpers were added to defensively reshape what the repo
returns:

```php
private function asSeries($v): array
private function asScalar($v): float
```

The original assumption was that the repo's `income()` / `expense()` /
`merchantIncome()` etc. returned arrays of daily values. They don't — most
return a scalar sum. `asSeries` returns `[]` for scalars (sparkline shows
empty) and `asScalar` folds whatever shape comes back into a single float
(used by the headline numbers).

## Props

```jsonc
{
  "currency": "SAR",
  "kpis": { "parcels": 0, "users": 0, "merchants": 0, "deliverymen": 0, "hubs": 0, "accounts": 0 },
  "pipeline": { "assigned": 0, "partial_delivered": 0, "delivered": 0 },
  "ledgers": [
    { "key": "courier",     "label": "Courier",     "income": 0, "expense": 0 },
    { "key": "deliveryman", "label": "Couriers",    "income": 0, "expense": 0 },
    { "key": "merchant",    "label": "Merchants",   "income": 0, "expense": 0 },
    { "key": "vat",         "label": "VAT",         "income": 0, "expense": 0 },
    { "key": "bank",        "label": "Bank",        "income": 0, "expense": 0 },
    { "key": "hub",         "label": "Hubs",        "income": 0, "expense": 0 }
  ],
  "hub_parcels": [{ "id": 1, "name": "...", "parcels_count": 0 }],
  "recent_parcels": [{ "id": 1, "tracking_id": "...", "merchant_name": "...", "status": 1, "cash_collection": 0, "created_at": "..." }],
  "series": {
    "dates": ["2026-06-13", "..."],
    "income": [], "expense": [],
    "merchantIncome": [], "merchantExpense": [],
    "deliverymanIncome": [], "deliverymanExpense": []
  },
  "totals": {
    "income": 0, "expense": 0,
    "merchantIncome": 0, "merchantExpense": 0,
    "deliverymanIncome": 0, "deliverymanExpense": 0
  },
  "t": { "...": "..." }
}
```

## UI layout

- **KPI row** — 6 cards (Parcels, Users, Merchants, Couriers, Hubs,
  Accounts), color-coded icon chips
- **Left column** (2/3):
  - **Parcel pipeline** — single segmented bar (assigned / partial /
    delivered) + 3 stats below with corresponding icons
  - **Ledger summary** — 6 rows, each showing income / expense / net
    with green / rose / signed-net tabular values
  - **Recent parcels** — table (tracking, merchant, status pill, cash,
    created date)
- **Right column** (1/3):
  - 3 chart cards — Income vs Expense, Merchant revenue, Courier
    revenue. Each renders scalar totals as chips and an SVG sparkline
    when a series is available. Falls back to "no data" only when both
    totals are zero AND there's no series.
  - **Top hubs** — horizontal bar list (parcels-count progress vs. the
    max in the set)

## Notes

- The 7-day "search charts" filter (`searchCharts()` controller method)
  isn't wired to a UI yet. The dashboard renders whatever the initial
  request gave it.
- KPIs show 0 if `data['total_*']` is missing. That shouldn't happen
  under normal circumstances since the controller always builds them.
