<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent backfill for the `tms` permission attribute so existing
 * installs pick up the new module key without needing to re-run
 * PermissionSeeder (which is not idempotent — it inserts duplicate rows).
 *
 * No role/user grants are backfilled here. TMS is currently ungated at
 * the route/middleware level; the Phase-3 step that adds
 * `hasPermission:tms_read` middleware is where the grant-backfill
 * decision belongs. Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        if (DB::table('permissions')->where('attribute', 'tms')->exists()) {
            return;
        }
        DB::table('permissions')->insert([
            'attribute'  => 'tms',
            'keywords'   => json_encode([
                'read'   => 'tms_read',
                'create' => 'tms_create',
                'update' => 'tms_update',
                'delete' => 'tms_delete',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('attribute', 'tms')->delete();
        }
    }
};
