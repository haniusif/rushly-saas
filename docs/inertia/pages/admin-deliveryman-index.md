# `/admin/deliveryman` — courier list

`resources/js/Pages/Admin/Deliveryman/Index.jsx`

The courier index. Replaces `resources/views/backend/deliveryman/index.blade.php`.

## Routes

- `GET /admin/deliveryman`         → `DeliveryManController::index`
- `GET /admin/deliveryman/filter`  → `DeliveryManController::filter`

Both go through a single private `renderIndex($paginator, $request)` that
calls `Inertia::render('Admin/Deliveryman/Index', ...)`.

## Controller

```php
$paginator = $this->repo->all();   // or ::filter($request) for filter()
```

The paginator items are flattened into a `rows` array (one shape, no
nested guards in the React side):

```jsonc
{
  "id": 1,
  "unique_id": "DM-001",
  "name": "...",
  "email": "...",
  "image": "...",          // user.image full URL
  "hub_name": "...",       // user.hub.name
  "delivery_charge": 0,
  "pickup_charge":   0,
  "return_charge":   0,
  "current_balance": 0,
  "opening_balance": 0,
  "status": 1,             // user.status (1/2/3/4)
  "edit_url": "/admin/deliveryman/edit/1"
}
```

Plus a separate `pagination` block (current/last page, from/to/total,
prev/next URLs).

Other props: `filters` (echo-back of name/email/phone), `permissions`
(create/update/delete), `currency`, `urls` (filter, index, create), and a
flat `t` map.

## Filter bar

Three text fields: name, email, phone. Submits via
`router.get(urls.filter, draft, { preserveState: true, replace: true })`
so the back button doesn't grow a history entry per filter change.

"Clear" resets local state and `router.get(urls.index)` without
preserveState so a fresh request fires.

## Table columns

| # | Column | Source |
|---|---|---|
| 1 | `#` | (page row index) |
| 2 | Unique ID | `r.unique_id` (font-mono) |
| 3 | User | avatar (image or initials) + name + email |
| 4 | Hub | `r.hub_name` |
| 5 | Delivery charge | tabular money |
| 6 | Pickup charge | tabular money |
| 7 | Return charge | tabular money |
| 8 | Current balance | (bold) tabular money |
| 9 | Opening balance | tabular money |
| 10 | Status | colored pill (active / suspended / on leave / ended) |
| 11 | Actions | dropdown — Edit only (delete was commented out in the legacy blade and not ported) |

## Pagination

Prev / Next buttons disabled when their URL is null. Page counter
(`current / last`) sits between them. Showing summary repeats above the
table and below.

## Notes

- The legacy blade's `<p>Showing X to Y of Z results</p>` is preserved
  via a `:from`/`:to`/`:total` template in `t.showing_results`.
- No bulk selection (the legacy had none either).
- No CSV export — there's no `deliveryman.export` route on the server.
