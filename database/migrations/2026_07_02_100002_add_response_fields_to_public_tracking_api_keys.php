<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_tracking_api_keys', function (Blueprint $t) {
            // JSON array of field names the public tracking endpoint is
            // allowed to return for this key. Null = no restriction
            // (all fields returned). Applied by
            // PublicTrackingController::show() after the base projection.
            $t->json('response_fields')->nullable()->after('allowed_origins');
        });
    }

    public function down(): void
    {
        Schema::table('public_tracking_api_keys', function (Blueprint $t) {
            $t->dropColumn('response_fields');
        });
    }
};
