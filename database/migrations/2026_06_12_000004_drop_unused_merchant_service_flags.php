<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the has_last_mile / has_fulfillment / has_storage booleans I
 * added in the previous migration. The existing JSON `services` column
 * already carries the same information and the admin edit UI is
 * already wired to it (resources/views/backend/merchant/edit.blade.php
 * line ~263, "Classification and services"). Two parallel sources of
 * truth would only cause drift, so the JSON column wins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['has_last_mile', 'has_fulfillment', 'has_storage']);
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('has_last_mile')->default(true)->after('status');
            $table->boolean('has_fulfillment')->default(false)->after('has_last_mile');
            $table->boolean('has_storage')->default(false)->after('has_fulfillment');
        });
    }
};
