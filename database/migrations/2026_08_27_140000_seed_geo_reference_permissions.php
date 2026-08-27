<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permissions for the countries / cities / areas admin modules.
 *
 * Those 18 routes existed in routes/web.php with no controller behind them —
 * CountryController, CityController and AreaController were never written — so
 * every one of them threw, and `php artisan route:list` could not build at all.
 * The controllers and pages now exist and the routes are gated, which means the
 * permission keys have to exist too or nobody can reach the new screens.
 *
 * Granted to roles/users that already hold `general_settings_read` — the same
 * rule 2026_07_22_230000_seed_mobile_apps_permission used for the Settings-area
 * page it added. Those principals can already reach the Settings hub, so this
 * surfaces three more screens to exactly the people who administer settings.
 *
 * Rows go into BOTH catalogs: `permissions` (tenant role editor) and
 * `super_admin_permissions`. Unlike the platform pages gated in
 * 2026_08_24_120000, this data is genuinely tenant-facing — a courier admin
 * maintains their own city and area lists — so tenant role editors must be able
 * to grant it.
 *
 * NOTE the data itself is shared: countries/cities/areas have no company_id, so
 * every tenant reads the same rows and an edit is visible to all of them. The
 * screens carry a standing notice saying so, and deletes are refused while any
 * parcel/merchant/child row still references the record.
 */
return new class extends Migration {
    private const MODULES = [
        'country' => ['country_read', 'country_create', 'country_update', 'country_delete'],
        'city'    => ['city_read', 'city_create', 'city_update', 'city_delete'],
        'area'    => ['area_read', 'area_create', 'area_update', 'area_delete'],
    ];

    private const SOURCE = 'general_settings_read';

    public function up(): void
    {
        $this->insertCatalogRows('permissions');
        $this->insertCatalogRows('super_admin_permissions');

        foreach (['roles', 'users'] as $table) {
            $this->grantWhereHas($table, self::SOURCE);
        }
        $this->grantToSuperAdmins();
    }

    public function down(): void
    {
        foreach (['permissions', 'super_admin_permissions'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereIn('attribute', array_keys(self::MODULES))->delete();
            }
        }

        $all = array_merge(...array_values(self::MODULES));
        foreach (['roles', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull('permissions')->get(['id', 'permissions']) as $row) {
                $perms = json_decode($row->permissions ?? '[]', true);
                if (! is_array($perms)) {
                    continue;
                }
                $filtered = array_values(array_diff($perms, $all));
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

    private function insertCatalogRows(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach (self::MODULES as $attribute => $keywords) {
            if (DB::table($table)->where('attribute', $attribute)->exists()) {
                continue;
            }
            DB::table($table)->insert([
                'attribute'  => $attribute,
                'keywords'   => json_encode([
                    'read'   => $attribute . '_read',
                    'create' => $attribute . '_create',
                    'update' => $attribute . '_update',
                    'delete' => $attribute . '_delete',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function grantWhereHas(string $table, string $sourcePermission): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach (DB::table($table)->whereNotNull('permissions')->get(['id', 'permissions']) as $row) {
            $perms = json_decode($row->permissions ?? '[]', true);
            if (! is_array($perms) || ! in_array($sourcePermission, $perms, true)) {
                continue;
            }
            $this->addPerms($table, $row->id, $perms);
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
            $row = DB::table('roles')->where('id', $roleId)->first(['id', 'permissions']);
            if ($row) {
                $this->addPerms('roles', $row->id, json_decode($row->permissions ?? '[]', true) ?: []);
            }
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        // Enforcement reads users.permissions, not the role's, so the users
        // attached to those roles need the grant too.
        foreach (DB::table('users')->whereIn('role_id', $roleIds)->get(['id', 'permissions']) as $row) {
            $this->addPerms('users', $row->id, json_decode($row->permissions ?? '[]', true) ?: []);
        }
    }

    private function addPerms(string $table, $id, array $perms): void
    {
        if (! is_array($perms)) {
            $perms = [];
        }

        $before = count($perms);
        foreach (array_merge(...array_values(self::MODULES)) as $keyword) {
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
};
