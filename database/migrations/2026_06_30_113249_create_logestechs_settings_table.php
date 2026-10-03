<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logestechs_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('base_url', 255)->nullable();
            $table->string('integration_source', 64)->nullable();
            $table->string('default_target_company_id', 64)->nullable();
            $table->string('default_email', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logestechs_settings');
    }
};
