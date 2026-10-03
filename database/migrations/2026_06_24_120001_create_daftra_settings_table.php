<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daftra_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('subdomain', 64)->nullable();
            $table->text('api_key')->nullable();
            $table->string('default_payment_method', 32)->default('cash');
            $table->decimal('vat_percent', 5, 2)->default(15.00);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daftra_settings');
    }
};
