<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill for the two finance pages that just became permission-gated:
 *
 *   paid_invoice_read -> /admin/paid/invoice  (Payment Received)
 *   payout_read       -> /admin/payout        (Payout)
 *
 * Both keys already existed in the tenant catalog (PermissionSeeder) but no
 * route ever referenced them, so the pages were open to every authenticated
 * admin and effectively NOBODY holds the keys today. Adding the middleware
 * without this backfill would take Finance away from everyone, including the
 * company admins who are supposed to have it.
 *
 * Rule: grant to any role/user that already holds `invoice_read`. That is the
 * permission the sibling MerchantInvoiceController routes (merchant.invoice.*)
 * are gated on, so those principals can already read invoice data — extending
 * them the paid-invoice and payout views grants no new class of information.
 * Everyone else keeps nothing, which is the point of the change.
 *
 * Super-admin roles and their users are granted unconditionally, matching
 * 2026_08_24_120000_seed_platform_page_permissions.
 *
 * Catalog rows are NOT inserted here: both attributes are already present in
 * `permissions` via PermissionSeeder. Nothing is added to the tenant catalog
 * or removed from it — these stay tenant-grantable, unlike the platform pages.
 */
return new class extends Migration {
    private const GRANTS = ['paid_invoice_read', 'payout_read'];
    private const SOURCE = 'invoice_read';

    public function up(): void
    {
        $this->grantWhereHas('roles', self::SOURCE);
        $this->grantWhereHas('users', self::SOURCE);
        $this->grantToSuperAdmins();
    }

    public function down(): void
    {
        // Deliberately NOT revoking. These two keys predate this migration and
        // an operator may have granted them by hand in the role editor; a blind
        // revoke on rollback would silently undo that. The routes losing their
        // middleware is enough to restore the previous behaviour.
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
        foreach (self::GRANTS as $keyword) {
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
