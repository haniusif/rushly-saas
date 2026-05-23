<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wms_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('hub_id')->constrained('hubs');
            $table->string('zone')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack');
            $table->string('shelf');
            $table->string('bin')->nullable();
            $table->string('code')->unique();
            $table->string('type')->default('standard');
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'hub_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_locations');
    }
};
