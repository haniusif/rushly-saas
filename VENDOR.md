# Rushly SaaS — Vendor Plan & Sub-Accounts

The **Vendor** plan and the **Sub-accounts** flow (`/admin/child-companies`) let a tenant admin create additional full tenants underneath their own account — a reseller / white-label mode. This document is the reference for how the two pieces fit together.

Companion docs: [`ARCHITECTURE.md`](ARCHITECTURE.md) (codebase layout), [`INTEGRATIONS.md`](INTEGRATIONS.md) (external systems).

---

## 1. Concept

Two things ship together:

1. **The Vendor plan** — a Plan row seeded via migration with a specific cap set (5 users, 100 drivers, 5000 parcels, 30 days) and a specific module set (`dashboard`, `delivery_man`, `tms`, `reports`). Intended sizing for a small vendor tenant.
2. **The Sub-accounts flow** — a tenant-context UI (`/admin/child-companies`) that lets any tenant admin holding the `company_create` permission spin up a brand-new tenant (own subdomain, own owner user, own subscription) linked back to their tenant via `general_settings.parent_company_id`.

Each sub-account is a **full tenant** in every respect — its own subdomain, its own subscription row, its own owner user, its own admin panel. It is not a sub-branch, sub-user, or child-record within the parent. The only linkage is `parent_company_id` so the parent can list their sub-accounts and (eventually) be billed for them.

**Product decision**: the Sub-accounts flow currently allows only the Vendor plan (enforced both client-side and server-side in `ChildCompanyController`). If other tiers are ever needed for sub-accounts, relax the filter in `create()` + `store()`.

---

## 2. Data model

| Column | Table | Purpose |
|---|---|---|
| `plans.user_count` | `plans` | Max staff-user seats. `NULL` = unlimited (backwards-compat for pre-2026-07 plans). |
| `subscriptions.user_count` | `subscriptions` | Copied from `plan.user_count` at subscription create/switch time. This is what the runtime seat-cap check reads (mirrors how `parcel_count` / `deliveryman_count` work). |
| `general_settings.parent_company_id` | `general_settings` | Nullable FK to `general_settings.id`. Set only for sub-accounts. `NULL` = super-admin-created tenant (the default path). |
| `permissions.attribute = 'company'` | `permissions` (tenant catalog) | Grantable tenant-side permission with `company_read` + `company_create` keys. Distinct from the super-admin `super_admin_permissions.company` row — both strings coexist. |
| `permissions.attribute = 'tms'` | `permissions` (tenant catalog) | Gates the TMS module per-plan. Registered by the Phase-1 seeder + backfilled to every user/role that already had `delivery_man_read` (the old TMS gate). |

**Migrations that landed this** (2026-07-05, commit `a92d561`):

```
2026_07_05_100001_add_user_count_to_plans.php
2026_07_05_100002_add_parent_company_id_to_general_settings.php
2026_07_05_100003_seed_tms_permissions.php
2026_07_05_100004_add_user_count_to_subscriptions.php
2026_07_05_100005_seed_vendor_plan.php
2026_07_05_100006_backfill_tms_permissions.php
2026_07_05_100007_seed_tenant_company_permission.php
```

All idempotent — safe to re-run against existing tenants.

---

## 3. Grant path

`company_create` is **not** auto-granted to any tenant role. Flow:

1. Super-admin flips `company_create` on for a tenant role via `/admin/roles/edit/{id}`.
2. The role is assigned to the target admin user.
3. The user's next request sees the sidebar entry "Sub-accounts" (Users Management group) and can visit `/admin/child-companies`.

This is the same pattern used by `performance_dashboard_read`, `wms_manage`, `abnormal_manage`.

For direct per-user grants (bypassing roles) — write `company_create` into `users.permissions` JSON array. Two users on the dev DB currently hold this:

| user_id | email | tenant |
|---|---|---|
| 78 | `admin@navix.com.sa` | NAVIX (`company_id=12`) |
| 14 | `admin@rushly-logistic.com` | Rushly operational (`company_id=9`) |

The two SUPER_ADMINs (`id=1`, `id=43`) also hold it, granted by the super-admin permission catalog.

---

## 4. Routes

Registered in `routes/web.php` inside the `if ($domain) :` tenant block — see [routes-tenant-domain-gate memory note](.claude/projects/-var-www-rushly-saas/memory/routes-tenant-domain-gate.md) for why CLI `route:list` won't show them without a host override.

| Method | URI | Name | Middleware |
|---|---|---|---|
| GET | `admin/child-companies` | `child-companies.index` | `hasPermission:company_create` |
| GET | `admin/child-companies/create` | `child-companies.create` | `hasPermission:company_create` |
| POST | `admin/child-companies` | `child-companies.store` | `hasPermission:company_create` |

All three routes are behind the same permission gate. Read + write are collapsed for now; if we later need read-only access without create rights, split them.

---

## 5. UI

**List** — `/admin/child-companies`:
- Table of your sub-accounts (name, portal link + copy button, email, phone, status, created date).
- Portal column exposes `https://{subdomain}.{APP_HOST}/admin/login` — clickable + one-click copy-to-clipboard via `navigator.clipboard` (fallback to `<textarea>` + `execCommand` for non-HTTPS dev).
- "Add new" button top-right.

**Create** — `/admin/child-companies/create`:
- Two-card form: Company block (name, subdomain, currency, plan, address) + Owner block (name, email, password, mobile).
- Subdomain preview shows the full `{sub}.{APP_HOST}` it will resolve to.
- Currency is a typeahead (`SearchableCurrencySelect` — filters by name/code/symbol; ~85-line inline component, no new deps).
- Plan is restricted to Vendor by product decision (see §1).
- Below the plan dropdown, the selected plan's caps show inline: e.g. "5 users · 100 drivers · 5000 parcels · 30 days".

**Sidebar entry** — `AdminLayout.jsx`, "Users Management" group, gated on `perm: 'company_create'`.

---

## 6. Under the hood

`ChildCompanyController::store` delegates to the same `CompanyRepository::store()` the super-admin panel uses — the only difference is a `$parentCompanyId` argument passed through:

```php
$this->repo->store($request, settings()->id);
```

`CompanyRepository::store($request, $parentCompanyId = null)` → `company_create($request, null, $parentCompanyId)` writes `parent_company_id` on the new `general_settings` row. Everything downstream (Tenant + Domain + owner User + Subscription + `CompanyFrontendDataSeeder`) is identical to the super-admin path.

**No Stancl central/tenant context switching is needed.** The codebase runs single-DB (`config/tenancy.php:31` has `DatabaseTenancyBootstrapper` disabled) — tenant isolation is enforced at query time by `->companywise()` scopes reading `settings()->id`. Creating a tenant from inside a tenant is just inserting rows keyed on a new `company_id`; the writes land in the same MySQL.

**User seat cap** — enforced in tenant `UserController::store`:

```php
$seatCap = optional(settings()->subscription)->user_count;
if ($seatCap !== null && User::companywise()->count() >= (int) $seatCap) {
    abort with error;
}
```

`NULL` = unlimited (legacy plans). For Vendor, cap is 5 — the 6th user create fails.

**Vendor plan filter** — `create()` filters `Plan::where('name', 'Vendor')`; `store()` re-checks that `plan_id` belongs to an active Vendor plan before delegating, so a tampered POST can't pick another tier.

---

## 7. Tests

`tests/Feature/Companies/` (Phase-5 commit `fc794aa`, 7 tests / 22 assertions):

- `CompaniesTestCase.php` — hand-built sqlite schema (per [test-suite-manual-schema memory note](.claude/projects/-var-www-rushly-saas/memory/test-suite-manual-schema.md)) for general_settings, plans, subscriptions, uploads, sms_*, config, notification_settings.
- `CompanyRepositoryStoreTest.php`:
  - `company_create_writes_parent_company_id_when_provided`
  - `company_create_without_parent_leaves_parent_company_id_null`
  - `company_create_does_not_touch_parent_company_id_on_update` — renaming a child never re-parents it.
- `VendorPlanTest.php`:
  - `vendor_plan_seed_migration_creates_the_expected_row_and_is_idempotent` — runs `up()` twice, asserts exactly one row.
  - `plan_repository_persists_user_count_on_create_and_update`.
  - `plan_store_request_accepts_null_user_count` — legacy backwards-compat.
  - `plan_store_request_rejects_user_count_below_one` — `min:1` rule.

Run: `vendor/bin/phpunit tests/Feature/Companies/ --no-coverage`.

---

## 8. Known limitations

- **No parent-pays-for-children billing.** Each child gets its own `subscriptions` row against the Vendor plan; the parent handles payment out-of-band. When we introduce parent-billed sub-accounts, the seam is `CompanyRepository::store()` around the subscription create — a `TODO(billing)` comment marks the spot.
- **No white-label inheritance.** Child doesn't inherit the parent's logo, colors, or branding — it gets the platform defaults from `CompanyFrontendDataSeeder`. Add later if needed.
- **No cross-tenant aggregate reporting.** Parent can list children but can't see aggregate parcels / revenue across all children in one view.
- **Vendor plan is the only option.** By product decision — see §1 for how to relax.
- **Sub-account creation currently blocks on department/designation.** The form doesn't collect them (nullable in the users table), so it works, but the child's owner user starts with `NULL` department/designation. The child admin fixes it up post-creation.

---

## 9. Knowledge Base

The end-user documentation for this feature lives at `/admin/knowledge-base/billing` → **Sub-accounts (Vendor plan)** card. Source: `lang/{en,ar}/kb_billing.php` under the `child-companies` key. Sidebar screenshot for the KB page goes to `public/images/kb/billing/child-companies.png` (auto-created on first upload via the built-in upload button, gated on `knowledge_base_update`).
