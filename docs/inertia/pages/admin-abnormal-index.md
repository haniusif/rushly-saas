# `/admin/abnormal` — abnormal shipments list

`resources/js/Pages/Admin/Abnormal/Index.jsx`

Shipments that have been stalled past the configured threshold. Index
page mirrors the layout of NDR — stat row + filter + table.

## Routes

- `GET /admin/abnormal` → `AbnormalShipmentController::index`,
  permission-gated by `abnormal_manage`
- Sibling endpoints (`/settings`, `/{id}`, `/{id}/assign`,
  `/{id}/action`, `/{id}/resolve`) still hit legacy blades.

## Controller

```php
return Inertia::render('Admin/Abnormal/Index', [
    'rows', 'pagination', 'filters', 'summary',
    'threshold',                   // detection threshold in days
    'lookups',                     // min_days, severities, statuses, deliverymen
    'urls' => [ 'index', 'settings' ],
    't',
]);
```

`summary` carries 4 counts: `stalled_3`, `stalled_5`, `stalled_7`,
`closed_lost` — exactly the values the legacy controller computed via
4× `(clone $base)->where(...)->count()`.

Defensive paginator handling on `deliveryman->all()`.

## UI

**Header** — single ⚙ Settings button linking to `abnormal.settings`
(legacy page).

**Summary cards** — 4 gradient cards (amber / rose / deep rose / slate),
matching the legacy `.sc-warn / .sc-dang / .sc-crit / .sc-lost`
gradients.

**Filter bar** — 4 fields + the threshold footnote:

- Duration (`3+ / 5+ / 7+ days`)
- Severity (`warning / danger / critical`)
- Status (`open / investigating / resolved / closed_lost`)
- Investigator (deliverymen lookup, value is `user_id` like legacy)
- "Detection threshold: N days" small note (from
  `repo->getThresholdDays()`)

**Table** — 8 columns: ID, Tracking (mono), Customer name, Last event
(relative), Stale days (centered, tabular-nums), Severity badge
(`critical` uses a deliberately dark slate-amber palette), Status pill,
Open button.

## Deferred

- Show / assign / takeAction / resolve / settings endpoints still hit
  legacy blades.
