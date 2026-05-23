<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_cycle_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('count_number')->unique();
            $table->foreignId('hub_id')->constrained('hubs');
            $table->foreignId('assigned_to')->constrained('users');
            $table->string('scope');
            $table->string('zone')->nullable();
            $table->string('status')->default('open');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_cycle_counts');
    }
};
