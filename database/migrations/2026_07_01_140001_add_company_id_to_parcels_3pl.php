<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 — close the multi-tenant leak on parcels_3pl (documented in
 * 3PL.md issue #3). This table has been growing since day one without a
 * `company_id` column; every row was implicitly cross-tenant.
 *
 * Backfill strategy: pull company_id from the linked parcel. When a
 * Parcels_3pl row's `parcel_id` doesn't resolve (deleted parcel), we
 * leave it null — the row is orphaned and shouldn't drive any
 * downstream decisions anyway.
 *
 * Idempotent — dev/staging DBs may already have the column from an
 * out-of-band alter; migration checks before adding.
 *
 * NOTE: a composite unique on (parcel_id, parcel_3pl_name, awb_number)
 * was originally planned to dedupe retry-assign rows, but MyISAM /
 * older MySQL key-length limits (1000 bytes on utf8mb4 columns of the
 * width parcels_3pl uses) reject the index. Deferred to a follow-up:
 * either shorten the columns first, or use an index prefix. Duplicate
 * rows are a data-quality nit; the tenant-scope column is the actual
 * security fix.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('parcels_3pl', 'company_id')) {
            Schema::table('parcels_3pl', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->index('company_id', 'parcels_3pl_company_id_idx');
            });
        }

        // Backfill from parcels. MySQL: UPDATE ... JOIN syntax.
        // SQLite (tests): fall back to correlated subquery.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement(
                'UPDATE parcels_3pl p3
                    JOIN parcels p ON p.id = p3.parcel_id
                 SET p3.company_id = p.company_id
                 WHERE p3.company_id IS NULL'
            );
        } else {
            DB::statement(
                'UPDATE parcels_3pl
                    SET company_id = (SELECT company_id FROM parcels WHERE parcels.id = parcels_3pl.parcel_id)
                 WHERE company_id IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::table('parcels_3pl', function (Blueprint $table) {
            if ($this->hasIndex('parcels_3pl', 'parcels_3pl_company_id_idx')) {
                $table->dropIndex('parcels_3pl_company_id_idx');
            }
            if (Schema::hasColumn('parcels_3pl', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $indexName],
            );
            return count($rows) > 0;
        }
        $rows = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name = ?", [$indexName]);
        return count($rows) > 0;
    }
};
