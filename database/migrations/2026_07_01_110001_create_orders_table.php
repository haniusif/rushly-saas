<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OMS canonical `orders` table. One row per merchant-side commerce order,
 * distinct from `parcels` (which model the courier-side shipment unit).
 *
 * Populated by OrderService::receiveNormalized() from the OrderDTO the
 * per-provider mapper produced. Every field maps directly to an
 * OrderDTO property so the mapper stays the single-source-of-truth
 * for canonical shape.
 *
 * Address fields are denormalized inline (same as parcels.customer_*)
 * because addresses are shipment-specific and change per order — no
 * separate `order_addresses` table.
 *
 * Deduplication: UNIQUE(connection_id, remote_order_id) so replaying the
 * same webhook is a no-op. OrderService uses updateOrCreate on that key.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            // Source references — loose FKs (no constraint) so a deleted
            // connection / merchant doesn't cascade-nuke historical orders.
            $table->unsignedBigInteger('connection_id')->nullable()->index();
            $table->string('source_provider_code', 32)->index();               // 'salla', 'zid', ... (redundant with connection but survives deletes)
            $table->unsignedBigInteger('merchant_id')->nullable()->index();    // Rushly merchants.id
            $table->unsignedBigInteger('webhook_event_id')->nullable()->index(); // webhook_events.id that produced this order

            // Provider-side identifiers
            $table->string('remote_order_id', 191);
            $table->string('remote_order_number', 191)->nullable();

            // Provider-native + canonical statuses
            $table->string('provider_status', 100)->nullable();
            $table->string('status', 32)->default('pending')->index();          // OrderStatus enum values
            $table->string('payment_status', 32)->default('unknown')->index(); // PaymentStatus enum values
            $table->string('fulfillment_status', 32)->default('unfulfilled')->index(); // FulfillmentStatus enum values
            $table->string('payment_method', 32)->nullable();                   // canonicalised by mapper

            // Customer (inline snapshot, same pattern as parcels.customer_*)
            $table->string('customer_remote_id', 191)->nullable();
            $table->string('customer_name', 191)->nullable();
            $table->string('customer_email', 191)->nullable();
            $table->string('customer_phone', 64)->nullable()->index();          // Indexed for Phase 5.5 dedupe queries

            // Shipping address (inline)
            $table->string('shipping_name', 191)->nullable();
            $table->string('shipping_phone', 64)->nullable();
            $table->string('shipping_line1', 255)->nullable();
            $table->string('shipping_line2', 255)->nullable();
            $table->string('shipping_city_name', 100)->nullable();
            $table->string('shipping_area_name', 100)->nullable();
            $table->string('shipping_region', 100)->nullable();
            $table->string('shipping_country', 100)->nullable();
            $table->string('shipping_postcode', 32)->nullable();
            $table->unsignedBigInteger('shipping_city_id')->nullable()->index();   // AddressResolver match into local cities.id
            $table->unsignedBigInteger('shipping_area_id')->nullable();

            // Money — decimal, 2 places (matches parcels.cash_collection convention)
            $table->decimal('subtotal',     12, 2)->default(0);
            $table->decimal('tax',          12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('discount',     12, 2)->default(0);
            $table->decimal('total',        12, 2)->default(0);
            $table->decimal('cod_amount',   12, 2)->default(0);
            $table->string('currency', 8)->default('SAR');

            $table->text('note')->nullable();

            // Snapshot of the OrderDTO that produced this row — kept for
            // audit / debugging. Consumers should read the columns above,
            // not this blob.
            $table->longText('normalized_snapshot')->nullable();
            // Provider-specific overflow that didn't fit anywhere else.
            $table->json('extra')->nullable();

            $table->timestamp('occurred_at')->nullable()->index();               // Provider's order-placed timestamp
            $table->timestamp('received_at')->useCurrent()->index();             // When Rushly first materialised this row
            $table->timestamps();

            // Idempotency: same (connection, remote order) can't produce two rows.
            $table->unique(['connection_id', 'remote_order_id'], 'orders_connection_remote_unique');
            $table->index(['company_id', 'status'], 'orders_company_status_idx');
            $table->index(['company_id', 'received_at'], 'orders_company_received_idx');
            $table->index(['merchant_id', 'status'], 'orders_merchant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
