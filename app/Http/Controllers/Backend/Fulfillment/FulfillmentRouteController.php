<?php

namespace App\Http\Controllers\Backend\Fulfillment;

use App\Commerce\Models\CommerceProvider;
use App\Fulfillment\Models\FulfillmentRoute;
use App\Http\Controllers\Controller;
use App\Shipping\Models\ShippingConnection;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FulfillmentRouteController extends Controller
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
        $routes = FulfillmentRoute::query()
            ->where('company_id', $companyId)
            ->orderBy('priority')
            ->get();

        return Inertia::render('Admin/Oms/FulfillmentRoutes/Index', [
            'routes'   => $routes->map(fn (FulfillmentRoute $r) => $this->serialize($r))->values(),
            'strategies' => $this->strategyOptions(),
            'urls'     => [
                'create' => route('fulfillment.routes.create'),
                'index'  => route('fulfillment.routes.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Oms/FulfillmentRoutes/Edit', [
            'mode'  => 'create',
            'route' => null,
            ...$this->formData(),
            'urls'  => [
                'submit' => route('fulfillment.routes.store'),
                'index'  => route('fulfillment.routes.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateForm($request);
        $data['company_id'] = (int) (settings()->id ?? 0);
        $route = FulfillmentRoute::create($data);
        Toastr::success('Route added.', 'Success');
        return redirect()->route('fulfillment.routes.edit', $route->id);
    }

    public function edit(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $route = FulfillmentRoute::where('id', $id)->where('company_id', $companyId)->first();
        abort_if(! $route, 404);

        return Inertia::render('Admin/Oms/FulfillmentRoutes/Edit', [
            'mode'  => 'edit',
            'route' => $this->serialize($route),
            ...$this->formData(),
            'urls'  => [
                'submit'  => route('fulfillment.routes.update', $route->id),
                'destroy' => route('fulfillment.routes.destroy', $route->id),
                'index'   => route('fulfillment.routes.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $route = FulfillmentRoute::where('id', $id)->where('company_id', $companyId)->first();
        abort_if(! $route, 404);

        $data = $this->validateForm($request);
        $route->update($data);
        Toastr::success('Route updated.', 'Success');
        return back();
    }

    public function destroy(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $route = FulfillmentRoute::where('id', $id)->where('company_id', $companyId)->first();
        abort_if(! $route, 404);
        $route->delete();
        Toastr::success('Route removed.', 'Success');
        return redirect()->route('fulfillment.routes.index');
    }

    // -----------------------------------------------------------------

    private function validateForm(Request $request): array
    {
        return $request->validate([
            'name'                   => ['required', 'string', 'max:191'],
            'priority'               => ['nullable', 'integer', 'min:0'],
            'is_active'              => ['nullable', 'boolean'],
            'merchant_id'            => ['nullable', 'integer'],
            'source_provider_code'   => ['nullable', 'string', 'max:32'],
            'shipping_city_id'       => ['nullable', 'integer'],
            'shipping_country'       => ['nullable', 'string', 'max:100'],
            'min_total'              => ['nullable', 'numeric', 'min:0'],
            'max_total'              => ['nullable', 'numeric', 'min:0'],
            'is_cod'                 => ['nullable', 'boolean'],
            'strategy'               => ['required', 'string', 'in:' . implode(',', array_keys((array) config('fulfillment.strategies', [])))],
            'shipping_connection_id' => ['nullable', 'integer'],
            'hub_id'                 => ['nullable', 'integer'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function serialize(FulfillmentRoute $r): array
    {
        return [
            'id'                     => $r->id,
            'name'                   => $r->name,
            'priority'               => $r->priority,
            'is_active'              => $r->is_active,
            'merchant_id'            => $r->merchant_id,
            'source_provider_code'   => $r->source_provider_code,
            'shipping_city_id'       => $r->shipping_city_id,
            'shipping_country'       => $r->shipping_country,
            'min_total'              => $r->min_total,
            'max_total'              => $r->max_total,
            'is_cod'                 => $r->is_cod,
            'strategy'               => $r->strategy,
            'shipping_connection_id' => $r->shipping_connection_id,
            'hub_id'                 => $r->hub_id,
            'notes'                  => $r->notes,
            'updated_at'             => optional($r->updated_at)->toIso8601String(),
        ];
    }

    private function formData(): array
    {
        $companyId = (int) (settings()->id ?? 0);
        return [
            'strategies' => $this->strategyOptions(),
            'providers'  => CommerceProvider::orderBy('name')->get(['code', 'name'])->values(),
            'shipping_connections' => ShippingConnection::query()
                ->where('company_id', $companyId)
                ->with('provider')
                ->orderBy('connection_name')
                ->get()
                ->map(fn ($c) => [
                    'id'       => $c->id,
                    'name'     => $c->connection_name,
                    'provider' => $c->provider->name ?? null,
                ])->values(),
        ];
    }

    private function strategyOptions(): array
    {
        $out = [];
        foreach ((array) config('fulfillment.strategies', []) as $code => $conf) {
            $out[] = ['code' => $code, 'label' => $conf['label'] ?? $code];
        }
        return $out;
    }

    private function strings(): array
    {
        return [
            'page_title'              => 'Fulfillment routes',
            'breadcrumb_settings'     => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations' => 'Integrations',
            'breadcrumb_commerce'     => 'Commerce',
            'breadcrumb_oms'          => 'OMS',
            'breadcrumb_routes'       => 'Fulfillment routes',
            'help'                    => 'Declarative rules — router picks the first active match by priority. Every condition is AND\'d; blank fields mean "don\'t filter on this column".',
            'no_routes'               => 'No routes yet. Without a route, orders stay in pending until you add one or set FULFILLMENT_DEFAULT_STRATEGY in env.',
            'add'                     => 'Add route',
            'save'                    => __('levels.save') ?: 'Save',
            'delete'                  => __('levels.delete') ?: 'Delete',
            'back'                    => __('levels.back') ?: 'Back',
        ];
    }
}
