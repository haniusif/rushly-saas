<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-lived, single-use handoff tokens for the super-admin "Login as company"
 * flow. A token is minted on the central host and consumed on the target
 * tenant's subdomain (see CompanyController::impersonate / consume). It lives in
 * the shared DB on purpose — the cache is re-scoped per tenant and can't carry a
 * value across the central/tenant boundary.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('impersonation_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('user_id');          // company owner being logged in
            $table->unsignedBigInteger('impersonator_id');  // the super-admin
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('impersonation_tokens');
    }
};
