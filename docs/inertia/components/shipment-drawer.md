# `ShipmentDrawer`

`resources/js/Components/parcel/ShipmentDrawer.jsx`

Right-edge offcanvas drawer that shows full shipment details + tracking
timeline. Used by `Pages/Admin/Parcel/Index.jsx` — clicking a tracking ID
(or "Track shipment" in the row actions) slides it in.

## Backend endpoint

`GET /admin/parcel/tracking-json/{id}` → `ParcelController::trackingJson($id)`
(route name `parcel.tracking_json`), permission-gated by `parcel_read`.

Returns JSON. The legacy HTML endpoint `parcel.tracking_offcanvas`
(`backend.parcel.partials.tracking_offcanvas` blade) is unchanged so the
legacy index keeps working.

### Payload shape

```jsonc
{
  "parcel": {
    "id", "tracking_id", "status", "status_label", "status_color",
    "created_at", "cod_amount", "selling_price", "invoice_no",
    "weight", "weight_unit", "delivery_type", "city", "area", "note",
    "urls": { "edit", "logs", "print", "print_label" }
  },
  "sender":    { "name", "address", "phone", "whatsapp" },
  "recipient": { "name", "address", "phone", "whatsapp" },
  "attachments": [{ "url", "label", "date", "contain" }],
  "events":  [{ "id", "status", "label", "color", "actor", "hub", "note", "created_at" }],
  "creation_event": { "actor", "created_at", "label" },
  "currency": "...",
  "t":       { /* drawer labels */ }
}
```

`whatsapp` is `https://wa.me/<digits>` derived from the phone number.

## Component contract

```jsx
<ShipmentDrawer parcelId={trackingId} onClose={closeTracking} />
```

- `parcelId` — when truthy the drawer is open and fetching; when `null` it
  unmounts via `if (!open) return null`. Calling code holds the id in state.
- `onClose()` — called when the user clicks the backdrop, the X button, or
  presses ESC.

## Behaviour notes

- **Portal**: rendered via `createPortal(..., document.body)` so the
  drawer never gets trapped inside the page's stacking context. Without
  this, the AdminLayout sidebar (`z-50`) could cover it.
- **z-index**: backdrop and panel both `z-[100]` to outrank the sidebar.
- **Scroll lock**: `document.body.style.overflow = 'hidden'` while open.
- **ESC**: `keydown` listener on `document` while open.
- **Hooks order**: `groupedByDate` `useMemo` runs on every render
  (including when closed). The early `return null` comes _after_ all
  hooks — required by React's rules-of-hooks.
- **Animations**: uses `tailwindcss-animate` utilities `animate-in
  fade-in` on the backdrop and `animate-in slide-in-from-right` on the
  panel.

## UI sections

| Section | Notes |
|---|---|
| Header | Tracking ID + current status pill + X close button |
| Action bar | Edit · Webhook logs · Print label · Print with tracking |
| Sender / Recipient cards | name, address, phone + WhatsApp icon |
| Detail card | tracking, booking date, COD, price, invoice, weight, delivery type, city, area, status pill, note |
| Attachments gallery | square thumbnails — `object-cover` or `object-contain` per `attachments[].contain` |
| Timeline | events grouped by date chips; each event shows actor + hub + time, status bubble, status pill; colour-coded dot via `COLOR_DOT()`; creation event pinned at the bottom |

## Out of scope (still falls through to legacy)

- "Change status" dropdown inside the drawer with `parcelStatus()`
  helper output → not ported. Users do status changes via the row's
  actions menu (which lists `allowed_transitions`).
- "Postpone" inline action — not ported.
