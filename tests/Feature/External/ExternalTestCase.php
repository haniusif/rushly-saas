<?php

namespace Tests\Feature\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Shared base for external integration tests (Salla, Zid, WooCommerce).
 *
 * The main app's migrations include MySQL-specific things, tenant scaffolding,
 * and hundreds of tables we don't need for these tests. So instead of running
 * full migrations, we build a minimal in-memory sqlite schema covering only
 * the tables the external endpoints + services touch:
 *
 *   - merchants            (id, company_id) — for the Merchant::find() lookup
 *   - parcels              (the columns the external controllers fill)
 *   - salla_order_links    (link table for Salla parcels)
 *   - zid_orders           (link table for Zid parcels)
 *   - woocommerce_orders   (link table for WC parcels)
 */
abstract class ExternalTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Stop the parcel observers from firing outbound HTTP during tests.
        // Individual tests register their own narrower fakes — but the global
        // catch-all here shadows them unless we clear it first, so tests that
        // need a non-200 response must call Http::fake([...]) themselves
        // BEFORE this line takes effect (handled by Laravel's fake stack —
        // last registered pattern wins for matching URLs, but a catch-all "*"
        // is broader). Use specific URL globs in tests for that reason.
        Http::fake(['rushly.test/*' => Http::response([], 200)]);

        // Spatie ActivityLog tries to read merchant.business_name and chains
        // through computed accessors that hit the `payments` table — disable it.
        config(['activitylog.enabled' => false]);

        $this->buildSchema();
    }

    private function buildSchema(): void
    {
        Schema::create('merchants', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('business_name')->nullable();
            $table->timestamps();
        });

        Schema::create('parcels', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('tracking_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('merchant_shop_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('delivery_type_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();
            $table->decimal('cash_collection', 12, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->string('note')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('salla_order_links', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('parcel_id')->nullable();
            $table->unsignedBigInteger('salla_merchant_id');
            $table->unsignedBigInteger('salla_order_id');
            $table->string('salla_shipment_id')->nullable();
            $table->string('salla_awb_number')->nullable();
            $table->string('last_pushed_status')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('zid_orders', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('parcel_id')->nullable();
            $table->string('zid_store_id');
            $table->string('zid_order_id');
            $table->string('zid_shipment_id')->nullable();
            $table->string('zid_awb_number')->nullable();
            $table->string('last_pushed_status')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('woocommerce_orders', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('parcel_id')->nullable();
            $table->string('site_url');
            $table->unsignedBigInteger('wc_order_id');
            $table->string('site_token')->nullable();
            $table->string('wc_awb_number')->nullable();
            $table->string('last_pushed_status')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}
