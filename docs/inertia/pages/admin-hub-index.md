# `/admin/hubs` — hubs list

`resources/js/Pages/Admin/Hub/Index.jsx`

Basic list of hubs with a 2-field filter, a small actions dropdown per
row, and pagination.

## Routes

- `GET /admin/hubs`        → `HubController::index`
- `GET /admin/hubs/filter` → `HubController::filter`
- Both funnel through `renderHubIndex($paginator, $request)` returning
  `Inertia::render('Admin/Hub/Index', ...)`

Sibling routes (`hubs.create`, `hubs.edit/{id}`, `hub.view/{id}`,
`hub-incharge.index/{id}`, `hub.delete/{id}`) still hit legacy blades.

## Controller props

- `rows`        — id, name, phone, address, status (int) + per-row `urls`
  (view, edit, incharge, delete) with `Route::has(...)` guards so a
  tenant without the legacy `hub.view` / `hub-incharge.index` routes
  doesn't blow up
- `pagination`  — standard meta object
- `filters`     — `name`, `phone`
- `permissions` — `create`, `update`, `delete`, `view`, `incharge_read`
- `urls`        — `index`, `filter`, `create`
- `t`           — labels (`title`, `list`, status pill labels, etc.)

## UI

**Filter bar** — `name` + `phone` text inputs. Submits via
`router.get(urls.filter, draft, { preserveState, replace })`. Clear
resets to `urls.index`.

**Header strip** — showing summary + Add CTA (gated).

**Table** — 6 columns: #, Name, Phone (with phone icon), Address (with
map-pin icon), Status pill (`Active` emerald / `Inactive` rose), Actions
dropdown.

Actions dropdown items, all permission-gated:

- View (gated on `hub_view`, only if `urls.view` is set)
- Edit (gated on `hub_update`)
- Hub incharge (gated on `hub_incharge_read`, only if `urls.incharge` is
  set)
- Delete (gated on `hub_delete`) — fires `router.delete(r.urls.delete)`
  after `window.confirm(t.delete_confirm)`

## Deferred

- Create / Edit forms still hit legacy `backend.hub.create` /
  `backend.hub.edit` blades.
