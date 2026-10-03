# Inertia migration guide — applying the new design to a legacy page

This guide covers the end-to-end recipe for converting a legacy `backend.*.blade.php`
page to the new Inertia + React + Tailwind stack. Every page that has shipped under
`resources/js/Pages/Admin/**` followed these steps; following them keeps the next page
visually consistent and avoids a lot of tedious one-off plumbing.

If you only read one section, read [The checklist](#the-checklist) at the bottom.

---

## 0. Prerequisites — already wired

These are global. Don't re-do them per page.

- **Inertia middleware**: `App\Http\Middleware\HandleInertiaRequests` is registered in the
  `web` group and selects `admin.app` vs `merchant.app` root view based on URL prefix.
- **Vite entries**: `resources/js/admin.jsx` (admin app) and `resources/js/merchant.jsx`
  (merchant app). The `app.blade.php` / `merchant.blade.php` files include `@vite([...])`.
- **Ziggy**: routes are emitted to JS. Use the `safeRoute(name, params)` helper from
  `resources/js/lib/utils` — it falls back gracefully when a route is missing.
- **UI primitives** in `resources/js/Components/ui/`: `Button`, `Card`, `Input`, `Label`,
  `Select`, `Textarea`, `DropdownMenu`. Pull from these — don't redefine.
- **Lucide icons**: every page uses `lucide-react` for icons.
- **Shared chrome**:
  - `Layouts/AdminLayout.jsx` — admin shell (sidebar, topbar, GlobalSearch, breadcrumbs)
  - `Layouts/MerchantLayout.jsx` — merchant shell
  - `Components/parcel/ShipmentDrawer.jsx`, `Components/parcel/ParcelForm.jsx`,
    `Components/parcel/ChangeStatusModal.jsx`, `Components/wms/ListPage.jsx`,
    `Components/merchant/MerchantSubHeader.jsx` — reuse before creating new shells

---

## 1. Anatomy of a page

Each converted page has **two halves**:

```
Backend
  app/Http/Controllers/Backend/<Module>Controller.php
    └─ public function <action>($id) {
         …repo queries…
         return Inertia::render('Admin/<Module>/<Page>', [ rows, lookups, urls, t, ... ]);
       }

Frontend
  resources/js/Pages/Admin/<Module>/<Page>.jsx
    └─ export default function Page({ rows, lookups, urls, t, ... }) {
         return <AdminLayout title={t.title} breadcrumbs={[...]}>
                  …Card / form / table…
                </AdminLayout>;
       }
```

The controller is the **only** place where translations are resolved (`__('parcel.title')`)
and route URLs are computed (`route('parcel.index')`). The React page is dumb: it only
reads `t.something` and `urls.something` from props.

Why? Locale resolution + Ziggy URL composition belong to PHP. Keeping React pure means we
never have to ship the entire route list or translation tree to the browser.

---

## 2. The backend half — the controller pattern

### 2.1 Convert a `view(...)` call

Before:
```php
public function show($id)
{
    $product = $this->repo->find($id);
    return view('backend.wms.products.show', compact('product'));
}
```

After:
```php
public function show(int $id)
{
    $product = $this->repo->find($id);
    if (!$product) {
        return redirect()->route('wms.products.index');
    }
    $product->loadMissing(['merchant', 'hub', 'stocks.location']);

    return Inertia::render('Admin/Wms/Products/Show', [
        'product'     => [ /* flat array — only the fields the page needs */ ],
        'stock'       => [ 'rows' => $stockRows, 'on_hand' => $on, ... ],
        'permissions' => [ 'update' => hasPermission('wms_manage') ],
        'urls'        => [
            'index'   => route('wms.products.index'),
            'edit'    => route('wms.products.edit', $product->id),
            'destroy' => route('wms.products.destroy', $product->id),
        ],
        't' => [
            'title'        => 'Product',
            'edit'         => __('levels.edit') ?: 'Edit',
            'sku'          => 'SKU',
            'no_stock'     => 'No stock recorded yet.',
            // …every user-facing string the React page references…
        ],
    ]);
}
```

**Rules**:
1. **Project, don't pass models.** Flatten Eloquent rows into plain arrays before they
   reach React. This avoids accidentally exposing private fields, keeps the JSON small,
   and decouples the API surface from schema changes.
2. **Resolve everything server-side**: URLs (`route('...')`), labels (`__('...') ?: 'fallback'`),
   permissions (`hasPermission('...')`).
3. **The four canonical prop buckets**:
   - `rows` / `<entity>` — the actual data
   - `lookups` — dropdown/select options
   - `urls` — every link or form-action the page needs
   - `t` — translation map
   - Plus optional `permissions`, `filters`, `pagination`, `currency`
4. **`?: 'English fallback'`** on every `__('...')` call. If the key is missing the page
   stays readable instead of showing the key string.

### 2.2 Index pages — paginated tables

Use the `RendersInertiaIndex` trait (`app/Http/Controllers/Backend/Wms/Concerns/`):

```php
use App\Http\Controllers\Backend\Wms\Concerns\RendersInertiaIndex;

class WmsLocationController extends Controller
{
    use RendersInertiaIndex;

    public function index(Request $request)
    {
        $paginator = $this->repo->all($request);

        return Inertia::render('Admin/Wms/Locations/Index', [
            'rows'        => collect($paginator->items())->map(fn ($l) => [...])->values(),
            'pagination'  => $this->paginateMeta($paginator),
            'filters'     => [ 'hub_id' => $request->input('hub_id', ''), ... ],
            'lookups'     => [ 'hubs' => $this->lookupRows($hubs, fn ($h) => [...]) ],
            'permissions' => [ 'create' => hasPermission('wms_manage') ],
            'urls'        => [ 'index' => route(...), 'create' => route(...) ],
            't'           => $this->indexLabels([ 'title' => 'Storage locations', ... ]),
        ]);
    }
}
```

The trait gives you:
- `paginateMeta($p)` — emits `current_page / last_page / from / to / total / prev_url / next_url / per_page`
- `lookupRows($source, $shape)` — handles `LengthAwarePaginator | Collection | array`
- `indexLabels($extra)` — common labels merged with per-page extras

### 2.3 Create + Edit pages — shared form pattern

Don't make `Edit.jsx` separately. Make `Create.jsx` dual-mode by accepting `mode` and an
existing-entity prop, then route both controller actions to the same Inertia page:

```php
// create()
return Inertia::render('Admin/Wms/Products/Form', $this->formProps([
    'mode' => 'create',
    'urls' => [ 'submit' => route('wms.products.store'), 'cancel' => route('wms.products.index') ],
]));

// edit()
return Inertia::render('Admin/Wms/Products/Form', $this->formProps([
    'mode'    => 'edit',
    'product' => [...],
    'urls'    => [ 'submit' => route('wms.products.update', $id), 'cancel' => route('wms.products.show', $id) ],
]));
```

```jsx
const form = useForm({
    name:  product?.name ?? '',
    sku:   product?.sku  ?? '',
    // …
    ...(isEdit ? { _method: 'put' } : {}),
});
```

The `_method: 'put'` spoofs the PUT verb so a single `form.post(urls.submit)` works for
both. For multipart submissions (file uploads), keep `forceFormData: true` on the post.

---

## 3. The frontend half — the page recipe

### 3.1 Skeleton

```jsx
import * as React from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Save, /* …icons… */ } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';

export default function Page({ rows = [], lookups = {}, urls = {}, t = {} }) {
    return (
        <AdminLayout title={t.title} breadcrumbs={[t.list, t.title]}>
            <Head title={t.title} />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                {/* Toolbar — back link + primary action */}
            </div>

            <Card>
                <CardContent className="p-6">
                    {/* page content */}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
```

### 3.2 Visual language

Pull these from existing pages — they're what makes the design feel consistent.

- **Card-first layout**. Everything lives in `<Card><CardContent>` blocks. Section
  headers go inside, not above.
- **Grid breakpoints**: `grid gap-5 lg:grid-cols-3` is the default 3-col layout, with
  one card spanning `lg:col-span-2` and the sidebar holding the rest. Forms use
  `grid gap-4 md:grid-cols-2` inside each card.
- **Label style**: tiny uppercase labels, not big.
  ```jsx
  <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
      {t.field_label}
  </div>
  ```
- **Pills / badges**: rounded-full, soft tint, hex-based when color comes from data:
  ```jsx
  // Static color
  <span className="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-[10px] font-medium">…</span>

  // Hex-driven (e.g. status colors from ParcelStatusHelper)
  <StatusPill label={r.status_label} color={r.status_color} />
  ```
- **Numbers**: always `tabular-nums` so columns align. Currency goes through `<Money>`.
- **Tables**:
  - header row: `bg-muted/40 text-[10px] uppercase tracking-wider text-muted-foreground border-b border-border`
  - cells: `px-3 py-3 align-top`
  - hover state on rows: `hover:bg-muted/30`
- **Buttons**: `<Button>` for primary, `<Button variant="outline">` for secondary,
  plain `<a className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 …">`
  for navigation links that need to look like buttons.
- **Empty states**: centered icon (40px, `text-muted-foreground/40`) + 1-line text +
  optional CTA. See `Admin/HubInCharge/Index.jsx` for the canonical empty-state block.

### 3.3 Patterns that recur

- **Filter form + table**:
  - `draft` state for the form
  - `router.get(urls.filter, draft, { preserveState: true, replace: true })` on submit
  - `clear()` resets the state and `router.get(urls.index)`
- **Date range**: don't use a free-text "YYYY-MM-DD To YYYY-MM-DD" input. Use two
  `<Input type="date">`, combine to wire format with `useEffect`. See `Pages/Admin/Parcel/Index.jsx`.
- **Inline confirm + delete**:
  ```jsx
  const onDelete = (row) => {
      if (!window.confirm(t.delete_confirm)) return;
      router.delete(row.urls.destroy, { preserveScroll: true });
  };
  ```
- **Modal/drawer**: use `createPortal(..., document.body)` to escape stacking contexts.
  `ShipmentDrawer.jsx` and `ChangeStatusModal.jsx` are the references — both handle
  Esc key, backdrop click, z-index, and hooks-order safety.

---

## 4. Translations — the rule

The controller's `t` array is the **only** thing the React page reads. Every user-facing
string goes through `t.something`. No hardcoded English in JSX — not even error messages
or window.alert text.

```jsx
// Bad
window.alert('Pick a courier.');
<p>No errors from the last import.</p>

// Good
window.alert(t.bulk_pick_courier);
<p>{t.no_errors}</p>
```

**On the backend**:
```php
't' => [
    'title'  => __('parcel.title')  ?: 'Parcels',
    'edit'   => __('levels.edit')   ?: 'Edit',
    'cancel' => __('levels.cancel') ?: 'Cancel',
    // page-specific strings (no separate language file needed):
    'delete_confirm' => 'Remove this in-charge?',
],
```

If a key is **missing** in `lang/en/*` or `lang/ar/*`, add it to both files — don't rely
only on the `?: 'English fallback'`. The fallback is a safety net, not a substitute for
proper i18n. See [Translation keys cheat sheet](#5-translation-keys-cheat-sheet).

After adding keys:
```bash
php artisan config:clear
php artisan view:clear
```

---

## 5. Translation keys cheat sheet

Common keys you'll reference. If missing, add to both `lang/en/levels.php` (or
`lang/en/<module>.php`) and `lang/ar/levels.php` (or `lang/ar/<module>.php`).

| Common | Where it lives |
|---|---|
| `levels.{add,edit,view,delete,actions,cancel,submit,confirm,filter,clear,save,list,status,date,created_at,updating}` | `lang/{locale}/levels.php` |
| `levels.{previous,next,all,select_file,no_data_found}` | `lang/{locale}/levels.php` |
| `levels.{income,expense,name,email,phone,address,unique_id,category,weight,qty,total}` | `lang/{locale}/levels.php` |
| `parcel.{title,tracking_id,customer_name,cash_collection,status,priority,cod,merchant,recipient_info,parcel_date}` | `lang/{locale}/parcel.php` |
| `parcel.{select_deliveryman,select_hub,import,sample,validation_log,in_row_number,bulk_action}` | `lang/{locale}/parcel.php` |
| `hub.title`, `incharge.title`, `merchant.title`, `deliveryman.title`, `support.title` | per-module file |
| `menus.delivery_type`, `menus.profile`, `menus.google_map_settings` | `lang/{locale}/menus.php` |

---

## 6. Permission gating

Permissions resolve server-side via `hasPermission('xxx_yyy')` and are surfaced to React
as a boolean map:

```php
'permissions' => [
    'create'        => hasPermission('parcel_create'),
    'update'        => hasPermission('parcel_update'),
    'delete'        => hasPermission('parcel_delete'),
    'status_update' => hasPermission('parcel_status_update'),
],
```

```jsx
{permissions.create && (
    <Link href={urls.create}>…</Link>
)}
```

Never gate on `userType` or hardcoded role names. Always go through `hasPermission()`.

---

## 7. Status / color values

When status colors come from the backend (parcel status, etc.), the controller passes
**hex strings** from a helper like `ParcelStatusHelper::color($id)`. React then renders
inline tints. Don't try to map hex strings into Tailwind class names.

```jsx
const FALLBACK_HEX = '#6c757d';
const isHex = (s) => /^#[0-9a-fA-F]{6}$/.test(s || '');
const hexToRgba = (hex, alpha) => {
    const h = isHex(hex) ? hex : FALLBACK_HEX;
    const r = parseInt(h.slice(1, 3), 16);
    const g = parseInt(h.slice(3, 5), 16);
    const b = parseInt(h.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

function StatusPill({ label, color }) {
    const hex = isHex(color) ? color : FALLBACK_HEX;
    return (
        <span
            className="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium"
            style={{
                backgroundColor: hexToRgba(hex, 0.12),
                borderColor:     hexToRgba(hex, 0.30),
                color:           hex,
            }}
        >
            {label || '—'}
        </span>
    );
}
```

---

## 8. Google Maps

For map pages, reuse the idempotent loader pattern from `Pages/Admin/Hub/Create.jsx` and
`Pages/Admin/Parcel/Map.jsx`:

```jsx
function loadGoogleMaps(apiKey) {
    if (window.google?.maps?.places) return Promise.resolve(window.google.maps);
    if (window.__rlMapsLoading)       return window.__rlMapsLoading;
    window.__rlMapsLoading = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.async = true; s.defer = true;
        s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
        s.onload  = () => resolve(window.google.maps);
        s.onerror = () => reject(new Error('Failed to load Google Maps'));
        document.head.appendChild(s);
    });
    return window.__rlMapsLoading;
}
```

The controller passes the key via `'google_maps_key' => googleMapSettingKey()`.

Native fullscreen via `requestFullscreen()` on the wrapper element — see `Parcel/Map.jsx`
for the toggle pattern (`fullscreenchange` listener, icon swap).

---

## 9. The checklist

Use this for every page conversion. Copy it into your PR description.

```
[ ] 1. Read the legacy blade — identify every data field, action, link, label.
[ ] 2. Identify the controller method that renders the blade.
[ ] 3. Replace return view(...) with return Inertia::render('Admin/<Module>/<Page>', [...]).
[ ] 4. Project Eloquent rows into flat arrays. Don't pass models.
[ ] 5. Resolve every URL via route('...'). Resolve every label via __('...') ?: 'fallback'.
[ ] 6. Emit four canonical prop buckets: rows/<entity>, lookups, urls, t. Add
       permissions / filters / pagination / currency when relevant.
[ ] 7. Create resources/js/Pages/Admin/<Module>/<Page>.jsx using the skeleton above.
[ ] 8. No hardcoded English in JSX — every user-facing string goes through t.something.
[ ] 9. Use AdminLayout + Card + UI primitives + lucide-react icons.
[ ] 10. For create + edit: share one form file (mode='edit', _method='put' on submit).
[ ] 11. Run npm run build, fix any errors.
[ ] 12. Hit the page in a browser, walk through happy path + at least one edge case
        (filter, delete, edit, submit with error).
[ ] 13. Verify Arabic renders (toggle the language dropdown). Add any missing keys to
        both lang/en/* and lang/ar/*.
[ ] 14. php artisan config:clear && php artisan view:clear if you added translation keys.
[ ] 15. Update the relevant doc under docs/inertia/pages/ or add a new entry.
```

---

## 10. Reference pages

When in doubt, look at the closest precedent:

| If you're building… | Look at |
|---|---|
| A list/index page with filter + pagination | `Pages/Admin/Parcel/Index.jsx` |
| A WMS-style sub-module index | `Pages/Admin/Wms/Products/Index.jsx` (uses shared `Components/wms/ListPage.jsx`) |
| A wizard / multi-step form | `Pages/Admin/Deliveryman/Create.jsx` |
| A single-section form | `Pages/Admin/Hub/Create.jsx` (incl. Google Maps) |
| A read-only "show" page | `Pages/Admin/Wms/Products/Show.jsx` |
| A show with stats + sub-table | `Pages/Admin/Hub/View.jsx` |
| A modal that escapes stacking | `Components/parcel/ChangeStatusModal.jsx` |
| A right-edge drawer | `Components/parcel/ShipmentDrawer.jsx` |
| A page with Google Maps + fullscreen | `Pages/Admin/Parcel/Map.jsx` |
| A file-upload form | `Pages/Admin/Parcel/Import.jsx` |

---

## 11. Common pitfalls

- **Forgetting `loadMissing(...)`** — the page fires N+1 queries the moment React
  iterates. Always eager-load relationships before serializing.
- **Returning paginators directly** — pass `$paginator->items()` and the meta separately.
  Use `$this->paginateMeta($p)`.
- **Numeric IDs in `<Select value=...>`** — `<Select>` matches by string. Coerce to
  string when serializing or when pre-populating form state:
  `String(merchant?.hub ?? '')`.
- **`?? 0` for things that are arrays** — `?? []` for arrays. `null ?? 0` shows `0`,
  which is wrong for lists.
- **Forgetting `_method: 'put'`** in shared edit forms — submission will hit `store()`
  and create a duplicate row.
- **`forceFormData: true` is required** when posting any `File` — Inertia's JSON path
  drops binary fields.
- **`preserveScroll: true` on inline actions** (priority toggle, delete, status change)
  so the user doesn't jump to the top of the table on every action.
- **Mixing `router.post` and form POSTs for logout** — Inertia can't follow the logout
  redirect to `/`. Use a real form POST with CSRF (see `AdminLayout.jsx` logout handler).
- **Color values rendered through a class map** — backend returns hex; class maps go
  stale. Use the inline `hexToRgba` approach from section 7.

---

## 12. After you ship

- Add a one-line entry to the relevant doc under `docs/inertia/pages/` describing what
  the page does and any non-obvious props.
- If you introduced a new shared component, add a doc under `docs/inertia/components/`.
- If you added or refined a translation key, update the
  [Translation keys cheat sheet](#5-translation-keys-cheat-sheet) in this file.
