<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated nationalities table — conceptually distinct from countries.
 *
 *   "Saudi Arabia" (country)  vs  "سعودي / Saudi" (nationality)
 *
 * Global table (no company_id) since nationality is not tenant-specific.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);           // Arabic form, e.g. سعودي
            $table->string('en_name', 100);        // English form, e.g. Saudi
            $table->string('code', 8)->unique();   // ISO-aligned where possible
            $table->unsignedInteger('sorting')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
