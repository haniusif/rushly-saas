<?php

namespace Tests\Feature\Ops;

use App\Commerce\Models\CommerceApiLog;
use App\Commerce\Models\WebhookEvent;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommercePruneLogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('commerce_api_logs', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('connection_id')->nullable();
            $t->string('provider_code');
            $t->string('endpoint');
            $t->string('method');
            $t->json('request_headers')->nullable();
            $t->longText('request_body')->nullable();
            $t->unsignedSmallInteger('response_status')->nullable();
            $t->longText('response_body')->nullable();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('webhook_events', function ($t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable();
            $t->unsignedBigInteger('connection_id')->nullable();
            $t->string('provider_code');
            $t->string('event_type')->nullable();
            $t->string('idempotency_key')->unique();
            $t->string('signature')->nullable();
            $t->longText('payload');
            $t->longText('normalized_payload')->nullable();
            $t->text('normalization_error')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamps();
        });
    }

    /** @test */
    public function drops_api_logs_older_than_retention_and_keeps_recent(): void
    {
        config(['commerce.logging.retention_days' => 30]);

        // Old row — should be pruned
        CommerceApiLog::create([
            'provider_code' => 'salla', 'endpoint' => '/x', 'method' => 'GET',
            'created_at' => now()->subDays(60),
        ]);
        // Recent row — should survive
        CommerceApiLog::create([
            'provider_code' => 'salla', 'endpoint' => '/y', 'method' => 'GET',
            'created_at' => now()->subDays(5),
        ]);

        $this->assertSame(2, CommerceApiLog::count());

        $this->artisan('commerce:prune-logs')->assertExitCode(0);

        $this->assertSame(1, CommerceApiLog::count());
        $this->assertSame('/y', CommerceApiLog::first()->endpoint);
    }

    /** @test */
    public function drops_processed_webhook_events_but_keeps_failed_ones(): void
    {
        config(['commerce.logging.retention_days' => 30]);

        // Old + processed → prune
        WebhookEvent::create([
            'provider_code' => 'salla', 'idempotency_key' => 'k1',
            'payload' => [], 'received_at' => now()->subDays(60),
            'processed_at' => now()->subDays(59),
        ]);
        // Old + failed (never processed) → KEEP for debugging
        WebhookEvent::create([
            'provider_code' => 'salla', 'idempotency_key' => 'k2',
            'payload' => [], 'received_at' => now()->subDays(60),
            'processed_at' => null, 'attempts' => 3, 'last_error' => 'boom',
        ]);
        // Recent + processed → keep
        WebhookEvent::create([
            'provider_code' => 'salla', 'idempotency_key' => 'k3',
            'payload' => [], 'received_at' => now()->subDays(5),
            'processed_at' => now()->subDays(4),
        ]);

        $this->artisan('commerce:prune-logs')->assertExitCode(0);

        $survivors = WebhookEvent::pluck('idempotency_key')->all();
        $this->assertContains('k2', $survivors, 'failed event must survive retention');
        $this->assertContains('k3', $survivors, 'recent event must survive retention');
        $this->assertNotContains('k1', $survivors, 'old processed event must be pruned');
    }

    /** @test */
    public function dry_run_reports_but_deletes_nothing(): void
    {
        config(['commerce.logging.retention_days' => 30]);

        CommerceApiLog::create([
            'provider_code' => 'salla', 'endpoint' => '/x', 'method' => 'GET',
            'created_at' => now()->subDays(60),
        ]);

        $this->artisan('commerce:prune-logs', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(1, CommerceApiLog::count(), 'row should still exist after dry-run');
    }
}
