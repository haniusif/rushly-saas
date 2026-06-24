<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salla_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 64)->nullable()->index();
            $table->string('strategy', 16)->default('Signature');
            $table->unsignedBigInteger('salla_merchant_id')->nullable()->index();
            $table->string('status', 16)->index();
            $table->boolean('signature_valid')->default(false);
            $table->string('rejection_reason', 64)->nullable();
            $table->json('payload')->nullable();
            $table->json('headers')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salla_webhook_logs');
    }
};
