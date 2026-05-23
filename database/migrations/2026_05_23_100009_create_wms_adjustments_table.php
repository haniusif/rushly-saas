<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('product_id')->constrained('wms_products');
            $table->foreignId('location_id')->constrained('wms_locations');
            $table->foreignId('adjusted_by')->constrained('users');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('quantity_change');
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            // Dual-approval support: pending approval until a second user confirms.
            $table->string('approval_status')->default('approved'); // approved | pending_approval | rejected
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_adjustments');
    }
};
