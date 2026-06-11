<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Logestechs (and any future "multi-tenant target") routes a shipment to a
 * specific account on the receiving side. The admin picks the target's
 * company id at assign time; we store it on the parcels_3pl row alongside
 * the AWB so the per-row context is preserved for audits and re-sends.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parcels_3pl')) {
            return;
        }
        Schema::table('parcels_3pl', function (Blueprint $table) {
            if (! Schema::hasColumn('parcels_3pl', 'target_company_id')) {
                $table->string('target_company_id', 64)->nullable()->after('parcel_3pl_name');
            }
        });
        // Separate, idempotent — composite index on (parcel_3pl_name, target_company_id)
        // hits the utf8mb4 key-length cap, so use a single-column index.
        $hasIdx = collect(DB::select(
            "SHOW INDEX FROM parcels_3pl WHERE Key_name = ?",
            ['parcels_3pl_target_company_id_idx']
        ))->isNotEmpty();
        if (! $hasIdx) {
            Schema::table('parcels_3pl', function (Blueprint $table) {
                $table->index('target_company_id', 'parcels_3pl_target_company_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('parcels_3pl')) {
            return;
        }
        Schema::table('parcels_3pl', function (Blueprint $table) {
            if (Schema::hasColumn('parcels_3pl', 'target_company_id')) {
                $table->dropIndex('parcels_3pl_target_company_id_idx');
                $table->dropColumn('target_company_id');
            }
        });
    }
};
