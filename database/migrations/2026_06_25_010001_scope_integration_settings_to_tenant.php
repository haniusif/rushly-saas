<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Move integration_settings from a single global row per platform to a
     * per-tenant row per (company_id, platform). Each tenant manages their own
     * Salla / Zid / etc. credentials going forward; the .env-derived globals
     * become the seed row for one designated tenant (see backfill section).
     */
    public function up(): void
    {
        Schema::table('integration_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id');
        });

        DB::statement('ALTER TABLE integration_settings DROP INDEX integration_settings_platform_unique');
        DB::statement('ALTER TABLE integration_settings ADD UNIQUE integration_settings_company_platform_unique (company_id, platform)');

        // Backfill: assign all existing global rows to the demo tenant
        // (company_id=13). Operators can re-target by updating company_id
        // directly before this migration runs in production.
        $targetCompanyId = (int) (env('INTEGRATIONS_BACKFILL_COMPANY_ID') ?: 13);
        DB::table('integration_settings')->whereNull('company_id')->update([
            'company_id' => $targetCompanyId,
        ]);

        // Seed the existing .env Salla creds onto the same tenant's row.
        // Credentials live in `meta` so we don't add Salla-only columns to a
        // table shared with Zid/Shopify/WooCommerce.
        $sallaMeta = array_filter([
            'oauth_client_id'     => env('SALLA_OAUTH_CLIENT_ID'),
            'oauth_client_secret' => env('SALLA_OAUTH_CLIENT_SECRET'),
            'oauth_redirect_uri'  => env('SALLA_OAUTH_CLIENT_REDIRECT_URI'),
            'webhook_secret'      => env('SALLA_WEBHOOK_SECRET'),
            'app_id'              => env('SALLA_APP_ID'),
            'authorization_mode'  => env('SALLA_AUTHORIZATION_MODE', 'easy'),
        ], fn ($v) => $v !== null && $v !== '');

        if (!empty($sallaMeta)) {
            DB::table('integration_settings')
                ->where('company_id', $targetCompanyId)
                ->where('platform', 'salla')
                ->update(['meta' => json_encode($sallaMeta)]);
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE integration_settings DROP INDEX integration_settings_company_platform_unique');
        DB::statement('ALTER TABLE integration_settings ADD UNIQUE integration_settings_platform_unique (platform)');

        Schema::table('integration_settings', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
