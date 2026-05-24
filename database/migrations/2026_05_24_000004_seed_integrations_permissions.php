<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent backfill for the `integrations_read` / `integrations_update`
 * permissions that were added alongside the new Integrations page.
 *
 * Without this, existing installs would only get the new permissions via
 * `php artisan db:seed --class=PermissionSeeder`, which would error out
 * because PermissionSeeder rebuilds the table from scratch and assumes a
 * fresh state. This migration is the safe path: it adds the missing rows
 * and grants the new permissions to any Role / User whose `permissions`
 * JSON array already contains the matching `general_settings_*` entries.
 *
 * Runs once. Safe to re-run (the inserts and JSON appends are guarded).
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
            DB::table('permissions')->where('attribute', 'integrations')->delete();
        }
        if (Schema::hasTable('super_admin_permissions')) {
            DB::table('super_admin_permissions')->where('attribute', 'integrations')->delete();
        }
        // Don't strip the keys back out of role/user JSON columns on down —
        // that's destructive and the column might have been hand-edited.
    }

    private function insertPermissionRow(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $exists = DB::table('permissions')->where('attribute', 'integrations')->exists();
        if ($exists) {
            return;
        }
        DB::table('permissions')->insert([
            'attribute'  => 'integrations',
            'keywords'   => json_encode([
                'read'   => 'integrations_read',
                'update' => 'integrations_update',
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
        $exists = DB::table('super_admin_permissions')->where('attribute', 'integrations')->exists();
        if ($exists) {
            return;
        }
        DB::table('super_admin_permissions')->insert([
            'attribute'  => 'integrations',
            'keywords'   => json_encode([
                'read'   => 'integrations_read',
                'update' => 'integrations_update',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function backfillRolesAndUsers(): void
    {
        // Strategy: anyone who currently has `general_settings_read` already
        // has access to the Settings area, so it's safe to also grant
        // `integrations_read` to them. Anyone with `general_settings_update`
        // gets `integrations_update` too. Super Admin slug always gets both.

        $this->grantIfHas('roles', 'general_settings_read',   'integrations_read');
        $this->grantIfHas('roles', 'general_settings_update', 'integrations_update');
        $this->grantIfHas('users', 'general_settings_read',   'integrations_read');
        $this->grantIfHas('users', 'general_settings_update', 'integrations_update');

        // Belt + braces: ensure the Super Admin role has both, regardless of
        // whether the legacy seeder ever included general_settings_update.
        if (Schema::hasTable('roles')) {
            foreach (DB::table('roles')->where('slug', 'super-admin')->get() as $role) {
                $perms = json_decode($role->permissions ?? '[]', true) ?: [];
                $perms = array_values(array_unique(array_merge($perms, [
                    'integrations_read', 'integrations_update',
                ])));
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode($perms),
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
        // `permissions` is a JSON column cast to array by the model. At the
        // SQL level it's a TEXT/JSON column holding `["foo","bar",...]`.
        // We scan in PHP to keep this portable across MySQL versions.
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
