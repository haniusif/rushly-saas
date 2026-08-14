<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail per order. Every material change (created,
 * status_changed, fulfillment_dispatched, parcel_linked, cancelled, ...)
 * writes one row.
 *
 * Analogous to `parcel_events` for the courier side. Keeping them
 * separate — parcel_events tracks courier movement (state machine
 * transitions with driver/hub context), order_events tracks commerce
 * lifecycle (with connection/webhook context).
 *
 * Payload JSON carries per-event context — mapper output snapshot for
 * `created`, {from, to} for `status_changed`, {parcel_id, tracking_id}
 * for `parcel_linked`, etc. Rendered as-is in the admin viewer.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('event_type', 64)->index();          // 'created', 'status_changed', 'parcel_linked', ...
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();  // null for system-generated events
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['order_id', 'occurred_at'], 'oe_order_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
    }
};
