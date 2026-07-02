<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Fulfillment\Models\FulfillmentDefault;
use App\Http\Controllers\Controller;
use App\Models\Backend\GeneralSettings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Super-admin business-logic surface for the FulfillmentRouter's
 * fallback path. Two tiers:
 *   - Global row (company_id = NULL) — platform-wide default
 *   - Per-tenant overrides — one row per company that wants different
 *     fallback behavior
 *
 * The Merchant.services JSON (last_mile / fulfillment / storage) is
 * the ONLY thing driving service→strategy pickup here. FulfillmentService
 * consults this when router matches no explicit fulfillment_route.
 */
class FulfillmentDefaultsController extends Controller
{
    public function __construct()
    {
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index()
    {
        $global    = FulfillmentDefault::global();
        $overrides = FulfillmentDefault::query()
            ->whereNotNull('company_id')
            ->orderBy('company_id')
            ->get()
            ->map(fn (FulfillmentDefault $r) => $this->serialize($r))
            ->values();

        // List of tenants available as override candidates (those with
        // an active general_settings row).
        $tenants = GeneralSettings::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name ?: "Tenant #{$t->id}"])
            ->values();

        return Inertia::render('SuperAdmin/BusinessLogic/FulfillmentDefaults/Index', [
            'global'     => $this->serialize($global),
            'overrides'  => $overrides,
            'tenants'    => $tenants,
            'strategies' => $this->strategyOptions(),
            'services'   => array_keys(FulfillmentDefault::SERVICE_STRATEGY_MAP),
            'urls'       => [
                'update_global'      => route('super-admin.business-logic.fulfillment-defaults.update-global'),
                'store_override'     => route('super-admin.business-logic.fulfillment-defaults.store-override'),
                // Template with __ID__ placeholder. Nested closures with
                // required params crash Inertia's resolveArrayableProperties()
                // via App::call() (BindingResolutionException).
                'destroy_override_tpl' => route('super-admin.business-logic.fulfillment-defaults.destroy-override', ['id' => '__ID__']),
            ],
            't' => [
                'page_title' => 'Fulfillment defaults',
                'subtitle'   => 'Business-logic fallback for the fulfillment router. When no fulfillment_route matches an order, these rules decide the strategy.',
                'global_h'   => 'Global platform defaults',
                'overrides_h' => 'Per-tenant overrides',
                'add_override' => 'Add tenant override',
                'save'        => 'Save',
                'delete'      => 'Delete',
                'default_strategy' => 'Default strategy (last resort)',
                'service_h'   => 'Service → strategy mapping',
                'service_last_mile'   => 'last_mile →',
                'service_fulfillment' => 'fulfillment →',
                'service_storage'     => 'storage →',
            ],
        ]);
    }

    public function updateGlobal(Request $request)
    {
        $data = $this->validateForm($request);
        $global = FulfillmentDefault::global();
        $global->fill($data);
        $global->updated_by = optional($request->user())->id;
        $global->save();
        Toastr::success('Global defaults saved.', 'Success');
        return back();
    }

    public function storeOverride(Request $request)
    {
        $data = $this->validateForm($request, requireCompany: true);
        $override = FulfillmentDefault::updateOrCreate(
            ['company_id' => (int) $data['company_id']],
            [
                'default_strategy'             => $data['default_strategy'],
                'service_last_mile_strategy'   => $data['service_last_mile_strategy'],
                'service_fulfillment_strategy' => $data['service_fulfillment_strategy'],
                'service_storage_strategy'     => $data['service_storage_strategy'],
                'updated_by'                   => optional($request->user())->id,
            ],
        );
        Toastr::success("Override saved for tenant #{$override->company_id}.", 'Success');
        return back();
    }

    public function destroyOverride(int $id)
    {
        $row = FulfillmentDefault::query()->whereNotNull('company_id')->where('id', $id)->first();
        abort_if(! $row, 404);
        $row->delete();
        Toastr::success('Override removed — tenant now inherits global defaults.', 'Success');
        return back();
    }

    // -----------------------------------------------------------------

    private function validateForm(Request $request, bool $requireCompany = false): array
    {
        $strategyValues = array_keys((array) config('fulfillment.strategies', []));
        $strategyRule   = 'in:' . implode(',', $strategyValues);

        $rules = [
            'default_strategy'             => ['nullable', $strategyRule],
            'service_last_mile_strategy'   => ['nullable', $strategyRule],
            'service_fulfillment_strategy' => ['nullable', $strategyRule],
            'service_storage_strategy'     => ['nullable', $strategyRule],
        ];
        if ($requireCompany) {
            $rules['company_id'] = ['required', 'integer', 'exists:general_settings,id'];
        }
        return $request->validate($rules);
    }

    private function serialize(FulfillmentDefault $r): array
    {
        return [
            'id'                             => $r->id,
            'company_id'                     => $r->company_id,
            'default_strategy'               => $r->default_strategy,
            'service_last_mile_strategy'     => $r->service_last_mile_strategy,
            'service_fulfillment_strategy'   => $r->service_fulfillment_strategy,
            'service_storage_strategy'       => $r->service_storage_strategy,
            'updated_at'                     => optional($r->updated_at)->toIso8601String(),
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
}
