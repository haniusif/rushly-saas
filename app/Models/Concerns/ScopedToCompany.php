<?php

namespace App\Models\Concerns;

use App\Enums\UserType;
use Illuminate\Support\Facades\Auth;

/**
 * Constrains every Eloquent query on the model to a single company_id.
 *
 * rushly-saas is single-database multi-tenancy: DatabaseTenancyBootstrapper is
 * disabled in config/tenancy.php, so every tenant's rows share one database and
 * company_id is the ONLY thing separating them. A query that forgets it returns
 * other tenants' data.
 *
 * Resolution order for the current company:
 *
 *   1. tenant()->company_id     — set by InitializeTenancyByDomain on web routes.
 *   2. Auth::user()->company_id — the fallback that makes this work on /api.
 *      routes/api.php is registered with only the `api` middleware group (see
 *      RouteServiceProvider), so NO tenancy middleware runs and tenant() is
 *      always null there. Without this branch every mobile-app API request was
 *      unscoped and returned all companies' rows.
 *
 * Contexts that are deliberately left unscoped:
 *
 *   - SUPER_ADMIN — cross-tenant access by design.
 *   - CLI (artisan, queue workers, scheduler, tinker) — no tenant and no auth
 *     user. Clamping these to some default company would be worse than not
 *     scoping: jobs legitimately sweep across tenants.
 *   - Unauthenticated HTTP — the public tracking endpoint and the Salla / Zid /
 *     WooCommerce webhooks have no user and no tenant, and already constrain
 *     company_id explicitly (or bypass scopes outright). Failing closed here
 *     would break those live integrations.
 *
 * An authenticated user with no usable company_id IS failed closed: that is a
 * broken account rather than a legitimate cross-tenant caller, and returning
 * every tenant's rows is the wrong answer.
 *
 * Escape hatch: Model::withoutGlobalScope('tenant')->... — the scope keeps the
 * name 'tenant' so existing call sites that opt out keep working.
 */
trait ScopedToCompany
{
    public static function bootScopedToCompany(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            // Super-admins have explicit cross-tenant authority.
            if (Auth::check()
                && (int) Auth::user()->user_type === UserType::SUPER_ADMIN) {
                return;
            }

            $companyId = static::currentCompanyId();

            if ($companyId !== null) {
                $query->where((new static())->getTable() . '.company_id', $companyId);
                return;
            }

            // No company context at all. Unauthenticated callers and CLI are
            // expected here and scope themselves; an authenticated one is not.
            if (Auth::check()) {
                $query->whereRaw('1 = 0');
            }
        });
    }

    /**
     * The company_id the current caller is confined to, or null when there is
     * no company context to derive (CLI, or an unauthenticated request).
     */
    protected static function currentCompanyId(): ?int
    {
        if (function_exists('tenant') && tenant() && tenant()->company_id) {
            return (int) tenant()->company_id;
        }

        if (Auth::check() && Auth::user()->company_id) {
            return (int) Auth::user()->company_id;
        }

        return null;
    }
}
