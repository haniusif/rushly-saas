# WMS index pages (10 modules)

All 10 admin WMS index pages share:

- The PHP trait
  [`RendersInertiaIndex`](../components/wms-list-page.md#php-apphttpcontrollersbackendwmsconcernsrendersinertiaindexphp)
  in every controller
- The React component
  [`ListPage`](../components/wms-list-page.md#react-resourcesjscomponentswmslistpagejsx)
  for the page chrome

This doc covers all 10 pages back-to-back. See
[components/wms-list-page.md](../components/wms-list-page.md) for the
shared infrastructure.

## Dashboard

- Route: `GET /admin/wms` → `WmsDashboardController::index`,
  permission-gated by `wms_manage`
- Page: `resources/js/Pages/Admin/Wms/Dashboard/Index.jsx`

8 KPI cards (each links to the relevant module list), a 7-day stock
movement bar chart (credit/debit per day), a current fulfillment status
breakdown bar chart, and 3 alert panels:

- **Low stock alerts** — top 8 products with on-hand ≤ reorder point;
  each row links to product show
- **Expiring soon** — top 8 stock rows with expiry within 7 days
- **SLA breached** — top 8 fulfillments past their SLA deadline

The page doesn't use `ListPage` (no table / no filter / no pagination)
— it's a custom layout with `KpiCard`, `MovementChart`, `FulChart`, and
`AlertPanel` helpers defined inline.

## Products

- Route: `GET /admin/wms/products` (`wms.products.index`) → `WmsProductController::index`
- Page: `resources/js/Pages/Admin/Wms/Products/Index.jsx`

**Filter**: search (name / SKU / barcode), merchant, hub
**Header extras**: Add CTA only
**Table**: SKU, Name, Merchant, Hub, Barcode (mono), On hand vs reorder
point, LOW/OK pill, View link + Barcode print link (opens
`wms.products.barcode` in a new tab)

Per-row `low` flag is computed server-side: `reorder_point > 0 &&
on_hand <= reorder_point`.

## Stock

- Route: `GET /admin/wms/stock` (`wms.stock.index`) → `WmsStockController::index`
- Page: `resources/js/Pages/Admin/Wms/Stock/Index.jsx`

**Filter**: search (SKU / product name), merchant, hub, "low only"
checkbox
**Header extras**: Export CSV link → `wms.stock.export`
**Table**: SKU, Product (with merchant sub-line), Location (mono), Qty,
Reserved, Available (with LOW pill when `r.low`), Batch, Expiry

`low_only` filter is applied client-side after the controller returns
(`rows.filter(r => !filters.low_only || r.low)`) to avoid a complex
subquery on aggregated columns. Low rows get an amber background tint.

## Locations

- Route: `GET /admin/wms/locations` (`wms.locations.index`) →
  `WmsLocationController::index`
- Page: `resources/js/Pages/Admin/Wms/Locations/Index.jsx`

**Filter**: hub, zone (text), aisle (text), type (from enum options)
**Header extras**: Map view button → `wms.locations.map`
**Table**: Code (mono), Hub, Zone, Aisle, Rack, Shelf, Bin, Type pill,
Capacity, Edit link (only the edit action is exposed — legacy didn't
show a delete here either)

## GRN (Receiving)

- Route: `GET /admin/wms/grn` (`wms.grn.index`) → `WmsGrnController::index`
- Page: `resources/js/Pages/Admin/Wms/Grn/Index.jsx`

**Filter**: status, merchant, hub
**Header extras**: Add CTA only
**Table**: GRN # (mono), Merchant, Hub, Received by, Items count,
Status pill (`draft` grey / `in_progress` sky / `completed` emerald /
`discrepancy` rose), Created (relative), View link

## Fulfillment

- Route: `GET /admin/wms/fulfillment` (`wms.fulfillment.index`) →
  `WmsFulfillmentController::index`
- Page: `resources/js/Pages/Admin/Wms/Fulfillment/Index.jsx`

**Stats**: 6 cards on top — pending, picking, packing, ready,
dispatched_today, sla_breached (rose)
**Filter**: status, hub, "SLA breached" checkbox
**Table**: Fulfillment # (mono), Parcel tracking, Merchant, Hub,
Picker, Status pill, SLA cell (shows "Overdue" with alert icon if
`sla_overdue`, otherwise relative-time deadline), Open link

`sla_overdue` is computed server-side as `sla_deadline?->isPast() &&
!in_array(status, [DISPATCHED, CANCELLED])`. Overdue rows get a rose
background tint.

## Outbound

- Route: `GET /admin/wms/outbound` (`wms.outbound.index`) → `WmsOutboundController::index`
- Page: `resources/js/Pages/Admin/Wms/Outbound/Index.jsx`

**Filter**: type, status, merchant
**Header extras**: Add CTA only
**Table**: Outbound # (mono), Type pill
(`fulfillment / manual / transfer / return_to_merchant`), Merchant,
Hub, Processed by, Status pill, Completed (relative), Open link

## Adjustments

- Route: `GET /admin/wms/adjustments` (`wms.adjustments.index`) →
  `WmsAdjustmentController::index`
- Page: `resources/js/Pages/Admin/Wms/Adjustments/Index.jsx`

**Filter**: reason (from enum options), approval status
**Header extras**: Add CTA only
**Table**: SKU, Product, Location (mono), Before, **Change** (color-coded
+/- with explicit sign), After, Reason pill, Approval pill
(`approved` emerald / `pending_approval` amber / `rejected` rose), By,
When (relative), View link

## Cycle counts

- Route: `GET /admin/wms/cycle-counts` (`wms.cycle-counts.index`) →
  `WmsCycleCountController::index`
- Page: `resources/js/Pages/Admin/Wms/CycleCounts/Index.jsx`

**Filter**: none (legacy didn't have one either)
**Header extras**: Add CTA only
**Table**: Count # (mono), Hub, Scope (with optional zone suffix),
Assigned, Status pill (`open / in_progress / completed`), Started
(relative), Completed (relative), Open link

## Damage

- Route: `GET /admin/wms/damage` (`wms.damage.index`) → `WmsDamageController::index`
- Page: `resources/js/Pages/Admin/Wms/Damage/Index.jsx`

**Filter**: none rendered on the page (controller accepts `cause` and
`action_taken` query params but the legacy blade dropped the form)
**Header extras**: Add CTA only
**Table**: SKU, Product, Location (mono), Qty, Cause pill (rose),
Action pill (sky) or `—`, Reported by, When (relative), View link

## Cross-cutting notes

- Every controller's `index()` is now `Inertia::render`. The legacy
  blades under `resources/views/backend/wms/*` are no longer reached on
  the index routes (they're still loaded for the `create` / `show` /
  `edit` actions and the `map` view).
- Every controller's `merchant->all() / hub->all() / deliveryman->all()`
  goes through the trait's `lookupRows()` to defensively detect a
  paginator and iterate `->items()`. The "Attempt to read property `id`
  on int" bug from the parcel create page can't recur in WMS.
- Pagination meta is built via the trait's `paginateMeta($p)` so the
  React side gets the same shape everywhere.
- Permissions surface on each row as `permissions.create` only (the
  legacy index views never showed inline edit / delete on most of these
  modules — those live on the show page).

## Deferred (Phase B)

For every module, the following endpoints still hit legacy blades:

- `create` form (the `Route::resource` controllers don't have a custom
  Inertia create view yet)
- `show` detail
- `edit` form (where applicable)
- module-specific actions (e.g. `wms.adjustments.approve` /
  `wms.fulfillment.pick` / `wms.outbound.complete` / `wms.grn.complete`)

Ship next when ready.
