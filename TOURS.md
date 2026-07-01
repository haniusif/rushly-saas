# Onboarding Tours

Interactive "Take a Tour" system for the Rushly SaaS. Guides users through
modules with a spotlight overlay, keyboard navigation, and per-user progress.

---

## What ships out of the box

- **Backend**: 4 tables (`tours`, `tour_steps`, `user_tour_progress`,
  `tour_events`), 4 Eloquent models, `TourRepository`, 3 JSON API endpoints,
  1 admin CRUD controller (Inertia).
- **Frontend**: `TourProvider` mounted globally in `merchant.jsx`,
  `TourOverlay` (SVG-mask spotlight), `TourStepPopover` (auto-placed card),
  `WelcomeModal` (first-login), `TourLauncher` (topbar dropdown).
- **Admin UI**: `/admin/tours` (list, create, edit, preview, analytics),
  gated by the `tour_manage` permission.
- **System tours seeded (18 total)**: 12 admin tours covering every sidebar
  group (`admin.dashboard.welcome` auto-start + parcels, wms, operations,
  finance, hr, productivity, billing, zatca, cms, system, settings) and
  6 merchant tours (`merchant.dashboard.welcome` auto-start + operations,
  finance, reports, settings, parcels). Every sidebar item across both
  panels is targetable via a `data-tour` attribute the layout renders
  automatically. Full inventory + step counts in `database/seeders/tours/README.md`.

---

## Architecture

### Data model

| Table | Purpose |
|---|---|
| `tours` | Tour definitions. `company_id = NULL` = system template. |
| `tour_steps` | Ordered steps. `target` JSON descriptor. `translations` JSON per locale. |
| `user_tour_progress` | Per-user state, keyed by (user_id, tour_key, tour_version). Bumping `version` on a tour re-shows it to users who saw v1. |
| `tour_events` | Append-only analytics stream (`started, step_forward, step_back, skipped, completed, dismissed, element_missing`). |

Users get a new `users.first_login_at` timestamp, set by
`LoginController::authenticated()` on the very first login. `TourProvider`
uses `first_login=true` on the initial `for-me` fetch to auto-open the
welcome modal for any `auto_start` tour.

### Resolution priority

`TourRepository::forUser()` returns tours applicable to the current user:

1. **Tenant overrides win** over system rows sharing the same `key`.
2. **Role scoping**: `role_scope` JSON array of `UserType` ints; `NULL` = all
   roles.
3. **Active + tenant-scoped**: `is_active = true`, `company_id IN (NULL,
   current_tenant)`.

Cached per-user for 5 minutes (`Cache::remember`, keyed by
`tenant.user.locale.permissions_hash`) — evicted on progress save.

### Frontend engine

```
resources/js/Tour/
├── TourProvider.jsx     — top-level context, loads for-me, autostart, keyboard bindings
├── TourOverlay.jsx      — SVG-mask spotlight, reposition on scroll/resize
├── TourStepPopover.jsx  — auto-placed tooltip, progress dots, keyboard
├── WelcomeModal.jsx     — first-login modal
├── TourLauncher.jsx     — topbar dropdown (all tours + progress state)
├── api.js               — thin fetch wrappers
├── keyboard.js          — arrow / Enter / Esc bindings
└── resolvers/
    └── findTarget.js    — data-tour | selector | route-name → DOM
```

### Missing element handling

When `findTarget()` returns null (e.g. the target sidebar item isn't
rendered on the current page), the popover centers itself on the viewport
and the engine emits an `element_missing` analytics event. Users can still
skip or advance the tour — no dead-end.

---

## Authoring tours

> **Day-to-day cheatsheet: `database/seeders/tours/README.md`** —
> co-located with the JSON files, has the full JSON schema, complete
> `data-tour` anchor catalog (44 admin + 12 merchant sidebar anchors +
> dashboard widget + topbar anchors), 4 copy-paste templates, and the
> current 18-tour inventory. Start there when you want to add a new tour.
> This section is the short version.

### Option A — JSON seed file (recommended for system tours)

Add a file under `database/seeders/tours/*.json`:

```json
{
    "key": "merchant.myfeature.tour",
    "module": "myfeature",
    "title": "My feature tour",
    "description": "Learn what my feature does.",
    "role_scope": [2],
    "version": 1,
    "is_active": true,
    "auto_start": false,
    "trigger_route": "merchant-panel.myfeature.index",
    "steps": [
        {
            "target": { "type": "data-tour", "value": "sidebar-nav_myfeature" },
            "placement": "end",
            "spotlight_padding": 6,
            "translations": {
                "en": { "title": "Sidebar link", "body": "Click here to open My feature." },
                "ar": { "title": "الرابط الجانبي", "body": "اضغط هنا لفتح ميزتي." }
            }
        }
    ]
}
```

Then run:

```bash
php artisan db:seed --class="Database\\Seeders\\TourSeeder"
```

The seeder is idempotent — re-running updates the tour and replaces its
steps. Tours are stored with `company_id = NULL` (system template).

### Option B — Admin manager UI

Log in as an admin with the `tour_manage` permission and visit
`/admin/tours`. Create tenant-scoped tours that override system
templates with the same `key`.

### Targeting

Three target types:

| Type | Value | When to use |
|---|---|---|
| `data-tour` | `sidebar-nav_parcels` | Preferred. Add `data-tour="X"` to the element. |
| `selector` | `#dashboard-kpis` or `.chart-card` | For elements you can't easily add a data attribute to. Fragile — CSS refactors will break these. |
| `route-name` | `merchant-panel.parcel.index` | Used with `action.navigate` to walk the user to a different page mid-tour. |

### Adding `data-tour` attributes

Already applied to:

- `AdminLayout.jsx` — every sidebar nav item: `sidebar-{tKey}`
- `MerchantLayout.jsx` — every sidebar nav item: `sidebar-{tKey}` + `topbar-notifications`
- `Merchant/Dashboard/Index.jsx` — `dashboard-kpis`, `dashboard-amounts`, `dashboard-charts`, `dashboard-reports`

When adding a new page, sprinkle `data-tour` on:
- Section wrappers (`data-tour="parcels-toolbar"`)
- Individual widgets (`data-tour="stats-total"`)
- Key CTAs (`data-tour="cta-create"`)

---

## API surface

All under session auth (same cookie as Inertia). Mounted in the
tenant-init middleware group at the tenant subdomain root.

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/tours/for-me` | Tours applicable to current user + first-login flag |
| `POST` | `/tours/{key}/progress` | `{status, current_step, version}` |
| `POST` | `/tours/{key}/event` | `{event, step_index?, duration_ms?, meta?}` |

Admin CRUD (`/admin/tours/*`, gated by `tour_manage`):

- `GET /admin/tours` — list
- `GET /admin/tours/create` — create form
- `POST /admin/tours/store` — save new
- `GET /admin/tours/{id}/edit` — edit form
- `PUT /admin/tours/{id}` — save changes
- `DELETE /admin/tours/{id}` — remove
- `POST /admin/tours/{id}/toggle` — enable/disable (JSON)
- `GET /admin/tours/{id}/preview` — preview run
- `GET /admin/tours/analytics` — analytics dashboard

---

## Analytics

Events flow into `tour_events` (append-only, indexed by
`(company_id, tour_key, event, created_at)`). The analytics page
computes per-tour aggregates on demand:

- **Starts** — count of `event=started`
- **Completes** — count of `event=completed`
- **Skips** — count of `event=skipped`
- **Completion %** — `completes / starts`
- **Drop-off step** — most common `step_index` among skips/dismisses
- **Avg time/step** — mean `duration_ms` of `event=step_forward`

Queries are cheap thanks to the indexes. If your `tour_events` table grows
past ~10M rows, add a nightly job to roll up per-day aggregates.

---

## Accessibility

- `role="dialog" aria-modal="true"` on the popover, focus set on step
  change, `aria-live="polite"` region for step announcements.
- Keyboard: `→ / Enter = Next`, `← = Prev`, `Esc = Skip`.
- Popover respects RTL (uses logical `start/end` placement).
- Missing element → popover centers, engine emits `element_missing`, no
  hard failure.

---

## Testing strategy

Automated coverage suggestions (not shipped as tests in this PR):

**Feature (Laravel `TestCase`)**
- `it_returns_tours_applicable_to_role()` — POST a login, hit `/tours/for-me`, assert only role-scoped tours come back.
- `it_marks_progress_and_updates_cache()` — post progress, re-fetch, assert cache invalidated.
- `it_emits_tour_events()` — log started/completed, query `tour_events`, assert rows.
- `it_gates_admin_manager_by_tour_manage()` — an admin without `tour_manage` gets 403 on `/admin/tours`.

**Component (Playwright / Cypress)**
- `sidebar_tour_walkthrough()` — log in as a first-login merchant, assert welcome modal, click "Take a tour", verify spotlight moves through each step, assert progress persists on refresh.
- `missing_target_skips_step()` — start a tour whose second step targets a non-existent selector; assert popover centers and analytics event fires.
- `keyboard_only_navigation()` — start a tour with tab focus on the popover, walk it with Enter/Left/Esc.

---

## Files touched

Backend:
- `database/migrations/2026_07_01_100001..100006_*.php`
- `app/Models/Backend/{Tour,TourStep,UserTourProgress,TourEvent}.php`
- `app/Repositories/Tour/{TourRepositoryInterface,TourRepository}.php`
- `app/Http/Controllers/Api/V10/TourController.php`
- `app/Http/Controllers/Backend/TourManagerController.php`
- `app/Http/Requests/Tour/{StoreRequest,UpdateRequest}.php`
- `app/Http/Controllers/Auth/LoginController.php` — added `authenticated()` hook
- `app/Providers/AppServiceProvider.php` — repo binding
- `database/seeders/PermissionSeeder.php` — added `tour_manage`
- `database/seeders/TourSeeder.php`
- `database/seeders/tours/*.json`
- `routes/web.php` — API + admin routes

Frontend:
- `resources/js/Tour/*.jsx, *.js` (7 files)
- `resources/js/merchant.jsx` — wired TourProvider
- `resources/js/Layouts/{AdminLayout,MerchantLayout}.jsx` — TourLauncher + data-tour attrs
- `resources/js/Pages/Merchant/Dashboard/Index.jsx` — data-tour attrs on widgets
- `resources/js/Pages/Admin/Tours/{Index,Create,Edit,Preview,Analytics,TourForm}.jsx`
- `resources/js/lib/i18n.js` — `take_a_tour` EN + AR
- `lang/{en,ar}/tours.php` + `lang/{en,ar}/menus.php` — tour manager labels

---

## Rollback

- Drop tables (in reverse order): `tour_events, user_tour_progress, tour_steps, tours`
- Remove `users.first_login_at` column
- Revert `permissions` JSON rows (the down migration handles this)
- Delete new files under `app/Models/Backend/Tour*.php`, `app/Repositories/Tour/`, `app/Http/Controllers/{Api/V10/TourController,Backend/TourManagerController}.php`, `resources/js/Tour/`, `resources/js/Pages/Admin/Tours/`.
- Restore `merchant.jsx`, `LoginController.php`, layouts, `AppServiceProvider.php`, `PermissionSeeder.php`, `routes/web.php`.

Nothing in the existing app calls the tour engine, so if tours are disabled
the app behaves exactly as before.
