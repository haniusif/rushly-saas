<?php

namespace App\Commerce\Jobs;

use App\Commerce\Contracts\SupportsInventorySync;
use App\Commerce\DTOs\CommerceConnectionDTO;
use App\Commerce\Factory\CommerceProviderFactory;
use App\Commerce\Models\CommerceConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pushes stock updates to one CommerceConnection. Dispatched by
 * PushStockToConnectedChannelsListener — one job per (connection,
 * update-batch) pair.
 *
 * Idempotency: providers expected to treat their inventory endpoints
 * as absolute-value (set-quantity-to-X), so retries are safe. Provider
 * impls are responsible for graceful handling of unknown SKUs (skip +
 * log, not throw) so a single bad SKU in a batch doesn't fail the
 * whole job.
 *
 * $updates shape: `[['sku' => string, 'quantity' => int], ...]`
 */
class PushStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int   $connectionId,
        public readonly array $updates,
    ) {}

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

    public function handle(CommerceProviderFactory $factory): void
    {
        $connection = CommerceConnection::query()->with('provider')->find($this->connectionId);
        if (! $connection) {
            Log::warning('commerce.push_stock.connection_missing', ['connection_id' => $this->connectionId]);
            return;
        }
        if ($connection->status !== 'active') {
            // Not an error — connection was paused between dispatch and
            // job run. Skip cleanly.
            return;
        }

        $provider = $factory->make($connection->provider->code);
        if (! $provider instanceof SupportsInventorySync) {
            Log::warning('commerce.push_stock.provider_does_not_support', [
                'connection_id' => $connection->id,
                'provider'      => $connection->provider->code,
            ]);
            return;
        }

        try {
            $provider->pushInventoryUpdate(CommerceConnectionDTO::fromModel($connection), $this->updates);
        } catch (Throwable $e) {
            Log::error('commerce.push_stock.failed', [
                'connection_id' => $connection->id,
                'provider'      => $connection->provider->code,
                'skus'          => array_column($this->updates, 'sku'),
                'error'         => $e->getMessage(),
            ]);
            throw $e; // let queue handle retry / final failure
        }
    }
}
