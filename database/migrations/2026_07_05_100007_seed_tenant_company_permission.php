<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent seed of the tenant-side `company` permission attribute.
 * Gates the child-company (reseller/white-label) UI at /admin/child-companies.
 *
 * Not auto-granted to any tenant role — super-admin flips it per-role
 * via /admin/roles/edit/{id}, same pattern as performance_dashboard_read.
 *
 * The super_admin_permissions table has its own 'company' row for the
 * platform-owner UI at /super-admin/company; the two are independent.
 * Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        if (DB::table('permissions')->where('attribute', 'company')->exists()) {
            return;
        }
        DB::table('permissions')->insert([
            'attribute'  => 'company',
            'keywords'   => json_encode([
                'read'   => 'company_read',
                'create' => 'company_create',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('attribute', 'company')->delete();
        }
    }
};
