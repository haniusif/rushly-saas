<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Dashboard Phase 4 — data instrumentation.
 *
 * Adds nullable columns that the dashboard's proxy KPIs can upgrade to real
 * metrics over time. **No backfill happens here** — the parcels table is
 * hot in this codebase and an UPDATE-everything would lock it. A separate
 * artisan command (performance:backfill) does that work in chunks.
 *
 * New columns:
 *   parcels.expected_delivery_at  TIMESTAMP NULL  — target SLA timestamp captured at create time
 *   parcels.distance_m            INT UNSIGNED NULL — straight-line metres pickup → customer (haversine)
 *   delivery_man.last_seen_at     TIMESTAMP NULL  — bumped by middleware on any authenticated driver request
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('parcels')) {
            Schema::table('parcels', function (Blueprint $t) {
                if (! Schema::hasColumn('parcels', 'expected_delivery_at')) {
                    $t->timestamp('expected_delivery_at')->nullable()->after('delivery_date');
                }
                if (! Schema::hasColumn('parcels', 'distance_m')) {
                    $t->unsignedInteger('distance_m')->nullable()->after('expected_delivery_at');
                    $t->index('distance_m', 'parcels_distance_m_idx');
                }
            });
        }

        if (Schema::hasTable('delivery_man')) {
            Schema::table('delivery_man', function (Blueprint $t) {
                if (! Schema::hasColumn('delivery_man', 'last_seen_at')) {
                    $t->timestamp('last_seen_at')->nullable()->after('updated_at');
                    $t->index('last_seen_at', 'delivery_man_last_seen_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parcels')) {
            Schema::table('parcels', function (Blueprint $t) {
                if (Schema::hasColumn('parcels', 'distance_m')) {
                    $t->dropIndex('parcels_distance_m_idx');
                    $t->dropColumn('distance_m');
                }
                if (Schema::hasColumn('parcels', 'expected_delivery_at')) {
                    $t->dropColumn('expected_delivery_at');
                }
            });
        }

        if (Schema::hasTable('delivery_man')) {
            Schema::table('delivery_man', function (Blueprint $t) {
                if (Schema::hasColumn('delivery_man', 'last_seen_at')) {
                    $t->dropIndex('delivery_man_last_seen_idx');
                    $t->dropColumn('last_seen_at');
                }
            });
        }
    }
};
