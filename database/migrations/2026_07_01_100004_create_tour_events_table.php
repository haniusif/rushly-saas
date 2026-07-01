<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only analytics stream for tour events.
 * Kept separate from activity_log so aggregate queries stay cheap and
 * indexed by (company_id, tour_key, event, created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('tour_key', 100);
            // started | step_forward | step_back | skipped | completed | dismissed | element_missing
            $table->string('event', 30);
            $table->unsignedInteger('step_index')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->longText('meta')->nullable();  // JSON
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'tour_key', 'event', 'created_at'], 'tour_events_scope_idx');
            $table->index(['tour_key', 'event'], 'tour_events_key_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_events');
    }
};
