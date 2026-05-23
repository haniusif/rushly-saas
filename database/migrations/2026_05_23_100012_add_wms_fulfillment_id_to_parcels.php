<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->unsignedBigInteger('wms_fulfillment_id')->nullable()->after('hub_id');
            $table->index('wms_fulfillment_id');
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropIndex(['wms_fulfillment_id']);
            $table->dropColumn('wms_fulfillment_id');
        });
    }
};
