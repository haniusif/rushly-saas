<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ndrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels');
            $table->unsignedBigInteger('company_id');                            // tenant scope
            $table->foreignId('deliveryman_id')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->tinyInteger('attempt_number')->default(1);                   // 1 | 2 | 3
            $table->string('failure_reason');
            // CUSTOMER_ABSENT | WRONG_ADDRESS | REFUSED_DELIVERY | CUSTOMER_POSTPONED
            // | ACCESS_DENIED | PAYMENT_ISSUE | DAMAGED_SHIPMENT | INCOMPLETE_ADDRESS | OTHER
            $table->text('driver_notes')->nullable();
            $table->string('driver_photo')->nullable();                          // path stored via Upload model
            $table->boolean('customer_notified')->default(false);
            $table->string('action_taken')->nullable();
            // reschedule | return_to_merchant | transfer_hub | escalate
            $table->date('next_attempt_date')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->string('status')->default('open');
            // open | in_progress | resolved | returned

            // Cross-module link (Phase 6) — created here so future code doesn't need another migration.
            $table->foreignId('abnormal_shipment_id')->nullable()->constrained('abnormal_shipments');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index('parcel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ndrs');
    }
};
