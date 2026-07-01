<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the tour_manage permission for existing installs.
 * Grants it to anyone who already has general_settings_update (same
 * pattern used for the knowledge_base_update backfill).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Grant to super admin role JSON permissions column (if any) — mirrors
        // KB permission migration. We only patch users, since the permission
        // seeder registers the definition once run.
        $users = DB::table('users')
            ->whereNotNull('permissions')
            ->select('id', 'permissions', 'user_type')
            ->get();

        foreach ($users as $u) {
            $perms = @json_decode($u->permissions, true);
            if (! is_array($perms)) continue;

            $isSuper = (int) $u->user_type === 6;
            $eligible = $isSuper || in_array('general_settings_update', $perms, true);
            if (! $eligible) continue;

            if (! in_array('tour_manage', $perms, true)) {
                $perms[] = 'tour_manage';
                DB::table('users')->where('id', $u->id)->update([
                    'permissions' => json_encode(array_values(array_unique($perms))),
                ]);
            }
        }
    }

    public function down(): void
    {
        $users = DB::table('users')->whereNotNull('permissions')->select('id', 'permissions')->get();
        foreach ($users as $u) {
            $perms = @json_decode($u->permissions, true);
            if (! is_array($perms)) continue;
            $filtered = array_values(array_filter($perms, fn ($p) => $p !== 'tour_manage'));
            if (count($filtered) !== count($perms)) {
                DB::table('users')->where('id', $u->id)->update(['permissions' => json_encode($filtered)]);
            }
        }
    }
};
