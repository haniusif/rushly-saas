<?php

namespace App\Console\Commands;

use App\Commerce\Models\CommerceApiLog;
use App\Commerce\Models\WebhookEvent;
use Illuminate\Console\Command;

/**
 * Prune Commerce-side high-volume audit tables. Runs daily from
 * Console\Kernel@3am with withoutOverlapping. Retention is
 * config('commerce.logging.retention_days') (default 30).
 *
 * Two tables are pruned:
 *   - commerce_api_logs — every outbound HTTP call. Purely diagnostic;
 *     older-than-retention rows are safe to drop.
 *   - webhook_events (processed) — event archive. Rows with
 *     processed_at IS NOT NULL AND received_at < cutoff are dropped.
 *     Unprocessed / failed events are kept regardless (retention isn't
 *     the right tool for "should we drop these").
 *
 * --dry-run reports counts without touching data. Useful for
 * capacity-planning + safety checks in staging before scheduling in
 * prod.
 */
class CommercePruneLogs extends Command
{
    protected $signature   = 'commerce:prune-logs {--dry-run : Report counts without deleting}';
    protected $description = 'Prune commerce_api_logs + processed webhook_events older than commerce.logging.retention_days';

    public function handle(): int
    {
        $days   = (int) config('commerce.logging.retention_days', 30);
        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $apiLogsCount = CommerceApiLog::query()
            ->where('created_at', '<', $cutoff)
            ->count();

        $webhookEventsCount = WebhookEvent::query()
            ->whereNotNull('processed_at')
            ->where('received_at', '<', $cutoff)
            ->count();

        $this->info("Retention: {$days} days (cutoff {$cutoff->toDateTimeString()})");
        $this->info("commerce_api_logs   : {$apiLogsCount} row(s) to prune");
        $this->info("webhook_events      : {$webhookEventsCount} processed row(s) to prune");

        if ($dryRun) {
            $this->warn('--dry-run set — no rows deleted.');
            return self::SUCCESS;
        }

        // Chunked deletes to avoid long-running lock on large tables.
        // ORDER BY id LIMIT N is a widely-supported pattern that keeps
        // each delete's row-count small enough to not blow the innodb
        // undo log or block writers for long.
        $pruneApiLogs = 0;
        do {
            $batch = CommerceApiLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(5000)
                ->delete();
            $pruneApiLogs += $batch;
        } while ($batch > 0);

        $pruneWebhookEvents = 0;
        do {
            $batch = WebhookEvent::query()
                ->whereNotNull('processed_at')
                ->where('received_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(5000)
                ->delete();
            $pruneWebhookEvents += $batch;
        } while ($batch > 0);

        $this->info("Pruned commerce_api_logs  : {$pruneApiLogs}");
        $this->info("Pruned webhook_events     : {$pruneWebhookEvents}");
        return self::SUCCESS;
    }
}
