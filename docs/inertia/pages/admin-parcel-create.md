# `/admin/parcel/create` — create a parcel

`resources/js/Pages/Admin/Parcel/Create.jsx`

Thin wrapper around the shared `ParcelForm` component (see
[components/parcel-form.md](../components/parcel-form.md)) — sets up the
form's defaults and submit method, then hands off rendering.

## Route

`GET /admin/parcel/create` → `ParcelController::create`,
permission-gated by `parcel_create`.

## Controller

`ParcelController::create()` returns:

```php
Inertia::render('Admin/Parcel/Create', [
    'merchants'      => /* Eloquent collection — id, name, vat, cod_charges, pickup_phone, pickup_address */,
    'cities'         => /* with .areas eagerloaded */,
    'categories'     => ...,
    'packagings'     => ...,   // includes price
    'delivery_types' => ...,
    'settings'       => [ 'currency', 'vat_tax', 'fragile_liquid_charge' ],
    'permissions'    => [ 'create_product_pick' ],
    'urls'           => [ 'store', 'cancel', 'merchant_shops', 'merchant_cod' ],
    't'              => $this->parcelCreateLabels(),
]);
```

### Merchant query gotcha

Don't use `$this->merchant->all()` — that returns a `LengthAwarePaginator`
and breaks `collect()->map()`. Use a direct query:

```php
\App\Models\Backend\Merchant::companywise()
    ->where('status', 1)
    ->with('user')
    ->orderBy('business_name')
    ->get();
```

## Form defaults

```js
useForm({
    merchant_id: '', shop_id: '', pickup_phone: '', pickup_address: '',
    cash_collection: '', selling_price: '', invoice_no: '',
    category_id: '', weight: '', delivery_type_id: '',
    customer_name: '', customer_phone: '',
    city_id: '', area_id: '', customer_address: '',
    note: '', packaging_id: '', priority_id: '2', fragileLiquid: false,
    cod_charge: 0, vat_tex: settings.vat_tax || 0, chargeDetails: '',
});
```

## Submit

```js
form.post(urls.store, { forceFormData: true, preserveScroll: true });
```

`forceFormData: true` because the legacy `ParcelController::store()`
expects a multipart payload (the form has the `enctype="multipart/form-data"`).

Server-side validation: `App\Http\Requests\Parcel\StoreRequest`. Required
fields: `merchant_id`, `category_id`, `delivery_type_id`,
`customer_name`, `customer_address`, `customer_phone`, `city_id`. Errors
come back through `form.errors` and surface inline under each field.

After successful save, the controller redirects to `parcel.index`
(Toastr success message is suppressed in the Inertia response — see
"Open follow-up" below).

## Live charge math

See [components/parcel-form.md](../components/parcel-form.md). The
charge summary updates as the user changes any input; `chargeDetails` is
kept in sync as a JSON string for the server-side wallet check.

## Open follow-up

- Toastr success/error messages don't render via Inertia — would need
  to be moved into the shared `flash` prop on `HandleInertiaRequests` and
  shown as a banner on the next page render.
- Live `parcel.deliveryCharge.get` fetch on city/area/delivery-type
  change isn't implemented; delivery charge stays at `0.00` until save.
- Google Maps autocomplete for pickup address isn't ported.
