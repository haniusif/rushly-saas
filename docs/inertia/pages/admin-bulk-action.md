# `/admin/bulk_action` — bulk action page

`resources/js/Pages/Admin/Parcel/BulkAction.jsx`

Single-form page that runs one of three actions on a list of shipment
IDs: assign to 3PL, change status, or cancel.

## Route + controller

- `GET /admin/bulk_action` → `ParcelBulkActionController::parcel_bulk_action`,
  permission-gated by `parcel_read`
- Submit POSTs to `parcel.bulk_action_apply` →
  `ParcelBulkActionController::apply()` (unchanged from legacy)

## Controller payload

```jsonc
{
  "statuses":    [{ "id": 1, "label": "Pending", "class": "...", "requires": [] }, ...],
  "merchants":   [{ "id", "name" }],
  "deliverymen": [{ "id", "name" }],
  "hubs":        [{ "id", "name" }],
  "companies":   [{ "value": "panda|zajel|aramex|jet|logestechs", "label": "..." }],
  "urls":        { "apply", "index" },
  "t":           { /* 28 labels */ }
}
```

`statuses` is a controller-owned, workflow-ordered list of every status
the server's `apply()` method actually knows how to drive (pickup →
warehouse → delivery → return → cancels). Each status carries a
`requires` array (`['delivery_man_id'|'date'|'hub_id'|'merchant_id']`) —
the page uses it to decide which sub-fields to show.

Defensive paginator handling on `merchant->all() /
deliveryman->all() / hub->all()`: each is wrapped in
`instanceof AbstractPaginator ? items() : value` before mapping.

## Page structure

Four stacked cards in a single `<form>`:

**1. Shipment IDs** — `<Textarea>` with monospace input. A `parseShipmentIds()`
util splits on whitespace/comma/semicolon and dedupes; a live counter pill
shows "N shipments".

**2. Action type** — three large tiles:

| Tile | Color | Value |
|---|---|---|
| Assign to 3PL | violet | `assign_3pl` |
| Change Status | sky | `change_status` |
| Cancel Shipments | rose | `cancel` |

Selecting a tile resets every dependent field. Cancel mode shows an
amber hint banner inline.

**3. Action-specific fields** — conditional cards:

- **Change Status path**:
  - Status `<Select>` (populated from `statuses`)
  - When picked, shows whichever fields the status's `requires` array
    asks for: driver / hub / merchant / date
  - Note textarea (optional)
- **Assign to 3PL path**:
  - Company `<Select>` (Panda / Zajel / Aramex / J&T / Logestechs)
  - When `company === 'logestechs'`, a violet inset panel reveals three
    required inputs: Logestechs Company ID, Email, Password
- **Cancel path**: no extra fields

**4. Apply bar** — Back link · Clear button · Apply Bulk Action

The submit button is disabled until `canSubmit` is true. The derivation
enforces:

- `ids.length > 0`
- An action type is selected
- For `change_status`: a status is picked AND every `requires` field is
  filled
- For `assign_3pl`: a company is picked; if `logestechs`, the three
  credential inputs must all be filled

## Submit

```js
form.post(urls.apply, { preserveScroll: true });
```

No multipart payload needed. The server's `apply()` parses
`shipment_ids` server-side using the same split logic.

## Deferred

- "Check" button from the legacy that pre-validated IDs against the
  server (resolved them, totaled them) is not ported. To add: build a
  small endpoint like `parcel/bulk-action/check`, render results above
  the Apply bar.
- Toastr success/error messages from the server still fire on redirect
  but aren't surfaced as in-page banners yet — surface them via the
  shared `flash` prop on `HandleInertiaRequests`.
- "Received by hub multiple" and "Assign return to merchant" actions
  hand the user a notice to use the legacy modal (those flows take
  multi-field forms the legacy modal collects).
