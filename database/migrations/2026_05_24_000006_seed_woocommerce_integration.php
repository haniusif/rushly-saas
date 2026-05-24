<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `woocommerce` row to `integration_settings` so it appears on the
 * Integrations admin page on existing installs (the first integration_settings
 * migration only seeded salla/zid/shopify).
 *
 * Idempotent — re-running this won't duplicate the row.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('integration_settings')) {
            return;
        }

        $exists = DB::table('integration_settings')->where('platform', 'woocommerce')->exists();
        if ($exists) {
            return;
        }

        $now = now();
        DB::table('integration_settings')->insert([
            'platform'        => 'woocommerce',
            'is_enabled'      => true,
            // Unlike salla/zid there is no single bridge — each WP install
            // talks directly to /api/v10/external/woocommerce/*. The app_url
            // is therefore unused for WC (or, for single-tenant installs, can
            // be set to that one merchant's WP site for default writeback).
            'app_url'         => env('RUSHLY_WOOCOMMERCE_APP_URL'),
            'writeback_token' => env('RUSHLY_WOOCOMMERCE_WRITEBACK_TOKEN'),
            'api_base'        => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_settings')) {
            return;
        }
        DB::table('integration_settings')->where('platform', 'woocommerce')->delete();
    }
};
