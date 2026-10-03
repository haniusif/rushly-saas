<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Logestechs\Models\Settings;
use App\Models\Backend\Parcels_3pl;
use App\Services\LogestechsService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class LogestechsSettingsController extends Controller
{
    public function index()
    {
        $companyId = settings()->id ?? null;
        $row       = Settings::forCompany($companyId);

        // Surface what the service will actually use after the DB-row → env
        // fallback, so the admin sees an honest "effective" view.
        $effectiveBase   = (string) ($row->base_url ?: config('services.logestechs.base_url'));
        $effectiveSource = (string) ($row->integration_source ?: (config('services.logestechs.integration_source') ?: 'API'));

        $parcelsAssigned = Parcels_3pl::where('parcel_3pl_name', 'logestechs')
            ->whereHas('parcel', fn ($q) => $q->where('company_id', $companyId))
            ->count();

        return Inertia::render('Admin/Integrations/Logestechs/Index', [
            'settings' => [
                'enabled'                   => (bool) $row->enabled,
                'base_url'                  => (string) ($row->base_url ?? ''),
                'integration_source'        => (string) ($row->integration_source ?? ''),
                'default_target_company_id' => (string) ($row->default_target_company_id ?? ''),
                'default_email'             => (string) ($row->default_email ?? ''),
                'effective_base_url'        => $effectiveBase,
                'effective_source'          => $effectiveSource,
                'env_base_url'              => (string) config('services.logestechs.base_url'),
                'ready'                     => $row->isReady(),
            ],
            'counts' => [
                'parcels_assigned' => $parcelsAssigned,
            ],
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'submit'       => route('logestechs.settings.update'),
                'test'         => route('logestechs.settings.test'),
                'integrations' => route('integrations.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'                   => ['nullable', 'boolean'],
            'base_url'                  => ['nullable', 'url', 'max:255'],
            'integration_source'        => ['nullable', 'string', 'max:64'],
            'default_target_company_id' => ['nullable', 'string', 'max:64'],
            'default_email'             => ['nullable', 'email', 'max:191'],
        ]);

        $row = Settings::forCompany(settings()->id ?? null);
        $row->enabled                   = (bool) ($data['enabled'] ?? false);
        $row->base_url                  = $data['base_url'] ?: null;
        $row->integration_source        = $data['integration_source'] ?: null;
        $row->default_target_company_id = $data['default_target_company_id'] ?: null;
        $row->default_email             = $data['default_email'] ?: null;
        $row->save();

        Toastr::success('Logestechs settings saved.', 'Success');
        return redirect()->route('logestechs.settings.index');
    }

    /**
     * Probe the resolved base URL against /addresses/villages — the only
     * Logestechs guest endpoint we know works without per-shipment creds
     * (confirmed live against company 496). A `target_company_id` is
     * accepted as input so admins can verify they can reach a real account.
     */
    public function test(Request $request)
    {
        $companyId = trim((string) $request->input('target_company_id'));

        try {
            $svc = app(LogestechsService::class);
            if (! $svc->isConfigured()) {
                Toastr::error('Set the Logestechs base URL before testing.', 'Error');
                return redirect()->route('logestechs.settings.index');
            }

            if ($companyId === '') {
                Toastr::warning('Tip: provide a target Logestechs company id for a real round-trip test. Probing the base URL only.', 'Heads up');
                $companyId = '0';
            }

            $resp = $svc->getVillages($companyId, '');
            if (! empty($resp['_error'])) {
                Toastr::error('Logestechs unreachable: ' . ($resp['message'] ?? 'unknown error'), 'Error');
            } else {
                $count = is_array($resp['data'] ?? null) ? count($resp['data']) : 0;
                Toastr::success("Connection OK — {$count} village(s) returned for company {$companyId}.", 'Success');
            }
        } catch (Throwable $e) {
            Toastr::error('Logestechs connection failed: ' . $e->getMessage(), 'Error');
        }

        return redirect()->route('logestechs.settings.index');
    }

    private function strings(): array
    {
        return [
            'title'                       => 'Logestechs (3PL)',
            'breadcrumb_settings'         => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations'     => 'Integrations',
            'help'                        => 'Outbound handoff to the Logestechs platform. Each shipment routes to a specific Logestechs company chosen at assign-time; only the base URL and integration label are configured here. The customer email + password are still typed per call.',
            'connection_section'          => 'Connection',
            'enabled'                     => 'Enable Logestechs handoff',
            'base_url'                    => 'Base URL',
            'base_url_hint'               => 'Overrides LOGESTECHS_BASE_URL from .env. Production: https://apisv2.logestechs.com/api',
            'integration_source'          => 'Integration source',
            'integration_source_hint'     => 'Label sent on every createShipment payload (defaults to "API"). Some Logestechs companies require a specific value like "WOOCOMMERCE".',
            'env_fallback'                => 'env fallback',
            'effective_section'           => 'Effective configuration',
            'effective_help'              => 'What the service will actually call with after applying the DB-row → .env fallback.',
            'defaults_section'            => 'Assign-time defaults',
            'defaults_help'               => 'Pre-fill the bulk-assign form so couriers don\'t retype the same Logestechs company id and account email every batch. The password is never stored — it\'s typed per submission.',
            'default_target_company_id'   => 'Default target company id',
            'default_target_company_id_hint' => 'Pre-fills the "Logestechs Company ID" input on the bulk-assign screen.',
            'default_email'               => 'Default Logestechs account email',
            'default_email_hint'          => 'Pre-fills the "Logestechs Account Email" input on the bulk-assign screen.',
            'status_section'              => 'Status',
            'parcels_assigned'            => 'Parcels assigned',
            'test_target_company_id'      => 'Test target company id (optional)',
            'test_target_company_id_hint' => 'Provide a real Logestechs company id (e.g. 496) to verify end-to-end reachability via /addresses/villages.',
            'test_connection'             => 'Test connection',
            'save'                        => __('levels.save') ?: 'Save',
            'cancel'                      => __('levels.cancel') ?: 'Cancel',
            'back'                        => __('levels.back') ?: 'Back',
        ];
    }
}
