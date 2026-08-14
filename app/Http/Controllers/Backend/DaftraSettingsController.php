<?php

namespace App\Http\Controllers\Backend;

use App\Daftra\Jobs\SyncClientJob;
use App\Daftra\Models\Settings;
use App\Daftra\Services\ApiClient;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class DaftraSettingsController extends Controller
{
    public function index()
    {
        $companyId = settings()->id ?? null;
        $row = Settings::forCompany($companyId);

        return Inertia::render('Admin/Integrations/Daftra/Index', [
            'settings' => [
                'enabled'                => (bool) $row->enabled,
                'subdomain'              => $row->subdomain ?: '',
                'api_key_set'            => (string) $row->api_key !== '',
                'api_key_tail'           => (string) $row->api_key !== '' ? substr((string) $row->api_key, -4) : null,
                'default_payment_method' => $row->default_payment_method ?: 'cash',
                'vat_percent'            => (string) ($row->vat_percent ?? '15.00'),
                'last_synced_at'         => optional($row->last_synced_at)->toIso8601String(),
                'ready'                  => $row->isReady(),
                'base_url'               => $row->baseUrl(),
            ],
            'counts' => [
                'merchants_total'  => Merchant::where('company_id', $companyId)->count(),
                'merchants_synced' => Merchant::where('company_id', $companyId)->whereNotNull('daftra_client_id')->count(),
                'merchants_failed' => Merchant::where('company_id', $companyId)->where('daftra_sync_status', 'failed')->count(),
            ],
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'submit'       => route('daftra.settings.update'),
                'test'         => route('daftra.settings.test'),
                'resync_all'   => route('daftra.settings.resync_all'),
                'integrations' => route('integrations.index'),
            ],
            't' => [
                'title'                  => 'Daftra (Accounting)',
                'breadcrumb_settings'    => __('menus.settings') ?: 'Settings',
                'breadcrumb_integrations'=> 'Integrations',
                'help'                   => 'Push Rushly merchants, invoices and invoice payments to your Daftra account. Per-tenant: each Rushly company connects to its own Daftra subdomain.',
                'connection_section'     => 'Connection',
                'enabled'                => 'Enable Daftra sync',
                'subdomain'              => 'Daftra subdomain',
                'subdomain_hint'         => 'The {subdomain} in https://{subdomain}.daftra.com',
                'api_key'                => 'API key',
                'api_key_hint'           => 'Generate from your Daftra settings → API Keys → Generate API key.',
                'defaults_section'       => 'Defaults',
                'payment_method'         => 'Default payment method',
                'payment_method_hint'    => 'Used when recording invoice payments. e.g. cash, bank, online.',
                'vat_percent'            => 'VAT percent',
                'status_section'         => 'Sync status',
                'merchants_total'        => 'Merchants total',
                'merchants_synced'       => 'Synced to Daftra',
                'merchants_failed'       => 'Failed',
                'test_connection'        => 'Test connection',
                'resync_all'             => 'Resync all merchants',
                'save'                   => __('levels.save') ?: 'Save',
                'cancel'                 => __('levels.cancel') ?: 'Cancel',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'                => ['nullable', 'boolean'],
            'subdomain'              => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9\-]+$/i'],
            'api_key'                => ['nullable', 'string', 'max:255'],
            'default_payment_method' => ['nullable', 'string', 'max:32'],
            'vat_percent'            => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $row = Settings::forCompany(settings()->id ?? null);
        $row->enabled                = (bool) ($data['enabled'] ?? false);
        $row->subdomain              = $data['subdomain'] ?? null;
        if (!empty($data['api_key']) && ! str_starts_with($data['api_key'], '••')) {
            $row->api_key = $data['api_key'];
        }
        $row->default_payment_method = $data['default_payment_method'] ?? 'cash';
        $row->vat_percent            = $data['vat_percent'] ?? 15;
        $row->save();

        Toastr::success('Daftra settings saved.', 'Success');
        return redirect()->route('daftra.settings.index');
    }

    public function test(Request $request)
    {
        try {
            $client = ApiClient::forCompany(settings()->id);
            $response = $client->get('clients');
            $count = count($response['data'] ?? []);
            Toastr::success("Connection OK — {$count} clients visible.", 'Success');
        } catch (Throwable $e) {
            Toastr::error('Daftra connection failed: ' . $e->getMessage(), 'Error');
        }
        return redirect()->route('daftra.settings.index');
    }

    public function resyncAll(Request $request)
    {
        $companyId = settings()->id;
        $merchantIds = Merchant::where('company_id', $companyId)->pluck('id');
        foreach ($merchantIds as $id) {
            SyncClientJob::dispatch($id);
        }
        Toastr::success("Queued Daftra resync for {$merchantIds->count()} merchants.", 'Success');
        return redirect()->route('daftra.settings.index');
    }
}
