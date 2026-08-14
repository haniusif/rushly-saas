<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * High-volume table. Prune via daily job (`shipping:prune-logs`) — keep
     * 30 days hot, drop the rest. For cold archival, tail to S3/object store
     * before pruning (out of scope for this migration).
     */
    public function up(): void
    {
        Schema::create('shipping_api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // Nullable: pre-auth calls (getCompanyByDomain) aren't tenant-scoped yet
            $table->unsignedBigInteger('connection_id')->nullable()->index();
            $table->string('provider_code', 32)->index();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();        // Partition / prune by this

            $table->index(['company_id', 'created_at'], 'sal_company_created_idx');
            $table->index(['connection_id', 'created_at'], 'sal_connection_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_api_logs');
    }
};
