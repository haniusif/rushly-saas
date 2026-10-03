<?php

namespace Tests\Feature\Oms;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Shared base for OMS tests. Same rationale as ExternalTestCase: the
 * main app's migrations aren't sqlite-clean, so we hand-build only the
 * tables the OMS pipeline touches:
 *
 *   - commerce_providers   (provider catalog)
 *   - commerce_connections (per-tenant install)
 *   - webhook_events       (source events, if any)
 *   - orders               (OMS canonical order)
 *   - order_items          (line items)
 *   - order_events         (audit trail)
 */
abstract class OmsTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);

        $this->buildSchema();
    }

    private function buildSchema(): void
    {
        Schema::create('commerce_providers', function ($t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->string('name', 64);
            $t->string('logo_url')->nullable();
            $t->string('status', 16)->default('active');
            $t->json('supports')->nullable();
            $t->timestamps();
        });

        Schema::create('commerce_connections', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('provider_id');
            $t->string('connection_name', 100);
            $t->string('remote_store_id', 191)->nullable();
            $t->string('domain', 255)->nullable();
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->text('access_token_encrypted')->nullable();
            $t->text('refresh_token_encrypted')->nullable();
            $t->timestamp('token_expires_at')->nullable();
            $t->text('api_key_encrypted')->nullable();
            $t->text('api_secret_encrypted')->nullable();
            $t->text('webhook_secret_encrypted')->nullable();
            $t->json('settings')->nullable();
            $t->string('status', 32)->default('active');
            $t->boolean('is_default')->default(false);
            $t->timestamp('last_tested_at')->nullable();
            $t->timestamp('last_sync_at')->nullable();
            $t->timestamp('last_event_at')->nullable();
            $t->timestamps();
        });

        Schema::create('webhook_events', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('connection_id')->nullable();
            $t->string('provider_code', 32);
            $t->string('event_type', 100)->nullable();
            $t->string('idempotency_key', 191)->unique();
            $t->string('signature', 191)->nullable();
            $t->longText('payload');
            $t->longText('normalized_payload')->nullable();
            $t->text('normalization_error')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('connection_id')->nullable();
            $t->string('source_provider_code', 32);
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->unsignedBigInteger('webhook_event_id')->nullable();
            $t->string('remote_order_id', 191);
            $t->string('remote_order_number', 191)->nullable();
            $t->string('provider_status', 100)->nullable();
            $t->string('status', 32)->default('pending');
            $t->string('payment_status', 32)->default('unknown');
            $t->string('fulfillment_status', 32)->default('unfulfilled');
            $t->string('payment_method', 32)->nullable();
            $t->string('customer_remote_id', 191)->nullable();
            $t->string('customer_name', 191)->nullable();
            $t->string('customer_email', 191)->nullable();
            $t->string('customer_phone', 64)->nullable();
            $t->string('shipping_name', 191)->nullable();
            $t->string('shipping_phone', 64)->nullable();
            $t->string('shipping_line1', 255)->nullable();
            $t->string('shipping_line2', 255)->nullable();
            $t->string('shipping_city_name', 100)->nullable();
            $t->string('shipping_area_name', 100)->nullable();
            $t->string('shipping_region', 100)->nullable();
            $t->string('shipping_country', 100)->nullable();
            $t->string('shipping_postcode', 32)->nullable();
            $t->unsignedBigInteger('shipping_city_id')->nullable();
            $t->unsignedBigInteger('shipping_area_id')->nullable();
            $t->decimal('subtotal',     12, 2)->default(0);
            $t->decimal('tax',          12, 2)->default(0);
            $t->decimal('shipping_fee', 12, 2)->default(0);
            $t->decimal('discount',     12, 2)->default(0);
            $t->decimal('total',        12, 2)->default(0);
            $t->decimal('cod_amount',   12, 2)->default(0);
            $t->string('currency', 8)->default('SAR');
            $t->text('note')->nullable();
            $t->longText('normalized_snapshot')->nullable();
            $t->json('extra')->nullable();
            $t->timestamp('occurred_at')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
            $t->unique(['connection_id', 'remote_order_id']);
        });

        Schema::create('order_items', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('sku', 191)->nullable();
            $t->string('name', 255);
            $t->unsignedInteger('quantity')->default(1);
            $t->decimal('unit_price',  12, 2)->default(0);
            $t->decimal('total_price', 12, 2)->default(0);
            $t->string('currency', 8)->default('SAR');
            $t->string('remote_product_id', 191)->nullable();
            $t->string('remote_variant_id', 191)->nullable();
            $t->json('extra')->nullable();
            $t->timestamps();
        });

        Schema::create('order_events', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('company_id');
            $t->string('event_type', 64);
            $t->json('payload')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('occurred_at')->nullable();
            $t->timestamps();
        });
    }
}
