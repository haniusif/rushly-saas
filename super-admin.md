# /super-admin/* routes audit

Snapshot: 2026-07-17. All routes live under the `super-admin` prefix in
`routes/superadmin.php` (and mirrored in the tenant-gated block of
`routes/web.php`). "UI stack" = what the controller returns.

| # | Method | URL                                                        | Route name                                | Controller                                 | UI stack             | Notes |
|---|--------|------------------------------------------------------------|-------------------------------------------|--------------------------------------------|----------------------|-------|
| 1 | GET    | `/super-admin/business-logic/fulfillment-defaults`         | `super-admin.business-logic.fulfillment-defaults.index` | `FulfillmentDefaultsController@index`      | **Inertia**          | `SuperAdmin/BusinessLogic/FulfillmentDefaults/Index`. Feature-flag gated. |
| 2 | POST   | `/super-admin/business-logic/fulfillment-defaults/global`  | `…update-global`                          | `FulfillmentDefaultsController@updateGlobal` | (action)             | Form POST. |
| 3 | POST   | `/super-admin/business-logic/fulfillment-defaults/overrides` | `…store-override`                       | `FulfillmentDefaultsController@storeOverride` | (action)             | Form POST. |
| 4 | DELETE | `/super-admin/business-logic/fulfillment-defaults/overrides/{id}` | `…destroy-override`                | `FulfillmentDefaultsController@destroyOverride` | (action)             | Form DELETE. |
| 5 | GET    | `/super-admin/plan/`                                       | `plan.index`                              | `PlanController@index`                     | **Inertia**          | `Admin/Superadmin/Plan/Index`. Ported `b2c064e` (2026-07-17). |
| 6 | GET    | `/super-admin/plan/create`                                 | `plan.create`                             | `PlanController@create`                    | **Legacy Blade**     | `resources/views/backend/super-admin/plan/create.blade.php` (185 lines). |
| 7 | POST   | `/super-admin/plan/store`                                  | `plan.store`                              | `PlanController@store`                     | (action)             | Form POST. |
| 8 | GET    | `/super-admin/plan/edit/{id}`                              | `plan.edit`                               | `PlanController@edit`                      | **Legacy Blade**     | `resources/views/backend/super-admin/plan/edit.blade.php` (187 lines). |
| 9 | PUT    | `/super-admin/plan/update`                                 | `plan.update`                             | `PlanController@update`                    | (action)             | Form PUT. |
| 10 | DELETE| `/super-admin/plan/delete/{id}`                            | `plan.delete`                             | `PlanController@delete`                    | (action)             | Form DELETE. |
| 11 | GET   | `/super-admin/plan/modules/{plan_id}`                      | `plan.modules.view`                       | `PlanController@modulesView`               | **Legacy Blade**     | `plan_modules.blade.php` (22 lines) — was a Bootstrap-modal partial. Effectively dead since the new Inertia Plan/Index page shows modules in an inline popover. |
| 12 | GET   | `/super-admin/subscription/history`                        | `subscription.history`                    | `PlanController@subscriptionHistory`       | **Inertia**          | `Admin/Subscription/History`. |
| 13 | GET   | `/super-admin/company/`                                    | `company.index`                           | `CompanyController@index`                  | **Inertia**          | `Admin/Superadmin/Company/Index`. Ported `67a1eaa` (2026-07-17). |
| 14 | GET   | `/super-admin/company/create`                              | `company.create`                          | `CompanyController@create`                 | **Legacy Blade**     | `company/create.blade.php`. |
| 15 | POST  | `/super-admin/company/store`                               | `company.store`                           | `CompanyController@store`                  | (action)             | Form POST. |
| 16 | GET   | `/super-admin/company/edit/{id}`                           | `company.edit`                            | `CompanyController@edit`                   | **Legacy Blade**     | `company/edit.blade.php` (258 lines). |
| 17 | PUT   | `/super-admin/company/update`                              | `company.update`                          | `CompanyController@update`                 | (action)             | Form PUT. |
| 18 | DELETE| `/super-admin/company/delete/{id}`                         | `company.delete`                          | `CompanyController@delete`                 | (action)             | Form DELETE. |
| 19 | GET   | `/super-admin/company/subscription/switch/{id}`            | `company.subscription.switch`             | `CompanyController@switchSubscription`     | **Legacy Blade**     | `switch_subscription.blade.php` (33 lines) — was a Bootstrap-modal partial. |
| 20 | POST  | `/super-admin/company/subscription/switch/store`           | `company.subscription.switch.store`       | `CompanyController@switchSubscriptionStore` | (action)             | Form POST. |

## UI-page count (GET only)

- **Total GET pages:** 10
- **Already Inertia:** 4 → `fulfillment-defaults`, `plan/`, `company/`, `subscription/history`
- **Legacy Blade left:** 6 → `plan/create`, `plan/edit`, `plan/modules/{id}`, `company/create`, `company/edit/{id}`, `company/subscription/switch/{id}`
  - Of those, `plan/modules/{id}` is effectively dead (replaced by an inline popover on the ported Plan index).

## Port plan

Next porting passes (in order, matches how the pages are entered from
the workflow):

1. `plan/create` + `plan/edit` — share the same form shape (name, price,
   parcel/deliveryman/days counts, position, status, description,
   module tri-state grid). Build one shared `Admin/Superadmin/Plan/Form.jsx`
   component; both pages render it with different initial props.
2. `company/create` + `company/edit` — two-panel form (Company info + owner
   User info) with domain-suffix widget, currency select, plan select,
   logo/avatar upload. Same shared-form pattern as `plan`.
3. `company/subscription/switch/{id}` — inline into the ported
   `Admin/Superadmin/Company/Index` as a dialog instead of its own page
   (matches the existing "Subscribe Now" button on the index).
4. `plan/modules/{id}` — leave the route in place but remove any menu
   entries pointing at it; the new Plan/Index popover is the canonical
   surface.

## Related pieces already Inertia (context)

- Super-admin dashboard `/dashboard` (Blade `backend/super-admin/dashboard`
  when `user_type=SUPER_ADMIN`) is **not** under `/super-admin/*` but
  is the same audience — still legacy Blade. Consider porting when the
  super-admin bulk pass finishes.
- Super-admin `/summary` is Inertia (`Admin/Superadmin/Summary/Index`).
- Sidebar for super-admin uses `SUPER_NAV` in `AdminLayout.jsx`.
