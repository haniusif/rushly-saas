<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per outbound HTTP call from a CommerceProvider to its remote
 * storefront. Inbound webhook payloads land in `webhook_events` (Phase 3)
 * — this table is outbound-only.
 *
 * High-volume. Prune via the daily retention job (Phase 8) keeping ~30 days
 * hot. Schema mirrors shipping_api_logs intentionally so ops tooling /
 * dashboards can stay shape-compatible.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('commerce_api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();      // Nullable: pre-auth lookups aren't tenant-scoped yet
            $table->unsignedBigInteger('connection_id')->nullable()->index();
            $table->string('provider_code', 32)->index();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();             // Partition / prune by this

            $table->index(['company_id', 'created_at'], 'cal_company_created_idx');
            $table->index(['connection_id', 'created_at'], 'cal_connection_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_api_logs');
    }
};
