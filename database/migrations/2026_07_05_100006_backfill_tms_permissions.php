<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grants the new tms_* permissions to every role/user that currently
 * carries delivery_man_read. Before Phase 3 the TMS routes were gated
 * on delivery_man_read; adding tms_read as the new gate without this
 * backfill would silently lock out every existing driver-manager user.
 *
 * Also grants the tms_* set to the platform super-admin role.
 * Safe to re-run.
 */
return new class extends Migration {
    public function up(): void
    {
        $grants = ['tms_read', 'tms_create', 'tms_update', 'tms_delete'];

        $this->grantAllIfHas('roles', 'delivery_man_read', $grants);
        $this->grantAllIfHas('users', 'delivery_man_read', $grants);
        $this->grantAllToSuperAdminRole($grants);
    }

    public function down(): void
    {
        $keys = ['tms_read', 'tms_create', 'tms_update', 'tms_delete'];
        $this->stripFromColumn('roles', $keys);
        $this->stripFromColumn('users', $keys);
    }

    private function grantAllIfHas(string $table, string $existing, array $newPerms): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $rows = DB::table($table)->whereNotNull('permissions')->get(['id', 'permissions']);
        foreach ($rows as $row) {
            $perms = json_decode($row->permissions ?? '[]', true);
            if (! is_array($perms) || ! in_array($existing, $perms, true)) {
                continue;
            }
            $added = false;
            foreach ($newPerms as $p) {
                if (! in_array($p, $perms, true)) {
                    $perms[] = $p;
                    $added = true;
                }
            }
            if ($added) {
                DB::table($table)->where('id', $row->id)->update([
                    'permissions' => json_encode(array_values(array_unique($perms))),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    private function grantAllToSuperAdminRole(array $newPerms): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }
        foreach (DB::table('roles')->where('slug', 'super-admin')->get() as $role) {
            $perms = json_decode($role->permissions ?? '[]', true) ?: [];
            $added = false;
            foreach ($newPerms as $p) {
                if (! in_array($p, $perms, true)) {
                    $perms[] = $p;
                    $added = true;
                }
            }
            if ($added) {
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique($perms))),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    private function stripFromColumn(string $table, array $keys): void
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
            $filtered = array_values(array_filter($perms, fn ($p) => ! in_array($p, $keys, true)));
            if (count($filtered) !== count($perms)) {
                DB::table($table)->where('id', $row->id)->update([
                    'permissions' => json_encode($filtered),
                    'updated_at'  => now(),
                ]);
            }
        }
    }
};
