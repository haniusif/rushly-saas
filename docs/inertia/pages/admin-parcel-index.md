# `/admin/parcel/index` — parcels list

`resources/js/Pages/Admin/Parcel/Index.jsx`

The parcels table — the highest-traffic admin screen. Replaces
`resources/views/backend/parcel/index.blade.php` (921 lines, many modal
includes).

## Routes

- `GET /admin/parcel/index`  → `ParcelController::index`
- `GET /admin/parcel/filter` → `ParcelController::filter`

Both funnel through `renderParcelIndex($paginator, $request, $paginate)`
which returns `Inertia::render('Admin/Parcel/Index', ...)`.

## Controller payload

Per row (in `rows`):

```jsonc
{
  "id": 1,
  "tracking_id": "...", "code": "...",
  "customer_name": "...", "customer_phone": "...", "customer_address": "...",
  "city": "...", "area": "...",
  "merchant_name": "...", "merchant_mobile": "...", "merchant_address": "...",
  "cash_collection": 0,
  "total_delivery_amount": 0, "vat_amount": 0, "current_payable": 0,
  "status": 9, "status_label": "Delivered", "status_color": "green",
  "partial_delivered": false, "partial_delivered_label": "Partial",
  "priority": 1,             // 1 = high, 2 = normal
  "attempts": 0,
  "invoice": { "id": "INV-1", "status": 2, "status_label": "Paid", "paid_at": "2026-..." } | null,
  "courier_name": "..." | null,       // last 3PL company
  "assigned_deliveryman": "..." | null, // lastParcelEvent.deliveryMan.user.name
  "created_at": "2026-...", "updated_at": "2026-... ...",
  "allowed_transitions": [{ "value": 2, "label": "...", "color": "..." }, ...],
  "urls": {
    "view": "...", "logs": "...", "clone": "...", "print": "...",
    "print_label": "...", "edit": "...", "delete": "...",
    "delivered_info": "..." | null
  }
}
```

Plus:

- `pagination` (current/last/from/to/total, prev/next URLs, per_page)
- `filters` (echo back of all 8 filter inputs)
- `lookups` — `statuses` (built via `ReflectionClass(ParcelStatus)::getConstants()`
  + `ParcelStatusHelper::label()` per value), `merchants` (id + business_name),
  `deliverymen` (id + user.name), `hubs` (id + name)
- `permissions` — `create`, `update`, `delete`, `status_update`, `finance_update`
- `currency`
- `urls` — index, filter, create, specific_search, multiple_print_label,
  parcel_map, export, import, priority_status, bulk_pickup_assign,
  bulk_transfer_to_hub, bulk_deliveryman_assign
- `t` — flat translation map

### `allowedTransitions(int $statusId): array`

Mirrors the rules from the global `parcelStatus()` helper at
`app/Http/Helper/Helper.php`. Returns `[{ value, label, color }, ...]` for
each valid next status. The React side renders these as a dropdown; each
item links to the parcel details page with `#change-status-{n}` so the
existing legacy modals (defined in the master blade partial includes)
still drive the actual transition.

## UI layout

### Filter bar (Card)

8 inputs in a 12-column grid:

| Field | Type |
|---|---|
| Date range | text (`YYYY-MM-DD to YYYY-MM-DD`) |
| Status | select (statuses lookup) |
| Merchant | select (merchants lookup) |
| Delivery courier | select (deliverymen lookup) |
| Pickup courier | select (deliverymen lookup) |
| Invoice / AWB | text |
| 3PL (`has_3pl`) | select (`panda`) |
| — | Clear + Filter buttons |

Submits via `router.get(urls.filter, draft, ...)` with `preserveState`
and `replace`. "Clear" resets local state + `router.get(urls.index)`.

### Specific search bar (Card)

Single-input search box that submits to `urls.specific_search` (the
`parcel.specific.search` GET endpoint).

### Header strip

Left: showing summary + selection counter. Right:

- **Print labels (bulk)** — only when `selected.length > 0`. Builds a
  hidden form with `parcels[][]=ID` for each selected row and submits it
  via `target="_blank"` to `urls.multiple_print_label`.
- **Parcel Map** — opens `urls.parcel_map` in a new tab
- **Export N Shipments** — `urls.export` with `:TOTAL` substituted
- **Import parcel** — `urls.import`
- **Per page** select — pushes `?per_page=N` (10/20/50/100/500/1000)
- **Add** CTA — gated on `permissions.create`

### Bulk action panel (Card, permission-gated `status_update`)

`<Select>` for the action type, plus conditional inputs:

| Action | Inputs |
|---|---|
| assignpickupbulk | deliveryman + date |
| transfer_to_hub_multiple_parcel | hub |
| received_by_hub_multiple_parcel | (deferred — falls through to legacy modal) |
| delivery_man_assign_multiple_parcel | deliveryman |
| assign_return_merchant | (deferred — falls through to legacy modal) |

`Apply` button posts via `router.post` to the matching endpoint. The
selected parcel IDs ship as `parcels: [{ id: id }]` because that's the
shape the controllers expect.

### Table (14 columns)

| # | Column | Notes |
|---|---|---|
| 1 | ☑ | select-all + per-row checkboxes |
| 2 | Actions | dropdown — view, logs, clone, print, print-label, edit, delete (edit + delete hidden for `status` 9/10) |
| 3 | Tracking | `<TrackingCell>` — tracking ID is a button that opens the `ShipmentDrawer`; small "Copy" link sits underneath; AWB sub-line; 3PL marker |
| 4 | Label | direct print-label icon |
| 5 | Recipient | name + phone + city/area + address with icons |
| 6 | Merchant | name + mobile + address |
| 7 | Amount | COD always; Total / VAT / Current Payable when `finance_update` |
| 8 | Priority | `<PriorityToggle>` (POSTs to `urls.priority_status`) |
| 9 | Status | colored pill + partial-delivered badge + "Updated on" timestamp |
| 10 | Status update | dropdown of `allowed_transitions` — gated on `status_update` permission |
| 11 | Invoice | status label + invoice ID + paid_at date |
| 12 | Attempts | `r.attempts` |
| 13 | POD | "View" button when `status === 9` (linking to `parcel.deliveredInfo`) |
| 14 | Courier / 3PL | 3PL company name (rose) + assigned deliveryman badge (sky) |

### `<PriorityToggle>`

Small switch. On click: optimistic `setOn(next)`, then `router.post(url,
{ id, value: 1|2 })`. On error, snaps back.

### `<StatusPill>` / `COLOR_TO_CLASSES`

Maps `ParcelStatusHelper::color()` strings (`grey/yellow/orange/blue/
purple/green/red/teal`) to Tailwind colour classes.

### Pagination

Same prev/next style as the deliveryman list.

## Tracking drawer

Clicking a tracking ID, OR the "Track shipment" item at the top of the
row actions dropdown, opens [`ShipmentDrawer`](../components/shipment-drawer.md).
The drawer is mounted once at the bottom of the page; the page holds the
open parcel id in `trackingId` state. See the drawer doc for the full
JSON endpoint and UI breakdown.

## Deferred / open

These existed in the legacy blade but weren't ported. Adding them is a
larger task that needs explicit signoff:

- Inline COD editing (the legacy code is commented out anyway)
- Bulk-action modals for "Received by hub multiple" and "Assign return to
  merchant" (need multi-field forms)
- Status transitions still hit the legacy modals via the details-page
  link rather than being recreated in React — each status modal is a
  full form (assign date, photos, signature, etc.).

## Common bugs to avoid

- `MerchantRepository::all()` returns a `LengthAwarePaginator`. If you
  reach for it for the merchants lookup, `collect()->map(fn ($m) => ...)`
  will iterate `toArray()` keys instead of the items and `$m->id` will
  blow up with "Attempt to read property `id` on int". Use a direct query
  with `->get()` instead.
- The `merchants` table has no `title` column despite the model's
  fillable mentioning it. Stick to `business_name`.
