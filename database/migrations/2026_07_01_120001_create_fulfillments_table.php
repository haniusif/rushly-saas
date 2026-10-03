<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per fulfillment attempt against an OMS Order. Distinct from
 * `wms_fulfillments` (which is warehouse-specific — driven by WMS pick/pack
 * workflow) and from `shipments` (courier hand-off). This table sits above
 * both: it records "we decided to fulfill order X via strategy Y".
 *
 * A single Order may have multiple Fulfillment rows over its lifetime —
 * initial attempt failed → retried, or split shipment where two Fulfillments
 * cover different item subsets (Phase 6.5). Phase 6 MVP writes exactly one
 * Fulfillment per Order.
 *
 * strategy links to a FulfillmentStrategy class via
 * config('fulfillment.strategies.<code>.class'), same resolution pattern
 * as commerce providers.
 *
 * shipping_connection_id / wms_fulfillment_id / external_reference are
 * populated by the strategy as it does its work — one is set, the others
 * stay null depending on which path was taken.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Which strategy handled this fulfillment. Values match
            // FulfillmentStrategyInterface::code() — 'wms', 'threepl_dropship',
            // 'vendor_direct', 'merchant_self'.
            $table->string('strategy', 32)->index();
            $table->unsignedBigInteger('route_id')->nullable();     // fulfillment_routes.id if router picked, null if manual
            $table->string('status', 32)->default('pending')->index(); // pending, in_progress, completed, failed, cancelled

            // Strategy-specific targets — one set per strategy, others null.
            $table->unsignedBigInteger('shipping_connection_id')->nullable(); // for threepl_dropship
            $table->unsignedBigInteger('wms_fulfillment_id')->nullable();     // for wms
            $table->unsignedBigInteger('hub_id')->nullable();                  // for wms — chosen warehouse
            $table->string('external_reference', 191)->nullable();             // awb / wms internal id / vendor po#

            // Strategy state + audit
            $table->json('payload')->nullable();      // strategy-specific extras (route match diagnostics, provider response, ...)
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'ful_company_status_idx');
            $table->index(['order_id', 'created_at'], 'ful_order_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
    }
};
