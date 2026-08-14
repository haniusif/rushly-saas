<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.5 — reverse link from courier-side Parcel back to the OMS
 * Order it was materialised from. Nullable because most Parcels don't
 * come from the OMS pipeline (legacy Salla path + admin manual create
 * still write Parcels without an Order).
 *
 * Indexed for the OrderToParcelBridge's idempotency check
 * (`Parcel::where('oms_order_id', $order->id)->first()`) — that's the
 * hot path on every fulfillment call.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->unsignedBigInteger('oms_order_id')->nullable()->after('wms_fulfillment_id');
            $table->index('oms_order_id', 'parcels_oms_order_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropIndex('parcels_oms_order_id_idx');
            $table->dropColumn('oms_order_id');
        });
    }
};
