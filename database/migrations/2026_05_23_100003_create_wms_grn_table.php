<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_grn', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('grn_number')->unique();
            $table->foreignId('hub_id')->constrained('hubs');
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('received_by')->constrained('users');
            $table->string('reference_number')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_grn');
    }
};
