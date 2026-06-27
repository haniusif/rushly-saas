<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent backfill for the `knowledge_base_update` permission that
 * gates the per-sub-page screenshot upload / delete actions on
 * /admin/knowledge-base (and the equivalent WMS KB endpoints).
 *
 * Reading the KB stays open to any logged-in admin — only writes are
 * gated. Existing admins who currently have `general_settings_update`
 * are assumed to be platform operators and get the new permission too,
 * matching the convention used by the integrations permission seeder.
 *
 * Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->insertPermissionRow();
        $this->insertSuperAdminPermissionRow();
        $this->backfillRolesAndUsers();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('attribute', 'knowledge_base')->delete();
        }
        if (Schema::hasTable('super_admin_permissions')) {
            DB::table('super_admin_permissions')->where('attribute', 'knowledge_base')->delete();
        }
        // Leave the JSON keys on roles/users alone — stripping them is
        // destructive and the column may have been hand-edited.
    }

    private function insertPermissionRow(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        if (DB::table('permissions')->where('attribute', 'knowledge_base')->exists()) {
            return;
        }
        DB::table('permissions')->insert([
            'attribute'  => 'knowledge_base',
            'keywords'   => json_encode([
                'update' => 'knowledge_base_update',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSuperAdminPermissionRow(): void
    {
        if (! Schema::hasTable('super_admin_permissions')) {
            return;
        }
        if (DB::table('super_admin_permissions')->where('attribute', 'knowledge_base')->exists()) {
            return;
        }
        DB::table('super_admin_permissions')->insert([
            'attribute'  => 'knowledge_base',
            'keywords'   => json_encode([
                'update' => 'knowledge_base_update',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function backfillRolesAndUsers(): void
    {
        // Anyone who can already edit general settings is a platform operator
        // and should be able to manage KB screenshots too.
        $this->grantIfHas('roles', 'general_settings_update', 'knowledge_base_update');
        $this->grantIfHas('users', 'general_settings_update', 'knowledge_base_update');

        // Always grant to the Super Admin role regardless of legacy state.
        if (Schema::hasTable('roles')) {
            foreach (DB::table('roles')->where('slug', 'super-admin')->get() as $role) {
                $perms = json_decode($role->permissions ?? '[]', true) ?: [];
                if (in_array('knowledge_base_update', $perms, true)) {
                    continue;
                }
                $perms[] = 'knowledge_base_update';
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique($perms))),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    private function grantIfHas(string $table, string $existingPermission, string $newPermission): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $rows = DB::table($table)->whereNotNull('permissions')->get(['id', 'permissions']);
        foreach ($rows as $row) {
            $perms = json_decode($row->permissions ?? '[]', true);
            if (! is_array($perms)) {
                continue;
            }
            if (! in_array($existingPermission, $perms, true)) {
                continue;
            }
            if (in_array($newPermission, $perms, true)) {
                continue;
            }
            $perms[] = $newPermission;
            DB::table($table)->where('id', $row->id)->update([
                'permissions' => json_encode($perms),
                'updated_at'  => now(),
            ]);
        }
    }
};
