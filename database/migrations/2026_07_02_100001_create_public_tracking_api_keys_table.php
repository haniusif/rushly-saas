<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_tracking_api_keys', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('name', 191);
            // sha256(plaintext) — we never store the plaintext.
            $t->string('key_hash', 64)->unique();
            // First 12 chars of the plaintext key — safe to display in the UI
            // so operators can recognize which row a live key belongs to.
            $t->string('key_prefix', 12)->index();
            // Optional origin allow-list for browser-side callers.
            // JSON array e.g. ["https://acme.com", "https://shop.acme.com"].
            // Null = no origin restriction (server-to-server only recommended).
            $t->json('allowed_origins')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_used_at')->nullable();
            $t->unsignedBigInteger('request_count')->default(0);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_tracking_api_keys');
    }
};
