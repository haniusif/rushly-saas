<?php

namespace App\Commerce\Jobs;

use App\Commerce\Models\WebhookEvent;
use App\Commerce\Webhooks\HandlerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes one webhook_events row. Resolves the per-provider handler
 * (config: commerce.providers.<code>.handler), invokes it inside a tenant
 * context that matches the event's connection, and stamps progress.
 *
 * Retry policy is config-driven (commerce.retry). Final failure stamps
 * last_error + attempts but does NOT clear the row — the admin viewer's
 * replay button re-dispatches against the same idempotency_key with the
 * same payload, so a fixed bug can be replayed without storefront help.
 */
class IngestWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $eventId) {}

    public function tries(): int
    {
        return (int) config('commerce.retry.tries', 3);
    }

    /** @return array<int> */
    public function backoff(): array
    {
        return (array) config('commerce.retry.backoff', [10, 30, 90]);
    }

    public function timeout(): int
    {
        return (int) config('commerce.retry.timeout', 60);
    }

    public function handle(Container $container): void
    {
        $event = WebhookEvent::query()->with('connection.provider')->find($this->eventId);
        if (! $event) {
            Log::warning('commerce.ingest.event_missing', ['event_id' => $this->eventId]);
            return;
        }

        if ($event->isProcessed()) {
            // Already done — defensive guard against double-dispatch races.
            return;
        }

        $connection = $event->connection;
        if (! $connection) {
            $this->recordFailure($event, 'connection no longer exists');
            return;
        }

        $handlerClass = (string) config("commerce.providers.{$event->provider_code}.handler", '');
        if (! $handlerClass || ! class_exists($handlerClass)) {
            $this->recordFailure($event, "no handler configured for provider '{$event->provider_code}'");
            return;
        }

        try {
            /** @var HandlerInterface $handler */
            $handler = $container->make($handlerClass);
            if (! $handler instanceof HandlerInterface) {
                $this->recordFailure($event, "handler {$handlerClass} does not implement HandlerInterface");
                return;
            }

            $handler->handle($event, $connection);

            $event->processed_at = now();
            $event->attempts     = $event->attempts + 1;
            $event->last_error   = null;
            $event->save();
        } catch (Throwable $e) {
            $event->attempts   = $event->attempts + 1;
            $event->last_error = mb_substr($e->getMessage(), 0, 65_000);
            $event->save();

            Log::error('commerce.ingest.handler_failed', [
                'event_id'      => $event->id,
                'provider'      => $event->provider_code,
                'handler'       => $handlerClass,
                'attempts'      => $event->attempts,
                'error'         => $e->getMessage(),
            ]);

            // Re-throw so Laravel queue handles retry per the configured policy.
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        // Final failure after all retries exhausted. Job-system-level log;
        // event row's last_error already carries the per-attempt detail.
        Log::error('commerce.ingest.exhausted', [
            'event_id' => $this->eventId,
            'error'    => $e->getMessage(),
        ]);
    }

    private function recordFailure(WebhookEvent $event, string $reason): void
    {
        $event->attempts   = $event->attempts + 1;
        $event->last_error = $reason;
        $event->save();
        Log::error('commerce.ingest.no_handler', ['event_id' => $event->id, 'reason' => $reason]);
    }
}
