<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `company_id` to `parcel_events` so per-tenant filtering can be enforced
 * at the model layer instead of relying on every caller to remember the right
 * join. Backfilled from the parent parcel.
 *
 * Why this exists:
 *   The Parcel model has a tenant global scope that filters by company_id
 *   automatically. parcel_events did NOT carry its own company_id, so any
 *   raw `DB::table('parcel_events')` query (and even `ParcelEvent::query()`)
 *   returned events from every tenant. The Performance Dashboard tripped on
 *   this — see the commit history for the inline-fix in that module.
 *
 *   With this migration + the matching `tenant` global scope on the
 *   ParcelEvent model (and a `creating` hook to auto-populate the column),
 *   isolation is enforced uniformly across the codebase. Future raw queries
 *   still bypass Eloquent scopes, but at least the data is *queryable* by
 *   company_id without a join.
 *
 * Safety notes:
 *   - Column is added nullable on purpose. Some rows may be orphans (parent
 *     parcel was soft-deleted or hard-deleted), and we don't want the backfill
 *     to fail the migration on those edge cases. The global scope treats a
 *     null company_id as "matches no tenant", so orphans are effectively
 *     invisible — which is the desired behaviour for stale data.
 *   - Backfill runs as a single UPDATE ... JOIN. Tested fine on the largest
 *     tenant in this codebase (low tens of thousands of rows). For very large
 *     deployments (millions of rows) consider chunking — convert the UPDATE
 *     into a loop over `parcels` in batches of 50k.
 *   - Idempotent. Safe to re-run: column add is guarded, backfill skips rows
 *     already populated.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('parcel_events')) {
            return;
        }

        if (! Schema::hasColumn('parcel_events', 'company_id')) {
            Schema::table('parcel_events', function (Blueprint $table) {
                // Match the column type / nullability of `parcels.company_id`.
                // Foreign-key constraint deliberately omitted — the rest of
                // this codebase uses soft references on company_id elsewhere
                // (users, roles, etc.) and adding FK now would risk failing
                // the migration on any orphaned event.
                $table->unsignedBigInteger('company_id')->nullable()->after('parcel_id');
                $table->index('company_id', 'parcel_events_company_id_index');
            });
        }

        // Backfill from the parent parcel. Skip rows already populated so
        // re-runs are cheap. The JOIN form is portable across MySQL 5.7 / 8.x.
        DB::statement(<<<'SQL'
            UPDATE parcel_events AS e
              INNER JOIN parcels AS p ON p.id = e.parcel_id
            SET e.company_id = p.company_id
            WHERE e.company_id IS NULL
        SQL);
    }

    public function down(): void
    {
        if (Schema::hasColumn('parcel_events', 'company_id')) {
            Schema::table('parcel_events', function (Blueprint $table) {
                // Dropping the index first is required on some MySQL versions
                // before the column can go.
                $table->dropIndex('parcel_events_company_id_index');
                $table->dropColumn('company_id');
            });
        }
    }
};
