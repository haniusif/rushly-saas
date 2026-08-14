<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.5 — denormalized parcel_id on fulfillments. Populated by
 * WmsFulfillmentStrategy + ThreePlDropshipStrategy after the bridge
 * hands them a Parcel. Redundant with `Parcel::where('oms_order_id',
 * $f->order_id)` but cheap and makes the admin viewer + downstream
 * queries a straight join.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('fulfillments', function (Blueprint $table) {
            $table->unsignedBigInteger('parcel_id')->nullable()->after('wms_fulfillment_id');
            $table->index('parcel_id', 'fulfillments_parcel_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fulfillments', function (Blueprint $table) {
            $table->dropIndex('fulfillments_parcel_id_idx');
            $table->dropColumn('parcel_id');
        });
    }
};
