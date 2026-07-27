# `/admin/ndr` — NDR list

`resources/js/Pages/Admin/Ndr/Index.jsx`

Non-Delivery Report list, with stat row + filter + table.

## Routes

- `GET /admin/ndr` → `NdrController::index` — permission-gated by `ndr_manage`
- Sibling endpoints (`/admin/ndr/export`, `/{id}`, `/{id}/action`,
  `/{id}/resolve`, `/create/{parcel}`) still hit legacy blades.

## Controller

`NdrController::index()`:

```php
return Inertia::render('Admin/Ndr/Index', [
    'rows'        => /* flattened paginator items */,
    'pagination'  => /* current/last/from/to/total + prev/next URLs */,
    'filters'     => /* status, failure_reason, deliveryman_id, date_from, date_to */,
    'stats'       => /* today, open, in_progress, resolved, return_rate */,
    'lookups'     => /* statuses, reasons, deliverymen */,
    'urls'        => [ 'index', 'export' ],
    't'           => /* labels */,
]);
```

`urls.export` is `route('ndr.export', $filters)` — pre-built with the
current filter set, so the download matches what's on screen.

Defensive paginator handling on `deliveryman->all()` via
`instanceof AbstractPaginator ? items() : value`.

## UI

**Stats row** — 4 gradient cards: Today (indigo), Pending = open +
in_progress (amber), Resolved (emerald), Return rate % (rose). Each
card has a subtle background icon.

**Filter bar** — 5 fields:

- Status (`open / in_progress / resolved / returned`)
- Failure reason (from `NdrFailureReason` enum options)
- Courier (from deliverymen lookup, using `user_id` as the value like
  legacy)
- Date from / Date to

Submits via `router.get(urls.index, draft, { preserveState, preserveScroll,
replace })`. "Clear" resets state + `router.get(urls.index)` to drop
query params. Export-to-Excel link uses `urls.export`.

**Table** — 8 columns: ID, Tracking (mono), Attempt badge (`N/3` with
`amber → orange → rose` color depending on N), Failure reason, Courier,
Status pill, Created (relative time), View link.

Empty state when no rows. Prev/Next pagination footer.

## Deferred

- `show`, `updateAction`, `resolve`, `create` endpoints all still hit
  legacy blades.
