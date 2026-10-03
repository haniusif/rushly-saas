<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw inbound webhook events from commerce providers. One row per HTTP
 * delivery — the persistence layer in front of the Phase 3 ingest pipeline.
 *
 * Why a separate table from commerce_api_logs:
 *   - api_logs is OUTBOUND (our HTTP calls to providers), webhook_events is INBOUND
 *   - webhook_events drives business logic (drop into OMS, dispatch jobs);
 *     api_logs is diagnostic-only
 *   - webhook_events needs idempotency_key UNIQUE for replay-safety; api_logs
 *     doesn't
 *
 * The `idempotency_key` column is the bridge between providers retrying the
 * same delivery and us refusing to double-process. Each provider's
 * parseWebhookEvent() composes a stable key from the event's natural keys
 * (e.g. `salla:{merchant_id}:{event}:{event_id}`).
 *
 * `processed_at` is null on insert, stamped by IngestWebhookJob when the
 * handler returns successfully. `attempts` + `last_error` capture retry
 * state — Laravel's queue retries the job; the column lets us surface
 * progress in the admin viewer.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();        // Tenant scope. Nullable: resolved AFTER connection lookup.
            $table->unsignedBigInteger('connection_id')->nullable()->index();      // FK-loose to commerce_connections.id. Nullable: pre-resolution.
            $table->string('provider_code', 32)->index();                          // 'salla', 'zid', ...
            $table->string('event_type', 100)->nullable();                         // 'order.created', 'shipment.creating', ...
            // Per-(connection,event) unique. Survives retries — second insert
            // raises 1062 which the ingest service catches and treats as
            // "already received, do nothing".
            $table->string('idempotency_key', 191)->unique('we_idempotency_key_unique');
            $table->string('signature', 191)->nullable();                          // X-Salla-Signature / X-Shopify-Hmac / ...
            $table->longText('payload');                                            // Full JSON body — keep raw for re-processing after normalization-rule changes
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable()->index();                 // Stamped on first successful handler run
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['provider_code', 'processed_at'], 'we_provider_processed_idx');
            $table->index(['connection_id', 'received_at'], 'we_connection_received_idx');
            $table->index(['company_id', 'received_at'], 'we_company_received_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
