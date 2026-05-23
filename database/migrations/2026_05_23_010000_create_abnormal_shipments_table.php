<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abnormal_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels');
            $table->unsignedBigInteger('company_id');          // tenant scope
            $table->timestamp('detected_at');                  // when cron first flagged
            $table->timestamp('last_event_at');                // timestamp of last real activity
            $table->unsignedTinyInteger('stale_days');         // days since last_event_at
            $table->string('severity');                        // warning | danger | critical
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('status')->default('open');         // open | investigating | resolved | closed_lost
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index('parcel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abnormal_shipments');
    }
};
