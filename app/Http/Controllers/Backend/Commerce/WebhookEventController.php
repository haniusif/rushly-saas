<?php

namespace App\Http\Controllers\Backend\Commerce;

use App\Commerce\Jobs\IngestWebhookJob;
use App\Commerce\Models\CommerceProvider;
use App\Commerce\Models\WebhookEvent;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * /admin/commerce/webhook-events — read-only viewer + replay action.
 *
 * Lists every inbound webhook the tenant has received, filterable by
 * provider / status / date. The detail page surfaces the raw payload,
 * signature, last_error, and a "Replay" button that re-dispatches the
 * existing IngestWebhookJob against the same row (idempotency_key
 * prevents the storefront from having to redeliver).
 *
 * Reuses integrations_read / _update permissions until Phase 9 adds
 * module-scoped perms. Feature-flag-gated, same convention as
 * ConnectionController.
 */
class WebhookEventController extends Controller
{
    public function __construct()
    {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index(Request $request)
    {
        $companyId = (int) (settings()->id ?? 0);

        $q = WebhookEvent::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($p = $request->query('provider')) {
            $q->where('provider_code', $p);
        }
        if ($s = $request->query('status')) {
            if ($s === 'processed') $q->whereNotNull('processed_at');
            if ($s === 'unprocessed') $q->whereNull('processed_at');
            if ($s === 'failed') $q->whereNull('processed_at')->where('attempts', '>', 0);
        }
        if ($e = $request->query('event_type')) {
            $q->where('event_type', 'like', '%' . $e . '%');
        }

        $events = $q->limit(200)->get([
            'id', 'provider_code', 'connection_id', 'event_type',
            'idempotency_key', 'attempts', 'received_at', 'processed_at', 'last_error',
        ]);

        return Inertia::render('Admin/Commerce/WebhookEvents/Index', [
            'events'    => $events->map(fn (WebhookEvent $e) => $this->serializeSummary($e))->values(),
            'providers' => CommerceProvider::orderBy('name')->get(['code', 'name'])->values(),
            'filters'   => [
                'provider'   => $request->query('provider', ''),
                'status'     => $request->query('status', ''),
                'event_type' => $request->query('event_type', ''),
            ],
            'permissions' => [
                'replay' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'index'           => route('commerce.webhook-events.index'),
                'connections'     => route('commerce.connections.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function show(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $event = WebhookEvent::query()
            ->with('connection.provider')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
        abort_if(! $event, 404);

        return Inertia::render('Admin/Commerce/WebhookEvents/Show', [
            'event' => $this->serializeFull($event),
            'permissions' => [
                'replay' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'index'  => route('commerce.webhook-events.index'),
                'replay' => route('commerce.webhook-events.replay', $event->id),
            ],
            't' => $this->strings(),
        ]);
    }

    public function replay(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $event = WebhookEvent::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
        abort_if(! $event, 404);

        // Clear the terminal state so the job re-runs and re-stamps. Keep
        // attempts so the admin can see total tries across replays.
        $event->processed_at = null;
        $event->last_error   = null;
        $event->save();

        IngestWebhookJob::dispatch($event->id)
            ->onQueue((string) config('commerce.queue.name', 'commerce'));

        Toastr::success('Webhook replayed.', 'Success');
        return back();
    }

    // -----------------------------------------------------------------

    private function serializeSummary(WebhookEvent $e): array
    {
        return [
            'id'              => $e->id,
            'provider_code'   => $e->provider_code,
            'connection_id'   => $e->connection_id,
            'event_type'      => $e->event_type,
            'idempotency_key' => $e->idempotency_key,
            'attempts'        => $e->attempts,
            'received_at'     => optional($e->received_at)->toIso8601String(),
            'processed_at'    => optional($e->processed_at)->toIso8601String(),
            'last_error'      => $e->last_error ? mb_substr($e->last_error, 0, 140) : null,
            'status'          => $this->statusFor($e),
        ];
    }

    private function serializeFull(WebhookEvent $e): array
    {
        return array_merge($this->serializeSummary($e), [
            'connection'    => $e->connection ? [
                'id'              => $e->connection->id,
                'connection_name' => $e->connection->connection_name,
                'provider'        => $e->connection->provider->name ?? null,
                'remote_store_id' => $e->connection->remote_store_id,
            ] : null,
            'signature' => $e->signature,
            'payload'   => $e->payload,
            'last_error_full' => $e->last_error,
        ]);
    }

    private function statusFor(WebhookEvent $e): string
    {
        if ($e->processed_at) return 'processed';
        if ($e->attempts > 0) return 'failed';
        return 'pending';
    }

    private function strings(): array
    {
        return [
            'page_title'              => 'Webhook events',
            'breadcrumb_settings'     => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations' => 'Integrations',
            'breadcrumb_commerce'     => 'Commerce',
            'breadcrumb_webhooks'     => 'Webhooks',
            'help'                    => 'Every inbound webhook delivery, with HMAC verification status, processing outcome, and a replay button for fixed failures.',
            'no_events'               => 'No webhook events yet. Once a storefront points at /api/v10/commerce/{provider}/webhook, deliveries land here.',
            'filter_provider'         => 'Provider',
            'filter_status'           => 'Status',
            'filter_event_type'       => 'Event type contains',
            'filter_all'              => 'All',
            'col_id'                  => 'ID',
            'col_provider'            => 'Provider',
            'col_event'               => 'Event',
            'col_received'            => 'Received',
            'col_processed'           => 'Processed',
            'col_attempts'            => 'Attempts',
            'col_status'              => 'Status',
            'col_error'               => 'Error',
            'view'                    => 'View',
            'replay'                  => 'Replay',
            'replay_confirm'          => 'Re-dispatch this event for processing? The idempotency key stays the same; the row is updated in place.',
            'back'                    => __('levels.back') ?: 'Back',
            'payload'                 => 'Payload',
            'signature'               => 'Signature',
            'connection'              => 'Connection',
            'remote_store_id'         => 'Remote store id',
            'idempotency_key'         => 'Idempotency key',
            'status_processed'        => 'processed',
            'status_pending'          => 'pending',
            'status_failed'           => 'failed',
        ];
    }
}
