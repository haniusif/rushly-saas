<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — extend webhook_events with the normalizer's output snapshot.
 *
 * `normalized_payload`   — JSON dump of the canonical OrderDTO the mapper
 *                          produced. Persisted alongside the raw payload so
 *                          Phase 5 OMS materialization reads normalized shape
 *                          directly (no re-run) AND we can diff raw ↔ normalized
 *                          in the admin viewer.
 *
 * `normalization_error`  — null when normalization succeeded or wasn't
 *                          attempted; carries the NormalizationException
 *                          message (with flattened field errors) otherwise.
 *
 * Neither column blocks handler success — a failed normalization stamps
 * the error but the event row is still marked processed. Downstream
 * consumers check `normalized_payload IS NOT NULL` before materializing.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->longText('normalized_payload')->nullable()->after('payload');
            $table->text('normalization_error')->nullable()->after('normalized_payload');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn(['normalized_payload', 'normalization_error']);
        });
    }
};
