<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salla_merchants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salla_merchant_id')->unique();
            $table->string('store_name')->nullable();
            $table->string('store_domain')->nullable();
            $table->string('owner_email')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->unsignedBigInteger('rushly_merchant_id')->nullable()->index();
            $table->string('rushly_merchant_token')->nullable();
            $table->unsignedBigInteger('rushly_shop_id')->nullable();
            $table->boolean('installed')->default(true);
            $table->timestamp('uninstalled_at')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salla_merchants');
    }
};
