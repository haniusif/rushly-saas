<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\Schema;
use Tests\Feature\Oms\OmsTestCase;

/**
 * Extends OmsTestCase — inherits commerce_providers, commerce_connections,
 * webhook_events, orders, order_items, order_events — and adds the two
 * Phase 6 tables.
 */
abstract class FulfillmentTestCase extends OmsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buildFulfillmentSchema();
    }

    private function buildFulfillmentSchema(): void
    {
        Schema::create('fulfillments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->unsignedBigInteger('order_id');
            $t->string('strategy', 32);
            $t->unsignedBigInteger('route_id')->nullable();
            $t->string('status', 32)->default('pending');
            $t->unsignedBigInteger('shipping_connection_id')->nullable();
            $t->unsignedBigInteger('wms_fulfillment_id')->nullable();
            $t->unsignedBigInteger('parcel_id')->nullable();
            $t->unsignedBigInteger('hub_id')->nullable();
            $t->string('external_reference', 191)->nullable();
            $t->json('payload')->nullable();
            $t->text('last_error')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamp('failed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('fulfillment_routes', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('name', 191);
            $t->unsignedInteger('priority')->default(100);
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->string('source_provider_code', 32)->nullable();
            $t->unsignedBigInteger('shipping_city_id')->nullable();
            $t->string('shipping_country', 100)->nullable();
            $t->decimal('min_total', 12, 2)->nullable();
            $t->decimal('max_total', 12, 2)->nullable();
            $t->boolean('is_cod')->nullable();
            $t->string('strategy', 32);
            $t->unsignedBigInteger('shipping_connection_id')->nullable();
            $t->unsignedBigInteger('hub_id')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        // Parcels + wms_fulfillments — needed by the Phase 6.5 Order→Parcel
        // bridge + WmsFulfillmentStrategy. Kept minimal — only the columns
        // the bridge / strategy write to.
        //
        // merchants + salla_order_links / zid_orders / woocommerce_orders
        // are needed because Parcel has registered observers (Salla / Zid /
        // WC / Instrumentation) that fire on create + look up related
        // rows. Rather than disable event listeners, we mirror the
        // approach in ExternalTestCase and hand-build the tables.
        Schema::create('merchants', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('business_name')->nullable();
            $t->json('services')->nullable();   // Phase 3 super-admin — [last_mile, fulfillment, storage]
            $t->timestamps();
        });

        // Phase — fulfillment_defaults (super-admin business logic).
        Schema::create('fulfillment_defaults', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->string('default_strategy', 32)->nullable();
            $t->string('service_last_mile_strategy', 32)->nullable();
            $t->string('service_fulfillment_strategy', 32)->nullable();
            $t->string('service_storage_strategy', 32)->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });

        Schema::create('salla_order_links', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->unsignedBigInteger('parcel_id')->nullable();
            $t->unsignedBigInteger('salla_merchant_id');
            $t->unsignedBigInteger('salla_order_id');
            $t->string('salla_shipment_id')->nullable();
            $t->string('salla_awb_number')->nullable();
            $t->string('last_pushed_status')->nullable();
            $t->timestamp('last_pushed_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('zid_orders', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->unsignedBigInteger('parcel_id')->nullable();
            $t->string('zid_store_id');
            $t->string('zid_order_id');
            $t->string('zid_shipment_id')->nullable();
            $t->string('zid_awb_number')->nullable();
            $t->string('last_pushed_status')->nullable();
            $t->timestamp('last_pushed_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('woocommerce_orders', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('merchant_id')->nullable();
            $t->unsignedBigInteger('parcel_id')->nullable();
            $t->string('site_url');
            $t->unsignedBigInteger('wc_order_id');
            $t->string('site_token')->nullable();
            $t->string('wc_awb_number')->nullable();
            $t->string('last_pushed_status')->nullable();
            $t->timestamp('last_pushed_at')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('parcels', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('merchant_id');
            $t->unsignedBigInteger('merchant_shop_id')->nullable();
            $t->string('tracking_id')->nullable();
            $t->string('customer_name')->nullable();
            $t->string('customer_phone')->nullable();
            $t->longText('customer_address')->nullable();
            $t->unsignedBigInteger('city_id')->nullable();
            $t->unsignedBigInteger('area_id')->nullable();
            $t->decimal('cash_collection', 13, 2)->nullable();
            $t->decimal('cod_amount', 13, 2)->nullable();
            $t->string('reference_number')->nullable();
            $t->longText('note')->nullable();
            $t->unsignedTinyInteger('status')->default(1);
            $t->unsignedBigInteger('wms_fulfillment_id')->nullable();
            $t->unsignedBigInteger('oms_order_id')->nullable();
            // Phase 4b Performance Dashboard instrumentation columns —
            // ParcelInstrumentationObserver auto-populates them on
            // create, so the test schema must have them or the insert
            // fails with "no column named expected_delivery_at".
            $t->timestamp('expected_delivery_at')->nullable();
            $t->bigInteger('distance_m')->nullable();
            $t->timestamps();
        });

        Schema::create('wms_fulfillments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id');
            $t->string('fulfillment_number');
            $t->unsignedBigInteger('parcel_id');
            $t->unsignedBigInteger('hub_id')->nullable();
            $t->unsignedBigInteger('merchant_id');
            $t->string('status', 32)->default('pending');
            $t->unsignedBigInteger('picker_id')->nullable();
            $t->unsignedBigInteger('packer_id')->nullable();
            $t->timestamp('picked_at')->nullable();
            $t->timestamp('packed_at')->nullable();
            $t->timestamp('dispatched_at')->nullable();
            $t->timestamp('sla_deadline')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamps();
        });
    }
}
