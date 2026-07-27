# Inertia / React admin frontend

Documentation for the Inertia + React + Tailwind stack rolled out on the
admin side. Each file below documents one page or one shared piece —
controller method, file paths, props it expects, and behaviour you'd need
to know before changing it.

## Infrastructure

- [setup.md](setup.md) — composer + npm packages, vite.config inputs,
  Tailwind/PostCSS config, entry points, kernel middleware, dynamic
  `rootView` selection in `HandleInertiaRequests`.

## Shared components

- [components/admin-layout.md](components/admin-layout.md) — `AdminLayout`
  shell (sidebar groups, topbar features, dark-mode persistence, mobile
  drawer).
- [components/parcel-form.md](components/parcel-form.md) — `ParcelForm`
  body used by `Pages/Admin/Parcel/Create.jsx` and `Pages/Admin/Parcel/Edit.jsx`.
- [components/shipment-drawer.md](components/shipment-drawer.md) —
  `ShipmentDrawer` (right-edge offcanvas with tracking timeline) used by
  the parcel index.
- [components/wms-list-page.md](components/wms-list-page.md) — `ListPage`
  shared chrome for all WMS index pages, plus the `RendersInertiaIndex`
  PHP trait that the WMS controllers `use`.

## Pages

### Core

| URL | Doc | Page file |
|---|---|---|
| `/dashboard` (admin user-type) | [pages/admin-dashboard.md](pages/admin-dashboard.md) | `Pages/Admin/Dashboard/Index.jsx` |

### Parcels

| URL | Doc | Page file |
|---|---|---|
| `/admin/parcel/index` | [pages/admin-parcel-index.md](pages/admin-parcel-index.md) | `Pages/Admin/Parcel/Index.jsx` |
| `/admin/parcel/create` | [pages/admin-parcel-create.md](pages/admin-parcel-create.md) | `Pages/Admin/Parcel/Create.jsx` |
| `/admin/parcel/edit/{id}` | [pages/admin-parcel-edit.md](pages/admin-parcel-edit.md) | `Pages/Admin/Parcel/Edit.jsx` |
| `/admin/bulk_action` | [pages/admin-bulk-action.md](pages/admin-bulk-action.md) | `Pages/Admin/Parcel/BulkAction.jsx` |
| `/admin/ndr` | [pages/admin-ndr-index.md](pages/admin-ndr-index.md) | `Pages/Admin/Ndr/Index.jsx` |
| `/admin/abnormal` | [pages/admin-abnormal-index.md](pages/admin-abnormal-index.md) | `Pages/Admin/Abnormal/Index.jsx` |

### Operations

| URL | Doc | Page file |
|---|---|---|
| `/admin/deliveryman` | [pages/admin-deliveryman-index.md](pages/admin-deliveryman-index.md) | `Pages/Admin/Deliveryman/Index.jsx` |
| `/admin/deliveryman/create` | [pages/admin-deliveryman-create.md](pages/admin-deliveryman-create.md) | `Pages/Admin/Deliveryman/Create.jsx` |
| `/admin/hubs` | [pages/admin-hub-index.md](pages/admin-hub-index.md) | `Pages/Admin/Hub/Index.jsx` |
| `/admin/merchant/index` | [pages/admin-merchant-index.md](pages/admin-merchant-index.md) | `Pages/Admin/Merchant/Index.jsx` |

### Warehouse (WMS)

All 10 WMS index pages share `Components/wms/ListPage.jsx` and the
`RendersInertiaIndex` PHP trait. See the combined doc for details:

| URL | Doc |
|---|---|
| `/admin/wms`              | [pages/admin-wms.md#dashboard](pages/admin-wms.md#dashboard) |
| `/admin/wms/products`     | [pages/admin-wms.md#products](pages/admin-wms.md#products) |
| `/admin/wms/stock`        | [pages/admin-wms.md#stock](pages/admin-wms.md#stock) |
| `/admin/wms/locations`    | [pages/admin-wms.md#locations](pages/admin-wms.md#locations) |
| `/admin/wms/grn`          | [pages/admin-wms.md#grn](pages/admin-wms.md#grn) |
| `/admin/wms/fulfillment`  | [pages/admin-wms.md#fulfillment](pages/admin-wms.md#fulfillment) |
| `/admin/wms/outbound`     | [pages/admin-wms.md#outbound](pages/admin-wms.md#outbound) |
| `/admin/wms/adjustments`  | [pages/admin-wms.md#adjustments](pages/admin-wms.md#adjustments) |
| `/admin/wms/cycle-counts` | [pages/admin-wms.md#cycle-counts](pages/admin-wms.md#cycle-counts) |
| `/admin/wms/damage`       | [pages/admin-wms.md#damage](pages/admin-wms.md#damage) |

## Conventions

- Every page wraps its content in `AdminLayout` (or `MerchantLayout` on the
  merchant side) so the sidebar / topbar are always rendered.
- Controllers pass a `t` map (Laravel `__()` calls flattened to keys) so the
  React side never needs lang files. New keys go in the controller's private
  `*Labels()` helper.
- Money values render through a local `<Money>` helper (tabular-nums,
  currency prefix). Don't roll your own.
- Translations beyond `t` (the sidebar nav, dropdowns) live in
  `resources/js/lib/i18n.js` and use `useT()`.
- Routes are reached via `safeRoute('name')` which wraps `window.route()`
  from Ziggy and returns `'#'` if Ziggy doesn't know the name.

## Building

```bash
npm install        # once
npm run build      # builds public/build/manifest.json + assets
```

The blade root view (`resources/views/admin/app.blade.php` or
`merchant/app.blade.php`) reads the manifest directly via
`file_get_contents(public_path('build/manifest.json'))` — it does **not**
use the `@vite()` directive because Stancl Tenancy's `asset_helper_tenancy`
rewrites `@vite` URLs to `/tenancy/assets/…` which 404s.
