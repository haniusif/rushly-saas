<?php

namespace App\Http\Controllers\Backend\Commerce;

use App\Commerce\Models\CommerceConnection;
use App\Http\Controllers\Controller;
use App\Shipping\Models\ShippingConnection;
use Inertia\Inertia;

/**
 * /admin/commerce/health — single-page dashboard aggregating the
 * status of every Commerce + Shipping connection for the current
 * tenant. Answers the "is any integration currently broken?" question
 * without clicking through each connection's edit page.
 *
 * Read-only: no mutations. Feature-flag-gated same as the rest of
 * `/admin/commerce/*`. Reuses integrations_read permission.
 */
class HealthController extends Controller
{
    public function __construct()
    {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index()
    {
        $companyId = (int) (settings()->id ?? 0);

        $commerce = CommerceConnection::query()
            ->with('provider')
            ->where('company_id', $companyId)
            ->orderBy('connection_name')
            ->get()
            ->map(fn (CommerceConnection $c) => [
                'kind'            => 'commerce',
                'id'              => $c->id,
                'name'            => $c->connection_name,
                'provider'        => $c->provider->name ?? $c->provider->code ?? '—',
                'provider_code'   => $c->provider->code ?? null,
                'status'          => $c->status,
                'health'          => $this->commerceHealth($c),
                'remote_id'       => $c->remote_store_id,
                'is_default'      => (bool) $c->is_default,
                'last_tested_at'  => optional($c->last_tested_at)->toIso8601String(),
                'last_sync_at'    => optional($c->last_sync_at)->toIso8601String(),
                'last_event_at'   => optional($c->last_event_at)->toIso8601String(),
                'edit_url'        => route('commerce.connections.edit', $c->id),
            ])->values();

        $shipping = ShippingConnection::query()
            ->with('provider')
            ->where('company_id', $companyId)
            ->orderBy('connection_name')
            ->get()
            ->map(fn (ShippingConnection $c) => [
                'kind'            => 'shipping',
                'id'              => $c->id,
                'name'            => $c->connection_name,
                'provider'        => $c->provider->name ?? $c->provider->code ?? '—',
                'provider_code'   => $c->provider->code ?? null,
                'status'          => $c->status,
                'health'          => $this->shippingHealth($c),
                'remote_id'       => $c->remote_company_id,
                'is_default'      => (bool) $c->is_default,
                'last_tested_at'  => optional($c->last_tested_at)->toIso8601String(),
                'last_sync_at'    => optional($c->last_sync_at)->toIso8601String(),
                'last_event_at'   => null,
                'edit_url'        => route('shipping.connections.edit', $c->id),
            ])->values();

        return Inertia::render('Admin/Commerce/Health/Index', [
            'connections' => $commerce->merge($shipping)->values(),
            'summary'     => $this->summarise($commerce->merge($shipping)),
            'urls' => [
                'commerce_connections' => route('commerce.connections.index'),
                'shipping_connections' => route('shipping.connections.index'),
            ],
            't' => [
                'page_title'  => 'Integration health',
                'subtitle'    => 'Live view of every Commerce and Shipping connection this tenant has configured. Click a card to open the connection\'s edit page.',
                'no_rows'     => 'No connections configured yet.',
                'health_ok'       => 'healthy',
                'health_stale'    => 'no recent activity',
                'health_paused'   => 'paused',
                'health_invalid'  => 'invalid / needs re-auth',
                'health_unknown'  => 'never tested',
            ],
        ]);
    }

    /**
     * Health heuristic for a CommerceConnection:
     *   - status in {invalid, reauth_required} → 'invalid'
     *   - status = paused → 'paused'
     *   - no last_tested_at + no last_event_at → 'unknown'
     *   - last_event_at older than 30 days OR never → 'stale'
     *   - otherwise → 'ok'
     */
    private function commerceHealth(CommerceConnection $c): string
    {
        if (in_array($c->status, ['invalid', 'reauth_required'], true)) return 'invalid';
        if ($c->status === 'paused') return 'paused';
        if (! $c->last_tested_at && ! $c->last_event_at) return 'unknown';

        $latest = $c->last_event_at ?: $c->last_tested_at;
        if ($latest && $latest->diffInDays() > 30) return 'stale';

        return 'ok';
    }

    /**
     * Shipping health heuristic is simpler — no webhooks, so we key on
     * last_tested_at + status.
     */
    private function shippingHealth(ShippingConnection $c): string
    {
        if (in_array($c->status, ['invalid'], true)) return 'invalid';
        if ($c->status === 'paused') return 'paused';
        if (! $c->last_tested_at) return 'unknown';
        if ($c->last_tested_at->diffInDays() > 30) return 'stale';
        return 'ok';
    }

    private function summarise($all): array
    {
        return [
            'total'     => $all->count(),
            'ok'        => $all->where('health', 'ok')->count(),
            'stale'     => $all->where('health', 'stale')->count(),
            'invalid'   => $all->where('health', 'invalid')->count(),
            'paused'    => $all->where('health', 'paused')->count(),
            'unknown'   => $all->where('health', 'unknown')->count(),
        ];
    }
}
