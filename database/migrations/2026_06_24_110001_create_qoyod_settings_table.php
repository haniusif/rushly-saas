<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qoyod_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->text('api_key')->nullable();
            $table->unsignedBigInteger('default_inventory_id')->nullable();
            $table->unsignedBigInteger('default_product_id')->nullable();
            $table->unsignedBigInteger('default_account_id')->nullable();
            $table->decimal('vat_percent', 5, 2)->default(15.00);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qoyod_settings');
    }
};
