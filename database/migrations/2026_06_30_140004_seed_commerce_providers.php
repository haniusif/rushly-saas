<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the catalog of supported commerce providers. Idempotent — safe to
 * re-run. Status is `disabled` for every provider until a concrete impl
 * lands in app/Commerce/Providers/ in later phases; the admin UI is
 * expected to gate "Add connection" on status=active AND
 * config('features.commerce_layer').
 *
 * Capability arrays must stay aligned with the SupportsX marker interfaces
 * the eventual provider class will declare.
 */
return new class extends Migration {
    public function up(): void
    {
        $rows = [
            [
                'code'     => 'salla',
                'name'     => 'Salla',
                'logo_url' => '/images/partners/salla.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['oauth', 'webhooks', 'bulk_fetch', 'order_writeback', 'inventory_sync']),
            ],
            [
                'code'     => 'zid',
                'name'     => 'Zid',
                'logo_url' => '/images/partners/zid.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['oauth', 'webhooks', 'bulk_fetch', 'order_writeback', 'inventory_sync']),
            ],
            [
                'code'     => 'shopify',
                'name'     => 'Shopify',
                'logo_url' => '/images/partners/shopify.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['oauth', 'webhooks', 'bulk_fetch', 'order_writeback', 'inventory_sync']),
            ],
            [
                'code'     => 'woocommerce',
                'name'     => 'WooCommerce',
                'logo_url' => '/images/partners/woocommerce.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['webhooks', 'order_writeback']),
            ],
            [
                'code'     => 'magento',
                'name'     => 'Magento',
                'logo_url' => '/images/partners/magento.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['webhooks', 'bulk_fetch', 'order_writeback']),
            ],
            [
                'code'     => 'opencart',
                'name'     => 'OpenCart',
                'logo_url' => '/images/partners/opencart.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['webhooks', 'order_writeback']),
            ],
            [
                'code'     => 'custom_rest',
                'name'     => 'Custom REST',
                'logo_url' => '/images/partners/custom-rest.svg',
                'status'   => 'disabled',
                'supports' => json_encode(['webhooks']),
            ],
        ];

        foreach ($rows as $row) {
            DB::table('commerce_providers')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('commerce_providers')
            ->whereIn('code', ['salla', 'zid', 'shopify', 'woocommerce', 'magento', 'opencart', 'custom_rest'])
            ->delete();
    }
};
