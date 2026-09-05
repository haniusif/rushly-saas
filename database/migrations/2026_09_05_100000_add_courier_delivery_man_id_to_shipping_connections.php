<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let a shipping connection name the courier record that stands in for the
 * carrier.
 *
 * When a 3PL accepts a shipment the parcel has physically left us, but the
 * lifecycle had no way to say so: it stayed at RECEIVED_WAREHOUSE until the
 * carrier's own tracking eventually reported something. That also broke the
 * DELIVERED bridge in Shipping\Listeners\UpdateParcelStatus, which only
 * routes a delivery through the repository (balances, notifications) when the
 * parcel was at DELIVERY_MAN_ASSIGN first.
 *
 * Moving the parcel to DELIVERY_MAN_ASSIGN ('out for delivery') needs someone
 * to assign it TO, so each connection points at a delivery_man row that
 * represents the carrier - the pattern a tenant had already improvised by
 * hand with a courier literally named 'Panda Delivery'.
 *
 * Nullable on purpose: a connection with no courier still ships, it just
 * leaves the parcel status alone rather than guessing at a courier.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('shipping_connections') || Schema::hasColumn('shipping_connections', 'courier_delivery_man_id')) {
            return;
        }

        Schema::table('shipping_connections', function (Blueprint $table) {
            $table->unsignedBigInteger('courier_delivery_man_id')->nullable()->after('is_default');
            $table->index('courier_delivery_man_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipping_connections') || ! Schema::hasColumn('shipping_connections', 'courier_delivery_man_id')) {
            return;
        }

        Schema::table('shipping_connections', function (Blueprint $table) {
            $table->dropIndex(['courier_delivery_man_id']);
            $table->dropColumn('courier_delivery_man_id');
        });
    }
};
