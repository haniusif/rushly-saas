<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordered steps for a tour. `target` is a JSON descriptor of how to find
 * the DOM element — {type: 'data-tour'|'selector'|'route-name', value: '...'}.
 * `translations` carries per-locale title/body: {en:{title,body},ar:{...}}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('target');                // JSON descriptor
            $table->string('placement', 20)->default('auto'); // top|bottom|start|end|auto
            $table->unsignedInteger('spotlight_padding')->default(8);
            $table->longText('translations');          // JSON: per-locale content
            $table->longText('action')->nullable();    // JSON: {navigate, wait_for, condition}
            $table->timestamps();

            $table->index(['tour_id', 'sort_order'], 'tour_steps_tour_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_steps');
    }
};
