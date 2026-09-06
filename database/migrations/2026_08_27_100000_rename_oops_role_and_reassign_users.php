<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data repair for company 9 (tenant rushly-logistic / admin.rushly.tech).
 *
 * Role id 4 was literally named "oops" — a mis-keyed name that had stuck, and
 * five of the tenant's seven admin users sat on it. It is a real role with 125
 * permissions spanning settings, integrations, merchants, hubs, parcels and
 * invoices, so it is renamed rather than deleted:
 *
 *   name: oops -> Operations Admin
 *   slug: oops -> operations-admin
 *
 * The rename alone changes NO access. Nothing in the codebase referenced the
 * slug "oops" (RoleRepository::permissions() only special-cases "admin" and
 * "super-admin", and both the old and new slug fall through the same branch).
 *
 * Two users are additionally moved to the standard role that matches what they
 * actually do. Enforcement reads users.permissions, not the role's
 * (PermissionCheckMiddleware), and UserRepository copies role -> user on
 * assignment, so this migration writes BOTH columns to match how the app itself
 * would have done it through the role editor:
 *
 *   27 "operation" -> Dispatcher  (held 38, gains Dispatcher's 44; its parcel /
 *                                  hub / delivery-man scope is what it already used)
 *   28 "CASHER"    -> Finance     (held 81, gains Finance's 65; its set was
 *                                  finance-heavy — bank, payout, income/expense)
 *
 * Both counts move because the assignment REPLACES the user's permissions with
 * the role's, exactly as the role editor does. CASHER nets -16.
 *
 * Left deliberately alone:
 *   24 William, 110 Claudine — they hold exactly the role's 125 permissions,
 *      so the rename is the whole fix. Moving them to Company Admin would have
 *      MORE THAN DOUBLED their access (125 -> 287); that is not a cleanup.
 *   45 Ibrahim — disabled account holding 12 permissions. Granting a disabled
 *      user a wider role is the wrong direction, so he stays put.
 *
 * Guarded and idempotent throughout: every write is scoped to company 9 and
 * checks the current value first, so a re-run is a no-op and the migration
 * cannot touch another tenant's identically-numbered rows.
 */
return new class extends Migration {
    private const COMPANY_ID = 9;
    private const ROLE_ID    = 4;

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        $this->renameRole('oops', 'Operations Admin', 'operations-admin');

        // Target roles are resolved by slug within the company, never by a
        // hardcoded id — role ids differ per tenant.
        $this->moveUser(27, 'dispatcher');
        $this->moveUser(28, 'finance');
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        $this->renameRole('Operations Admin', 'oops', 'oops');

        // Restore the exact per-user permission sets captured before the move.
        // They were hand-tuned subsets, not a copy of any role, so they cannot
        // be reconstructed from the role table.
        $this->restoreUser(27, self::ORIGINAL_27);
        $this->restoreUser(28, self::ORIGINAL_28);
    }

    private function renameRole(string $fromName, string $toName, string $toSlug): void
    {
        $role = DB::table('roles')
            ->where('id', self::ROLE_ID)
            ->where('company_id', self::COMPANY_ID)
            ->first(['id', 'name']);

        // Only rename if it still carries the name we expect — protects against
        // re-runs and against someone having renamed it by hand in the meantime.
        if (! $role || $role->name !== $fromName) {
            return;
        }

        DB::table('roles')->where('id', self::ROLE_ID)->update([
            'name'       => $toName,
            'slug'       => $toSlug,
            'updated_at' => now(),
        ]);
    }

    private function moveUser(int $userId, string $targetSlug): void
    {
        $user = DB::table('users')
            ->where('id', $userId)
            ->where('company_id', self::COMPANY_ID)
            ->first(['id', 'role_id']);

        // Only move a user still sitting on the old role.
        if (! $user || (int) $user->role_id !== self::ROLE_ID) {
            return;
        }

        $target = DB::table('roles')
            ->where('company_id', self::COMPANY_ID)
            ->where('slug', $targetSlug)
            ->first(['id', 'permissions']);

        if (! $target) {
            return;
        }

        DB::table('users')->where('id', $userId)->update([
            'role_id'     => $target->id,
            'permissions' => $target->permissions,
            'updated_at'  => now(),
        ]);
    }

    private function restoreUser(int $userId, array $permissions): void
    {
        $exists = DB::table('users')
            ->where('id', $userId)
            ->where('company_id', self::COMPANY_ID)
            ->exists();

        if (! $exists) {
            return;
        }

        DB::table('users')->where('id', $userId)->update([
            'role_id'     => self::ROLE_ID,
            'permissions' => json_encode($permissions),
            'updated_at'  => now(),
        ]);
    }

    /** Permissions user 27 ("operation") held before this migration. */
    private const ORIGINAL_27 = [
            'dashboard_read',
            'calendar_read',
            'total_parcel',
            'total_delivery_man',
            'total_parcels_pending',
            'total_pickup_assigned',
            'total_received_warehouse',
            'total_deliveryman_assigned',
            'total_partial_deliverd',
            'hub_read',
            'todo_read',
            'liquid_fragile_read',
            'liquid_fragile_update',
            'liquid_status_change',
            'parcel_read',
            'parcel_create',
            'parcel_update',
            'parcel_status_update',
            'delivery_man_read',
            'delivery_category_read',
            'delivery_category_create',
            'delivery_category_update',
            'packaging_read',
            'packaging_create',
            'category_read',
            'category_create',
            'category_update',
            'cash_received_from_delivery_man_read',
            'cash_received_from_delivery_man_create',
            'cash_received_from_delivery_man_update',
            'cash_received_from_delivery_man_delete',
            'parcel_status_reports',
            'knowledge_base_update',
            'tms_read',
            'tms_create',
            'tms_update',
            'tms_delete',
            'mobile_apps_read',
    ];

    /** Permissions user 28 ("CASHER") held before this migration. */
    private const ORIGINAL_28 = [
            'dashboard_read',
            'calendar_read',
            'total_parcel',
            'total_delivery_man',
            'total_parcels_pending',
            'total_partial_deliverd',
            'total_parcels_deliverd',
            'deliveryman_revenue_charts',
            'recent_parcels',
            'hub_read',
            'income_read',
            'income_create',
            'income_update',
            'expense_read',
            'expense_create',
            'expense_update',
            'todo_read',
            'todo_create',
            'todo_update',
            'fund_transfer_read',
            'fund_transfer_create',
            'merchant_read',
            'merchant_view',
            'merchant_shop_read',
            'merchant_payment_read',
            'merchant_payment_create',
            'merchant_payment_update',
            'payment_read',
            'payment_create',
            'hub_payment_read',
            'hub_payment_create',
            'hub_payment_request_read',
            'hub_payment_request_create',
            'liquid_fragile_read',
            'liquid_fragile_update',
            'sms_settings_read',
            'notification_settings_read',
            'notification_settings_update',
            'push_notification_read',
            'push_notification_create',
            'parcel_read',
            'delivery_man_read',
            'delivery_category_read',
            'delivery_type_read',
            'support_read',
            'support_create',
            'support_update',
            'support_reply',
            'support_status_update',
            'asset_category_read',
            'asset_category_create',
            'asset_category_update',
            'assets_read',
            'assets_create',
            'bank_transaction_read',
            'cash_received_from_delivery_man_read',
            'cash_received_from_delivery_man_create',
            'cash_received_from_delivery_man_update',
            'parcel_status_reports',
            'parcel_wise_profit',
            'parcel_total_summery',
            'salary_reports',
            'merchant_hub_deliveryman',
            'fraud_read',
            'fraud_create',
            'fraud_update',
            'invoice_read',
            'invoice_status_update',
            'paid_invoice_read',
            'invoice_generate_menually',
            'partner_read',
            'pages_read',
            'wallet_request_read',
            'wallet_request_create',
            'knowledge_base_update',
            'tms_read',
            'tms_create',
            'tms_update',
            'tms_delete',
            'mobile_apps_read',
            'payout_read',
    ];
};
