<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('salla_shipments', function (Blueprint $table) {
            // Salla's human-readable shipment identifier. Required when we
            // PUT back to /admin/v2/shipments/{id} (see ApiClient::updateShipment).
            // Captured from the order.shipment.creating webhook payload.
            $table->string('shipment_number')->nullable()->after('salla_shipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('salla_shipments', function (Blueprint $table) {
            $table->dropColumn('shipment_number');
        });
    }
};
