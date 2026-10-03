<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Replaces `parcels_3pl` for connections created via the new Shipping
     * module. The old table stays untouched (still serves Aramex/Jet/Zajel/Panda
     * which haven't been migrated yet).
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('parcel_id')->index();
            $table->foreignId('connection_id')->constrained('shipping_connections')->cascadeOnDelete();

            $table->string('remote_shipment_id', 128)->nullable();    // Their id (Logestechs `id`, Aramex AWB, ...)
            $table->string('awb_number', 128)->nullable();             // Their human-readable AWB (Logestechs barcode)
            $table->string('awb_pdf_url', 512)->nullable();

            $table->string('current_status_raw', 128)->nullable();    // Provider's status string
            $table->unsignedSmallInteger('current_status_local')->nullable(); // Mapped to ParcelStatus enum
            $table->timestamp('last_status_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();

            $table->enum('state', ['pending', 'created', 'failed', 'cancelled'])->default('pending');
            $table->json('request_payload')->nullable();              // Last submitted ShipmentDTO snapshot
            $table->json('response_payload')->nullable();             // Last create-shipment response

            $table->timestamps();

            // Prevent duplicate remote rows; allow nullable for failed creates
            $table->unique(['connection_id', 'remote_shipment_id'], 'sh_connection_remote_unique');
            $table->index(['company_id', 'current_status_local'], 'sh_company_status_idx');
            $table->index(['company_id', 'state', 'last_synced_at'], 'sh_company_state_sync_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
