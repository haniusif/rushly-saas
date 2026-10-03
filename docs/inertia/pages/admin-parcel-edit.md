# `/admin/parcel/edit/{id}` — edit a parcel

`resources/js/Pages/Admin/Parcel/Edit.jsx`

Wrapper around the shared `ParcelForm` component (see
[components/parcel-form.md](../components/parcel-form.md)), in edit mode.

## Route

`GET /admin/parcel/edit/{id}` → `ParcelController::edit`,
permission-gated by `parcel_update`.

## Controller

`ParcelController::edit($id)` returns:

```php
Inertia::render('Admin/Parcel/Edit', [
    'parcel'         => /* flat object with every field the form binds to */,
    'initial_shops'  => /* the merchant's existing shops; passed to ParcelForm.initialShops */,
    'merchants'      => /* same shape as create */,
    'cities'         => /* with .areas eagerloaded */,
    'categories'     => ...,
    'packagings'     => ...,
    'delivery_types' => ...,
    'settings'       => [...],
    'urls'           => [ 'update', 'cancel', 'merchant_shops', 'merchant_cod' ],
    't'              => array_merge($this->parcelCreateLabels(), [
        'edit'   => __('parcel.edit_parcel') ?: 'Edit parcel',
        'update' => __('levels.update') ?: 'Update',
    ]),
]);
```

`parcelCreateLabels()` is reused so the form labels match exactly.

### `parcel` shape

Flat. Every field the React form binds to is present:

```jsonc
{
  "id": 1, "tracking_id": "...",
  "merchant_id": 12, "shop_id": null, "pickup_phone": "...", "pickup_address": "...",
  "cash_collection": 0, "selling_price": 0, "invoice_no": "...",
  "category_id": 1, "weight": "...", "delivery_type_id": 1,
  "customer_name": "...", "customer_phone": "...",
  "city_id": 5, "area_id": 12, "customer_address": "...",
  "note": "...", "packaging_id": 2, "priority_type_id": 2,
  "liquid_fragile_amount": 0,
  "cod_charge": 0, "vat": 0
}
```

Numeric IDs are coerced to strings on the React side so the
`<Select value=...>` matches the `<option value=...>` (an int won't
match a string-valued option, leaving the dropdown unselected).

`priority_id` on the form maps to `parcel.priority_type_id` server-side.
`fragileLiquid` on the form is `!!parcel.liquid_fragile_amount`.

## Submit

```js
form.post(urls.update, { forceFormData: true, preserveScroll: true });
```

The form data includes `_method: 'put'` (set when the form is
initialised) so Laravel reads it as a PUT despite the request being
POST. `App\Http\Requests\Parcel\UpdateRequest` validates — same required
fields as create except `city_id` is **not** required on update (kept
the rules in sync with the legacy `UpdateRequest`).

## Form behaviour vs. create

- **Merchant select is disabled**. Changing merchants on an existing
  parcel would corrupt finance ledgers (CourierStatement /
  DeliverymanStatement / MerchantStatement entries are tied to the
  merchant at creation).
- **Shops list pre-populated** via `initial_shops` prop — no AJAX fetch
  on first render. Auto-fetch still kicks in if a user (somehow)
  re-selects a different merchant.
- **Submit button label** says "Update" (or whatever
  `t.update || t.save` resolves to).

## Title

The page title and breadcrumb include the parcel's tracking ID:

```
Edit · TRACKING-12345
```

So the browser tab shows what's being edited even when the user has
multiple edit windows open.
