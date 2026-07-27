<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('salla_merchants', function (Blueprint $table) {
            // The Rushly tenant that owned the OAuth callback / webhook URL
            // this store installed through. Nullable so historic rows created
            // before this column existed keep loading; stamped from
            // settings()->id at OAuth callback + app.store.authorize time.
            $table->unsignedBigInteger('company_id')->nullable()->after('salla_merchant_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('salla_merchants', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
