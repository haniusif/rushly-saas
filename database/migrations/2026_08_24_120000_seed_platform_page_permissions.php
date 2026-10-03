<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent seed for the three permissions that close off the platform-facing
 * landing pages from tenant (client) dashboards:
 *
 *   summary_read              -> /summary
 *   operations_dashboard_read -> /operations-dashboard
 *   knowledge_base_read       -> /admin/knowledge-base (index + {section})
 *
 * Those routes previously carried NO hasPermission middleware, so every
 * authenticated admin could reach them regardless of role. They now do.
 *
 * Rows are inserted into `super_admin_permissions` ONLY, never `permissions`.
 * RoleRepository::permissions() builds the tenant role editor from
 * `permissions` and the super-admin editor from `super_admin_permissions`, so
 * keeping these out of the former is what makes the pages un-grantable — and
 * therefore genuinely hidden — on a client dashboard.
 *
 * Enforcement reads users.permissions (PermissionCheckMiddleware), not the
 * role's, so the backfill grants to super-admin ROLES and to the USERS
 * attached to them. Mirrors 2026_07_22_230000_seed_mobile_apps_permission.
 */
return new class extends Migration {
    private const PERMS = [
        'summary'              => 'summary_read',
        'operations_dashboard' => 'operations_dashboard_read',
        'knowledge_base'       => 'knowledge_base_read',
    ];

    public function up(): void
    {
        $this->insertSuperAdminPermissionRows();
        $this->grantToSuperAdmins();
    }

    public function down(): void
    {
        if (Schema::hasTable('super_admin_permissions')) {
            // knowledge_base predates this migration and still carries its
            // `update` keyword, so drop only the key this migration added.
            DB::table('super_admin_permissions')
                ->whereIn('attribute', ['summary', 'operations_dashboard'])
                ->delete();

            $row = DB::table('super_admin_permissions')->where('attribute', 'knowledge_base')->first();
            if ($row) {
                $keywords = json_decode($row->keywords ?? '[]', true) ?: [];
                unset($keywords['read']);
                DB::table('super_admin_permissions')->where('attribute', 'knowledge_base')->update([
                    'keywords'   => json_encode($keywords),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->revokeFromAll(array_values(self::PERMS));
    }

    private function insertSuperAdminPermissionRows(): void
    {
        if (! Schema::hasTable('super_admin_permissions')) {
            return;
        }

        foreach (self::PERMS as $attribute => $keyword) {
            $existing = DB::table('super_admin_permissions')->where('attribute', $attribute)->first();

            if (! $existing) {
                DB::table('super_admin_permissions')->insert([
                    'attribute'  => $attribute,
                    'keywords'   => json_encode(['read' => $keyword]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Merge rather than overwrite: knowledge_base already holds `update`.
            $keywords = json_decode($existing->keywords ?? '[]', true) ?: [];
            if (($keywords['read'] ?? null) === $keyword) {
                continue;
            }
            $keywords['read'] = $keyword;
            DB::table('super_admin_permissions')->where('attribute', $attribute)->update([
                'keywords'   => json_encode($keywords),
                'updated_at' => now(),
            ]);
        }
    }

    private function grantToSuperAdmins(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleIds = DB::table('roles')->where('slug', 'super-admin')->pluck('id');
        if ($roleIds->isEmpty()) {
            return;
        }

        foreach ($roleIds as $roleId) {
            $this->grant('roles', $roleId);
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (DB::table('users')->whereIn('role_id', $roleIds)->pluck('id') as $userId) {
            $this->grant('users', $userId);
        }
    }

    private function grant(string $table, $id): void
    {
        $row = DB::table($table)->where('id', $id)->first(['id', 'permissions']);
        if (! $row) {
            return;
        }

        $perms = json_decode($row->permissions ?? '[]', true);
        if (! is_array($perms)) {
            $perms = [];
        }

        $before = count($perms);
        foreach (array_values(self::PERMS) as $keyword) {
            if (! in_array($keyword, $perms, true)) {
                $perms[] = $keyword;
            }
        }
        if (count($perms) === $before) {
            return;
        }

        DB::table($table)->where('id', $id)->update([
            'permissions' => json_encode(array_values(array_unique($perms))),
            'updated_at'  => now(),
        ]);
    }

    private function revokeFromAll(array $keywords): void
    {
        foreach (['roles', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull('permissions')->get(['id', 'permissions']) as $row) {
                $perms = json_decode($row->permissions ?? '[]', true);
                if (! is_array($perms)) {
                    continue;
                }
                $filtered = array_values(array_diff($perms, $keywords));
                if (count($filtered) === count($perms)) {
                    continue;
                }
                DB::table($table)->where('id', $row->id)->update([
                    'permissions' => json_encode($filtered),
                    'updated_at'  => now(),
                ]);
            }
        }
    }
};
