# `AdminLayout`

`resources/js/Layouts/AdminLayout.jsx`

The shell every admin Inertia page wraps itself in. Mirrors the structure
of `MerchantLayout.jsx` but with admin-specific nav groups and no brand
overrides (admin is single-tenant).

## Usage

```jsx
<AdminLayout title="Couriers" breadcrumbs={["Couriers", "List"]}>
    {/* page content */}
</AdminLayout>
```

Props:

- `title` — page heading (also pushed into `<Head>`)
- `breadcrumbs` — array of strings; last item is rendered with stronger text
- `children` — page body

## Sidebar

Fixed at `w-64`, dark theme via the `bg-sidebar` / `text-sidebar-foreground`
tokens (set in `tailwind.config.js`). Six nav groups, 25 items total:

| Group | Items |
|---|---|
| Main | Dashboard |
| Parcels | Parcels, Bulk action, NDR, Abnormal |
| Warehouse | WMS dashboard, Products, Stock, Locations, GRN, Fulfillment, Outbound, Adjustments, Cycle counts, Damage |
| Operations | Couriers, TMS, Hubs, Merchants |
| Productivity | To-do, Support, News & offers |
| System | Activity logs, Settings |

Each item is `{ tKey, icon, route, match }` where:

- `tKey` is a key in `resources/js/lib/i18n.js` (`menu_*` keys, EN + AR)
- `icon` is a lucide-react component
- `route` is a Laravel route name resolved via `safeRoute()` (wraps
  `window.route()` from Ziggy; returns `'#'` if unknown)
- `match` is an array of URL prefixes; active state is computed from
  `url.startsWith('/' + prefix)`

On mobile the sidebar is hidden behind a backdrop and slides in via
`translate-x-*`. The X button closes it.

## Topbar

Sticky `h-16` with backdrop blur. Items left → right:

1. Mobile sidebar toggle (`md:hidden`)
2. Search input (`md:flex` — placeholder only; no behaviour wired yet)
3. Language switcher (`LanguageMenu` dropdown — uses `SUPPORTED_LOCALES`
   from `lib/i18n.js`; switching POSTs to the `setlocalization` route)
4. Dark mode toggle (persists in `localStorage` under `admin-theme`)
5. Bell icon (no behaviour)
6. User dropdown:
   - Avatar (initials), name, chevron
   - Items: Profile (links to `admin.profile.edit`), Log out
     (`router.post(safeRoute('logout'))` after `window.confirm`)

## Theming

Inherits the same shadcn token system as the merchant side. The dark mode
key (`admin-theme`) is distinct from `merchant-theme` so admin and merchant
sessions can have independent preferences.

## Translation contract

The layout reads sidebar / topbar strings via `useT()` (see
`resources/js/lib/i18n.js`). The dictionary contains `menu_*` keys for
every group label and item label, in both EN and AR. Add more locales by
extending `DICTIONARY` in `i18n.js`.

## Known broken links

Several sidebar items point at routes that may not exist on every tenant:

- `tms` — only if the TMS module is enabled
- `admin.profile.edit` — no such route exists yet; the user dropdown's
  Profile item will navigate to `#` until that's added
- `settings.index` — depends on tenant feature flags

`safeRoute()` swallows the missing-route error and returns `'#'`. If a
sidebar link is dead in a particular tenant, the cleanest fix is to gate
the item server-side and not render it at all — but that means moving
the `NAV` array to props instead of a constant.
