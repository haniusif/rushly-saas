<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user progress for a tour version. Keyed by (user_id, tour_key,
 * tour_version) so bumping the version resets progress and re-shows the
 * tour to users who saw v1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tour_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('tour_key', 100);
            $table->unsignedInteger('tour_version')->default(1);
            $table->string('status', 20)->default('started'); // started|completed|skipped|dismissed
            $table->unsignedInteger('current_step')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tour_key', 'tour_version'], 'user_tour_unique');
            $table->index(['company_id', 'tour_key', 'status'], 'user_tour_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tour_progress');
    }
};
