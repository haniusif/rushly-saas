<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding "Take a Tour" — tour definitions.
 *
 * A tour is a scripted walkthrough of a module. company_id is nullable so
 * one row can act either as a system-wide template (null) or as a tenant
 * override. `key` scopes uniqueness within (company_id, role_scope hash)
 * — enforced at the app layer to keep the migration portable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('key', 100)->index();
            $table->string('module', 60)->nullable()->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->longText('role_scope')->nullable();  // JSON array of UserType ints; null = all roles
            $table->longText('meta')->nullable();         // JSON: freeform (icon, category, etc.)
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_start')->default(false); // first-login autostart
            $table->string('trigger_route', 191)->nullable(); // route name to auto-start on
            $table->timestamps();

            $table->index(['company_id', 'key', 'is_active'], 'tours_company_key_active_idx');
            $table->index(['module', 'is_active'], 'tours_module_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
