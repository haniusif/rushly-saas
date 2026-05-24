<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('zid_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->unsignedBigInteger('parcel_id')->nullable()->index();
            $table->string('zid_store_id')->index();    // Zid IDs are strings.
            $table->string('zid_order_id')->index();
            $table->string('zid_shipment_id')->nullable()->index();
            $table->string('zid_awb_number')->nullable();
            $table->string('last_pushed_status')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['zid_store_id', 'zid_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zid_orders');
    }
};
