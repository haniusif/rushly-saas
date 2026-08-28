<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Register EcoExpress (Eco Freight / Focalsoft) as a shipping provider so
 * tenants can create connections against it and it shows on /admin/integrations.
 *
 * `supports` is the honest capability list, and it is deliberately shorter than
 * Logestechs'. EcoExpress publishes NO cancellation endpoint and NO AWB/label
 * endpoint — verified against the live sandbox, not inferred — so 'cancel' and
 * 'awb_pdf' are absent. EcoExpressProvider throws ProviderUnavailableException
 * for both; leaving the capability advertised here would have the UI offer
 * buttons that can only fail.
 *
 * 'villages' is absent too: EcoExpress exposes /states and /cities but both are
 * code-driven rather than search-driven, so there is nothing to bind a
 * free-text area query against.
 *
 * No credentials here. client_id, client_secret and account_no are per-tenant
 * and live encrypted on shipping_connections.settings, entered through the UI.
 */
return new class extends Migration {
    private const CODE = 'ecoexpress';

    public function up(): void
    {
        if (! Schema::hasTable('shipping_providers')) {
            return;
        }

        if (DB::table('shipping_providers')->where('code', self::CODE)->exists()) {
            return;
        }

        DB::table('shipping_providers')->insert([
            'code'       => self::CODE,
            'name'       => 'EcoExpress',
            'logo_url'   => null,
            'status'     => 'active',
            'supports'   => json_encode(['tracking']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipping_providers')) {
            return;
        }

        // Only remove the provider row when nothing depends on it. Deleting a
        // provider that still has connections would orphan them and take the
        // tenant's credentials with it.
        $id = DB::table('shipping_providers')->where('code', self::CODE)->value('id');
        if (! $id) {
            return;
        }

        if (Schema::hasTable('shipping_connections')
            && DB::table('shipping_connections')->where('provider_id', $id)->exists()) {
            return;
        }

        DB::table('shipping_providers')->where('id', $id)->delete();
    }
};
