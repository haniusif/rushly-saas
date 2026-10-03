<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (tenant, commerce provider, store install). A single tenant
 * may hold multiple connections to the same provider — e.g. two Salla
 * merchants, or one Shopify shop + one custom REST endpoint. The
 * connection_name disambiguates in the admin UI.
 *
 * Auth state is provider-shape-dependent (OAuth bearer + refresh, static
 * API key, per-shop bearer, …), so we carry separate encrypted columns
 * rather than one polymorphic JSON blob — easier to mask in logs and to
 * rotate individually. Columns not used by a given provider stay null.
 *
 * Tenant scoping follows the codebase-wide `company_id` + `companywise`
 * scope convention (NOT a foreign key — tenant tables aren't constrained
 * either).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('commerce_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();                  // Tenant scope
            $table->foreignId('provider_id')->constrained('commerce_providers')->cascadeOnDelete();

            $table->string('connection_name', 100);                              // Human label
            $table->string('remote_store_id', 191)->nullable();                  // salla_merchant_id, zid_store_id, shopify shop domain, WC site_url, ...
            $table->string('domain', 255)->nullable();                           // Public storefront URL (display only)

            // Which Rushly merchant this storefront feeds parcels into. Loose
            // FK — merchants table belongs to the existing schema; cascading
            // would couple modules tighter than is healthy.
            $table->unsignedBigInteger('merchant_id')->nullable()->index();

            // OAuth providers (Salla, Zid, Shopify).
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Static-credential providers (WC plugin, custom REST).
            $table->text('api_key_encrypted')->nullable();
            $table->text('api_secret_encrypted')->nullable();

            // Inbound webhook HMAC secret — per-connection so rotating one
            // tenant's secret can't break another.
            $table->text('webhook_secret_encrypted')->nullable();

            $table->json('settings')->nullable();                                // Provider-specific extras
            $table->enum('status', ['active', 'paused', 'invalid', 'reauth_required'])->default('active');
            $table->boolean('is_default')->default(false);

            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider_id', 'connection_name'], 'cc_company_provider_name_unique');
            // One external store install ↔ one connection. Stops accidental
            // double-bind of the same Shopify shop across tenants.
            $table->unique(['provider_id', 'remote_store_id'], 'cc_provider_remote_store_unique');
            $table->index(['company_id', 'is_default'], 'cc_company_default_idx');
            $table->index(['provider_id', 'status'], 'cc_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_connections');
    }
};
