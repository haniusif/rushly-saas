<?php

namespace App\Http\Controllers\Backend\Oms;

use App\Commerce\Models\CommerceConnection;
use App\Http\Controllers\Controller;
use App\Oms\Enums\OrderStatus;
use App\Oms\Enums\PaymentStatus;
use App\Oms\Models\Order;
use App\Oms\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * /admin/oms/orders — read-only viewer for the canonical OMS orders.
 *
 * Read-only in Phase 5. Mutations (status changes, manual retries,
 * Fulfillment dispatch) land in Phase 6+. Feature-flag-gated, same
 * convention as the other Commerce/OMS pages.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $repo,
    ) {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index(Request $request)
    {
        $companyId = (int) (settings()->id ?? 0);

        $status       = $request->query('status');
        $connectionId = $request->query('connection_id');

        $orders = $this->repo->listForCompany(
            companyId:    $companyId,
            status:       $status ?: null,
            connectionId: $connectionId ? (int) $connectionId : null,
            limit:        200,
        );

        return Inertia::render('Admin/Oms/Orders/Index', [
            'orders'     => $orders->map(fn (Order $o) => $this->serializeSummary($o))->values(),
            'connections' => CommerceConnection::query()
                ->where('company_id', $companyId)
                ->with('provider')
                ->orderBy('connection_name')
                ->get(['id', 'connection_name', 'provider_id'])
                ->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->connection_name,
                    'provider' => $c->provider->name ?? null,
                ])->values(),
            'statuses'   => OrderStatus::ALL,
            'filters'    => [
                'status'        => $status ?: '',
                'connection_id' => $connectionId ?: '',
            ],
            'urls'       => [
                'index'       => route('oms.orders.index'),
                'connections' => route('commerce.connections.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function show(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $order = $this->repo->findForCompany($id, $companyId);
        abort_if(! $order, 404);

        // Load events + items in one shot so the detail page renders
        // without N+1s.
        $order->load(['items', 'events', 'connection.provider', 'webhookEvent']);

        return Inertia::render('Admin/Oms/Orders/Show', [
            'order' => $this->serializeFull($order),
            'urls'  => [
                'index' => route('oms.orders.index'),
                'webhook_event' => $order->webhook_event_id
                    ? route('commerce.webhook-events.show', $order->webhook_event_id)
                    : null,
            ],
            't' => $this->strings(),
        ]);
    }

    // ----------------------------------------------------------------

    private function serializeSummary(Order $o): array
    {
        return [
            'id'                   => $o->id,
            'source_provider_code' => $o->source_provider_code,
            'remote_order_id'      => $o->remote_order_id,
            'remote_order_number'  => $o->remote_order_number,
            'connection'           => $o->connection ? [
                'id'   => $o->connection->id,
                'name' => $o->connection->connection_name,
            ] : null,
            'customer_name'        => $o->customer_name,
            'customer_phone'       => $o->customer_phone,
            'shipping_city_name'   => $o->shipping_city_name,
            'total'                => $o->total,
            'currency'             => $o->currency,
            'cod_amount'           => $o->cod_amount,
            'status'               => $o->status,
            'payment_status'       => $o->payment_status,
            'fulfillment_status'   => $o->fulfillment_status,
            'occurred_at'          => optional($o->occurred_at)->toIso8601String(),
            'received_at'          => optional($o->received_at)->toIso8601String(),
        ];
    }

    private function serializeFull(Order $o): array
    {
        return array_merge($this->serializeSummary($o), [
            'provider_status' => $o->provider_status,
            'payment_method'  => $o->payment_method,
            'customer_email'  => $o->customer_email,
            'shipping'        => [
                'name'      => $o->shipping_name,
                'phone'     => $o->shipping_phone,
                'line1'     => $o->shipping_line1,
                'line2'     => $o->shipping_line2,
                'city_name' => $o->shipping_city_name,
                'area_name' => $o->shipping_area_name,
                'region'    => $o->shipping_region,
                'country'   => $o->shipping_country,
                'postcode'  => $o->shipping_postcode,
                'city_id'   => $o->shipping_city_id,
                'area_id'   => $o->shipping_area_id,
            ],
            'totals' => [
                'subtotal'     => $o->subtotal,
                'tax'          => $o->tax,
                'shipping_fee' => $o->shipping_fee,
                'discount'     => $o->discount,
                'total'        => $o->total,
                'cod_amount'   => $o->cod_amount,
                'currency'     => $o->currency,
            ],
            'items' => $o->items->map(fn ($i) => [
                'id'          => $i->id,
                'sort_order'  => $i->sort_order,
                'sku'         => $i->sku,
                'name'        => $i->name,
                'quantity'    => $i->quantity,
                'unit_price'  => $i->unit_price,
                'total_price' => $i->total_price,
                'currency'    => $i->currency,
            ])->values(),
            'events' => $o->events->map(fn ($e) => [
                'id'          => $e->id,
                'event_type'  => $e->event_type,
                'payload'     => $e->payload,
                'occurred_at' => optional($e->occurred_at)->toIso8601String(),
            ])->values(),
            'note'                => $o->note,
            'normalized_snapshot' => $o->normalized_snapshot,
            'extra'               => $o->extra,
            'webhook_event_id'    => $o->webhook_event_id,
        ]);
    }

    private function strings(): array
    {
        return [
            'page_title'              => 'Orders',
            'breadcrumb_settings'     => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations' => 'Integrations',
            'breadcrumb_commerce'     => 'Commerce',
            'breadcrumb_orders'       => 'Orders',
            'help'                    => 'Canonical OMS orders materialised from webhook deliveries. Distinct from parcels — one Order can produce 0..N Parcels.',
            'no_orders'               => 'No orders yet. Once a commerce webhook fires and normalisation succeeds, an Order row lands here.',
            'filter_status'           => 'Status',
            'filter_connection'       => 'Connection',
            'filter_all'              => 'All',
            'col_id'                  => 'ID',
            'col_source'              => 'Source',
            'col_remote'              => 'Remote #',
            'col_customer'            => 'Customer',
            'col_city'                => 'City',
            'col_total'               => 'Total',
            'col_status'              => 'Status',
            'col_payment'             => 'Payment',
            'col_fulfillment'         => 'Fulfillment',
            'col_received'            => 'Received',
            'view'                    => 'View',
            'back'                    => __('levels.back') ?: 'Back',
            'items'                   => 'Line items',
            'shipping'                => 'Shipping',
            'totals'                  => 'Totals',
            'events'                  => 'Audit trail',
            'snapshot'                => 'Normalized snapshot',
            'webhook_source'          => 'Source webhook',
        ];
    }
}
