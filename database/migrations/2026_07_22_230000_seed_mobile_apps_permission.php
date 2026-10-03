<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent backfill for the `mobile_apps_read` permission that gates the
 * new /settings/mobile-apps page. Mirrors the same pattern used by the
 * integrations_permissions and tour_manage_permission migrations.
 *
 * Grants `mobile_apps_read` to any Role / User whose `permissions` JSON
 * already contains `general_settings_read` — those principals can already
 * reach the Settings hub, so this is safe to broadcast to them.
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
            DB::table('permissions')->where('attribute', 'mobile_apps')->delete();
        }
        if (Schema::hasTable('super_admin_permissions')) {
            DB::table('super_admin_permissions')->where('attribute', 'mobile_apps')->delete();
        }
    }

    private function insertPermissionRow(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        if (DB::table('permissions')->where('attribute', 'mobile_apps')->exists()) {
            return;
        }
        DB::table('permissions')->insert([
            'attribute'  => 'mobile_apps',
            'keywords'   => json_encode(['read' => 'mobile_apps_read']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSuperAdminPermissionRow(): void
    {
        if (! Schema::hasTable('super_admin_permissions')) {
            return;
        }
        if (DB::table('super_admin_permissions')->where('attribute', 'mobile_apps')->exists()) {
            return;
        }
        DB::table('super_admin_permissions')->insert([
            'attribute'  => 'mobile_apps',
            'keywords'   => json_encode(['read' => 'mobile_apps_read']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function backfillRolesAndUsers(): void
    {
        $this->grantIfHas('roles', 'general_settings_read', 'mobile_apps_read');
        $this->grantIfHas('users', 'general_settings_read', 'mobile_apps_read');

        if (Schema::hasTable('roles')) {
            foreach (DB::table('roles')->where('slug', 'super-admin')->get() as $role) {
                $perms = json_decode($role->permissions ?? '[]', true) ?: [];
                if (! in_array('mobile_apps_read', $perms, true)) {
                    $perms[] = 'mobile_apps_read';
                    DB::table('roles')->where('id', $role->id)->update([
                        'permissions' => json_encode(array_values(array_unique($perms))),
                        'updated_at'  => now(),
                    ]);
                }
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
