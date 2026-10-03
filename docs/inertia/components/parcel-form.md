# `ParcelForm`

`resources/js/Components/parcel/ParcelForm.jsx`

Shared form body used by `Pages/Admin/Parcel/Create.jsx` and
`Pages/Admin/Parcel/Edit.jsx`. Renders 5 grouped section cards in the left
column and a sticky live charge summary in the right column.

## Why it's a shared component

The blade had `create.blade.php` (554 lines) and `edit.blade.php` (615
lines) that were ~90% identical. Same approach in React would have
duplicated several hundred lines of JSX. The parent owns its own
`useForm()` instance (so create vs. edit have independent defaults +
submit method), and just hands the form down.

## Props

| Prop | Shape | Notes |
|---|---|---|
| `form` | `useForm()` instance | Required. Parent constructs it with whatever defaults make sense for create vs. edit. |
| `mode` | `'create' \| 'edit'` | Toggles merchant select lock + submit button label |
| `lookups` | `{ merchants, cities, categories, packagings, delivery_types }` | All passed through from the controller |
| `settings` | `{ currency, vat_tax, fragile_liquid_charge }` | From the controller |
| `urls` | `{ store/update, cancel, merchant_shops, merchant_cod }` | Endpoints |
| `t` | `Record<string,string>` | Translation map |
| `initialShops` | `Array<{ id, name }>` | Pre-populated shops in edit mode |
| `onSubmit` | `(e: Event) => void` | Submit handler the parent provides |

## Sections

| # | Title | Fields |
|---|---|---|
| 1 | Pickup | merchant_id (req), shop_id, pickup_phone, pickup_address |
| 2 | Amounts | cash_collection (req), selling_price, invoice_no |
| 3 | Item | category_id (req), weight, delivery_type_id (req) |
| 4 | Customer | customer_name (req), customer_phone (req), city_id (req), area_id, customer_address (req), note |
| 5 | Options | packaging_id, priority_id (Normal/High), fragileLiquid checkbox |

## Dependent dropdowns

- **Merchant → Shop**. On merchant change, the component POSTs to
  `urls.merchant_shops` with a CSRF token and replaces local `shops` state.
  When `mode === 'edit'` the merchant select is disabled (changing
  merchants would break finance ledgers).
- **City → Area**. The `cities` prop carries nested `areas`, so the area
  list is derived locally — no AJAX call. If the city changes and the
  current `area_id` isn't valid for the new city, it's cleared.

## Merchant auto-fill

When a merchant is picked, the form auto-fills `pickup_phone` and
`pickup_address` (if empty) from `merchant.pickup_phone` /
`merchant.pickup_address`, and sets `vat_tex` to the merchant's VAT %.
Edit mode skips this since the values came in pre-filled.

## Charge math (client-side)

The right column shows a live charge breakdown:

```
COD charge       = cash_collection * merchant.cod_charges.inside_city / 100
Liquid charge    = fragileLiquid ? settings.fragile_liquid_charge : 0
Packaging charge = selected packaging.price
Delivery charge  = 0   ← computed server-side (depends on merchant tier + city zone)
Total charge     = COD + liquid + packaging + delivery
VAT              = total * (form.vat_tex / 100)
Net payable      = cash_collection - total - VAT
Current payable  = net payable
```

These get serialised to JSON and stored in `form.data.chargeDetails`
(hidden field) — the legacy server-side wallet-check at
`ParcelController::store()` reads this same JSON shape, so it's not
purely cosmetic.

## Deferred / known limits

- Delivery charge is always shown as `0.00` until save. The legacy form
  calls `parcel.deliveryCharge.get` on each change; that wasn't ported.
  If the merchant has a low wallet balance, the server-side wallet check
  may fire on submit because `totalDeliveryChargeAmount` is missing the
  actual delivery component.
- Google Maps pickup-address autocomplete + lat/long capture (used
  `map-current.js` + a Maps API key on the blade side) is not ported.
  `pickup_lat` / `pickup_long` are not collected.
- The fulfillment-merchant product picker (`partials/product_picker.blade`)
  is not ported. Few merchants use it.

## Submit flow

The parent's `onSubmit` is called with the form event. It typically:

- `form.post(urls.store, { forceFormData: true, preserveScroll: true })`
  for create
- `form.post(urls.update, { forceFormData: true, preserveScroll: true })`
  for edit (with `_method: 'put'` already in `form.data` to spoof PUT for
  the multipart payload — Laravel reads it)

Server errors come back through `form.errors[fieldName]` and render
inline as a small destructive-coloured note under each `<Field>`.
