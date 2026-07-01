# Knowledge Base — How to extend it

The admin Knowledge Base lives at `/admin/knowledge-base`. It's a central
hub with a card per system section; each section opens a handbook page
that documents every sub-page (purpose, key fields, status flow, cross
links, notes) and has a per-sub-page screenshot slot with upload /
replace / delete.

This doc explains how to **add a new sub-page**, **add a new section**,
or **add a new locale**. Content lives in PHP lang files so editing it
needs no JS rebuild.

> **Sibling system: onboarding tours.** The KB gives users a *static*
> reference to browse when they want to look something up. Onboarding
> tours (`/admin/tours`, engine at `resources/js/Tour/`) give them an
> *interactive* spotlight walkthrough of the same modules on first login
> or on demand from the topbar. The two are complementary — reach for KB
> when you need a durable reference doc, reach for tours when you need
> to guide someone through a UI flow. See `TOURS.md` and
> `database/seeders/tours/README.md`.

---

## Where things live

| Piece | Path |
|---|---|
| Hub + section controller | `app/Http/Controllers/Backend/AdminKnowledgeBaseController.php` |
| Routes (inside `prefix=admin`, behind auth) | `routes/web.php` — search `admin.kb.` |
| Hub page (cards grid) | `resources/js/Pages/Admin/KnowledgeBase/Hub.jsx` |
| Section page (one section's handbook) | `resources/js/Pages/Admin/KnowledgeBase/Section.jsx` |
| Hub chrome strings (titles, buttons) | `lang/{en,ar}/kb_chrome.php` |
| **Section content** | `lang/{en,ar}/kb_{section}.php` |
| Uploaded screenshots | `public/images/kb/{section}/{sub}.png` |

The WMS section is a special case — its card on the hub **deep-links** to
the dedicated `/admin/wms/knowledge-base` page (built earlier and not
migrated). The WMS KB's own files live at:

- `app/Http/Controllers/Backend/Wms/WmsKnowledgeBaseController.php`
- `resources/js/Pages/Admin/Wms/KnowledgeBase/Index.jsx`
- WMS-only translations are inline in `resources/js/lib/i18n.js`
  (`wms_kb_*` keys)

---

## How content is loaded

1. The controller's `index()` builds a list of sections from the
   `SECTIONS` constant (slug, icon, sub-page slugs, optional external URL).
2. For each section it fetches `__('kb_{section}.label')` and
   `__('kb_{section}.overview')` and ships them to `Hub.jsx`.
3. When you open a section, `show($section)` reads the whole
   `trans('kb_{section}')` tree, joins the manifest's sub-page slugs with
   the lang file's `sub_pages[{slug}]` payload, and renders
   `Section.jsx`. Missing sub-pages show a "Content pending" placeholder.
4. Screenshots are picked up from
   `public/images/kb/{section}/{sub}.png` (mtime cache-busts the URL).

Hyphens in section / sub-page slugs are fine. Only the lang **file** name
converts hyphens to underscores (`kb_payment_gateway.php`), the array key
inside `sub_pages` keeps the hyphen (`'payment-gateway' => [...]`).

---

## Recipe 1 — Add a sub-page to an existing section

Example: add an "Imports" sub-page to Shipments.

**Step 1.** Add the slug to the section's manifest entry in
`AdminKnowledgeBaseController.php`:

```php
'shipments' => [
    'icon' => 'Package',
    'subs' => ['parcels', 'bulk-action', 'ndr', 'abnormal', 'imports'],
],
```

**Step 2.** Add a `sub_pages` entry in **both** `lang/en/kb_shipments.php`
and `lang/ar/kb_shipments.php`:

```php
'imports' => [
    'icon'    => 'Upload',         // Lucide icon name (see icon list below)
    'label'   => 'CSV Imports',
    'purpose' => 'Bulk-create parcels by uploading a CSV …',
    'pages' => [
        ['path' => 'Index',  'desc' => 'List of recent imports with status and row counts.'],
        ['path' => 'Create', 'desc' => 'Upload a CSV — preview rows, fix validation errors, confirm.'],
    ],
    'fields' => ['file', 'row_count', 'valid_rows', 'invalid_rows', 'status'],
    'status_flow' => [
        ['label' => 'queued',     'tone' => 'default'],
        ['label' => 'processing', 'tone' => 'info'],
        ['label' => 'completed',  'tone' => 'ok'],
        ['label' => 'failed',     'tone' => 'bad'],
    ],
    'cross_links' => 'Created parcels appear in the Parcels list …',
    'notes'       => 'Max 10k rows per file. Header row required.',
],
```

**Step 3.** (Optional) Drop a screenshot at
`public/images/kb/shipments/imports.png` — or just upload it from the UI
after deploy.

That's it. No route changes, no controller changes beyond the manifest.

---

## Recipe 2 — Add a brand-new section

Example: add a "Reports" top-level section.

**Step 1.** Register it in the `SECTIONS` constant in
`AdminKnowledgeBaseController.php`:

```php
'reports' => [
    'icon' => 'BarChart3',
    'subs' => ['parcel-status', 'revenue', 'courier-performance'],
],
```

**Step 2.** Make sure the icon is in the **icon map** in both
`Hub.jsx` and `Section.jsx`. If it's a new Lucide icon, add it to:

```js
// Hub.jsx + Section.jsx — top of file
import { BarChart3, /* … */ } from 'lucide-react';

const ICONS = {
    LayoutDashboard, Package, Warehouse, Truck, DollarSign,
    UserCog, ListChecks, Receipt, FileText, Layout, History,
    Settings, BarChart3,   // ← add here
};
```

(If you skip this, the section falls back to a generic `BookOpen` icon.)

**Step 3.** Create the lang files:

```bash
# Pick a safe filename — hyphens become underscores
touch lang/en/kb_reports.php
touch lang/ar/kb_reports.php
```

Use the same structure as the existing populated sections (label,
overview, sub_pages array). Look at `lang/en/kb_finance.php` for a
short, complete template.

**Step 4.** (Optional) Add the section to a sidebar group if you want a
direct link from the main admin nav — edit `resources/js/Layouts/AdminLayout.jsx`.

That's it. The new card appears on the hub immediately.

---

## Recipe 3 — Add a new locale

The hub uses Laravel's standard `lang/{locale}/` directory. To add French:

**Step 1.** Make sure `'fr'` is in `SUPPORTED_LOCALES` in `resources/js/lib/i18n.js`.

**Step 2.** Copy every `lang/en/kb_*.php` file into `lang/fr/` and translate the
human-readable strings. Don't translate technical identifiers (field names,
status enum values, route paths, code snippets).

**Step 3.** Translate `lang/en/kb_chrome.php` too (titles, buttons, "Back to
all sections", etc.) → `lang/fr/kb_chrome.php`.

Laravel's `__()` helper handles the locale lookup automatically — no
controller changes needed.

---

## Reference

### Status flow tones

`tone` controls the colour of a status pill. Use whichever matches the
semantics, not the literal English word:

| tone | colour | typical use |
|---|---|---|
| `default` | grey | initial / neutral state (`pending`, `draft`) |
| `info` | sky-blue | in-progress / intermediate (`picking`, `processing`) |
| `warn` | amber | requires attention (`pending_approval`, `pending`) |
| `ok` | emerald | success / terminal-good (`delivered`, `approved`) |
| `bad` | rose | failure / terminal-bad (`cancelled`, `failed`, `rejected`) |
| `violet` | violet | "downstream effect" / system action |

### Icons available

Edit `ICONS = {…}` in `Hub.jsx` and `Section.jsx` to add more. Currently mapped:

`LayoutDashboard`, `Package`, `Warehouse`, `Truck`, `DollarSign`,
`UserCog`, `ListChecks`, `Receipt`, `FileText`, `Layout`, `History`,
`Settings`, `BookOpen`.

Per-sub-page icons can be **any** Lucide name as a string in the lang
file (`'icon' => 'AlertOctagon'`) — but only icons in the import map at
the top of `Section.jsx` will actually render. If you pick an
unimported name it silently falls back to `BookOpen`.

### Sub-page schema (full)

Every field is optional except `label`. Anything you omit just hides
its block on the page.

```php
'my-sub' => [
    'icon'        => 'Package',
    'label'       => 'My Sub-page',
    'purpose'     => '1–2 sentence summary shown under the title.',
    'pages' => [
        ['path' => 'Index', 'desc' => '…'],
        ['path' => 'Create', 'desc' => '…'],
        // any number of entries — usually 2–6
    ],
    'fields'      => ['col_a', 'col_b', 'col_c'],   // shown as code chips
    'status_flow' => [
        ['label' => 'pending',  'tone' => 'default'],
        ['label' => 'approved', 'tone' => 'ok'],
    ],
    'cross_links' => 'Free prose about which modules feed in / out.',
    'notes'       => 'Anything non-obvious — gotchas, gates, retention, etc.',
],
```

---

## Screenshots

Two ways to add screenshots:

1. **From the UI (recommended).** On each sub-page, hit the "Upload
   screenshot" button. The file is re-encoded to PNG via GD and stored
   at `public/images/kb/{section}/{sub}.png`. Replace / Delete buttons
   appear on hover when one exists.

2. **From the filesystem.** Drop a PNG at the same path. The page
   detects it on next render (mtime cache-busts the `<img src>`).

The directory `public/images/kb/` is **not** in `.gitignore`. If you
want uploaded screenshots to land in version control, you'll need to
`git add` them manually. If you don't (e.g. you redeploy from a clean
checkout and re-upload), add the directory to `.gitignore` — your call.

GD is required for the upload re-encode (PHP `imagecreatefrompng` /
`imagecreatefromjpeg` / `imagecreatefromwebp`). All three are present
on the current production server.

---

## Endpoints (for reference)

| Verb | Path | Permission |
|---|---|---|
| `GET` | `/admin/knowledge-base` | admin auth |
| `GET` | `/admin/knowledge-base/{section}` | admin auth |
| `POST` | `/admin/knowledge-base/{section}/screenshot/{sub}` | `knowledge_base_update` |
| `DELETE` | `/admin/knowledge-base/{section}/screenshot/{sub}` | `knowledge_base_update` |

Upload accepts `screenshot` (image, max 5 MB, png / jpg / jpeg / webp).
Delete takes no body.

The same `knowledge_base_update` permission also gates the WMS KB upload /
delete endpoints (`POST/DELETE /admin/wms/knowledge-base/screenshot/{slug}`).

## Permissions

Reading the KB is open to any logged-in admin. **Writing** (screenshot
upload / replace / delete) requires `knowledge_base_update`.

- Defined in `database/seeders/PermissionSeeder.php` under the
  `knowledge_base` attribute.
- Backfilled for existing installs by
  `database/migrations/2026_06_27_000001_seed_knowledge_base_permissions.php`,
  which idempotently grants the new permission to anyone who already has
  `general_settings_update` and to the Super Admin role.
- The React pages receive a `can_update` prop from their controllers and
  hide the Upload / Replace / Delete buttons when it is false — so users
  without the permission see the screenshot read-only, no broken-action UI.
- Grant or revoke per-role from the Users & Roles section.

---

## Quick checklist for a new section

- [ ] Manifest entry in `AdminKnowledgeBaseController::SECTIONS`
- [ ] Icon import + map entry in `Hub.jsx` **and** `Section.jsx` (if new)
- [ ] `lang/en/kb_{slug}.php` with `label`, `overview`, populated `sub_pages`
- [ ] `lang/ar/kb_{slug}.php` mirror
- [ ] (optional) Screenshots at `public/images/kb/{slug}/{sub}.png`
- [ ] `php artisan route:clear` if route caching is enabled in your env
- [ ] Visit `/admin/knowledge-base` and click the new card
