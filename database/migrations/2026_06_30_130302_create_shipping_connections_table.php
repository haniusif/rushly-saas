<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();    // Tenant scope (matches existing codebase convention)
            $table->foreignId('provider_id')->constrained('shipping_providers')->cascadeOnDelete();
            $table->string('connection_name', 100);                // Human label: "Riyadh Logestechs", "Backup acct"
            $table->string('remote_company_id', 64)->nullable();   // Their side's company id (resolved via getCompanyByDomain)
            $table->string('domain', 255)->nullable();             // e.g. salesksa.logestechs.com
            $table->string('email', 191)->nullable();
            $table->text('password_encrypted')->nullable();        // Laravel Crypt — decryptable per call
            $table->json('settings')->nullable();                  // Provider-specific extras (integration_source, default_service_type, ...)
            $table->enum('status', ['active', 'paused', 'invalid'])->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider_id', 'connection_name'], 'sc_company_provider_name_unique');
            $table->index(['company_id', 'is_default'], 'sc_company_default_idx');
            $table->index(['provider_id', 'status'], 'sc_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_connections');
    }
};
