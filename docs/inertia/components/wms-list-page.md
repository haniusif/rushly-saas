# `ListPage` + `RendersInertiaIndex`

Shared chrome used by every WMS index page. A bit of React for the UI,
a PHP trait for the controller-side scaffolding.

## React: `resources/js/Components/wms/ListPage.jsx`

Drop-in wrapper that any list page can use. Handles:

- AdminLayout title + breadcrumbs
- Optional stats row above the filter
- Filter form with auto-bound Clear / Filter buttons that call
  `router.get(urls.index, draft, { preserveState, preserveScroll, replace })`
- Header strip — "Showing X – Y of Z" + slot for header extras (Export /
  Map view / per-page selector / etc.) + Add CTA gated on
  `permissions.create` and `urls.create`
- Table card (just renders `tableContent`)
- Prev / Next pagination footer when `pagination.last_page > 1`

### Usage

```jsx
import ListPage, {
    tableHeadClass, tableRowClass, emptyRow, FilterLabel, Pill, ucwords,
} from '@/Components/wms/ListPage';

<ListPage
    t={t} urls={urls} pagination={pagination} permissions={permissions}
    filters={filters}
    defaultFilters={{ status: '', hub_id: '' }}
    statsCards={<div>…</div>}
    filterContent={({ draft, setDraft }) => (
        <div className="grid gap-3 md:grid-cols-12">
            …
        </div>
    )}
    headerExtras={<a href="...">Export</a>}
    tableContent={
        <>
            <thead><tr className={tableHeadClass}>…</tr></thead>
            <tbody>
                {rows.length === 0 && emptyRow(N, t.no_rows)}
                {rows.map((r) => <tr key={r.id} className={tableRowClass}>…</tr>)}
            </tbody>
        </>
    }
/>
```

### Exports

| Name | What |
|---|---|
| `ListPage` (default) | The wrapper |
| `tableHeadClass` | Standard `<tr>` class for `<thead>` row |
| `tableRowClass` | Standard `<tr>` class for `<tbody>` rows |
| `emptyRow(cols, label)` | One-row `<tr>` rendered when `rows.length === 0` |
| `FilterLabel` | Small uppercase label above a filter input |
| `Pill` | Coloured pill — props `color` (`grey/amber/sky/emerald/rose/violet/blue`) + `className` |
| `ucwords` | Cosmetic `'in_progress' → 'In Progress'` |

### Filter state

`filters` (from the controller) and `defaultFilters` (what "Clear"
should produce) are both required if `filterContent` is provided. The
component owns `draft` state internally and resyncs whenever the
incoming `filters` prop changes (so a page navigation reflects the new
state without keeping stale draft).

## PHP: `app/Http/Controllers/Backend/Wms/Concerns/RendersInertiaIndex.php`

Trait the 10 WMS controllers `use` to keep their `index()` methods
short.

```php
class WmsXxxController extends Controller
{
    use RendersInertiaIndex;
    // ...
}
```

### Methods

- `paginateMeta($p): array` — turns any `LengthAwarePaginator` into the
  React-side pagination object (`current_page` / `last_page` / `from` /
  `to` / `total` / `prev_url` / `next_url`).
- `indexLabels(array $extra = []): array` — common translation keys
  (`list`, `add`, `edit`, `view`, `open`, `delete`, `actions`, `filter`,
  `clear`, `status`, `all`, `no_rows`, `showing_results`) plus per-page
  overrides via `$extra`.
- `lookupRows($source, callable $shape): array` — defensive paginator-
  vs-collection iterator. `merchant->all()` / `hub->all()` /
  `deliveryman->all()` may return a paginator depending on the repo;
  this trait method always iterates `->items()` if a paginator is
  detected, otherwise the source itself. Without it, mapping a
  paginator with `collect()->map(...)` iterates `toArray()` keys (ints)
  and breaks.

### Why a trait, not a base class

The WMS controllers extend Laravel's `Controller`. A trait keeps them
free to inherit other base classes if needed later, and keeps the
shared code in one auditable place.
