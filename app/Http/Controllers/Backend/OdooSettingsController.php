<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Odoo\Jobs\PushCourierBillJob;
use App\Odoo\Jobs\PushInvoiceJob;
use App\Odoo\Jobs\SyncMerchantJob;
use App\Odoo\Jobs\SyncVendorJob;
use App\Odoo\Models\CourierPartner;
use App\Odoo\Models\Settings;
use App\Odoo\Services\ApiClient;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class OdooSettingsController extends Controller
{
    public function index()
    {
        $companyId = settings()->id ?? null;
        $row = Settings::forCompany($companyId);

        $vendors = CourierPartner::where('company_id', $companyId)->orderBy('display_name')->get();

        return Inertia::render('Admin/Integrations/Odoo/Index', [
            'settings' => [
                'enabled'                     => (bool) $row->enabled,
                'host_url'                    => $row->host_url ?: '',
                'database'                    => $row->database ?: '',
                'username'                    => $row->username ?: '',
                'api_key_set'                 => (string) $row->api_key !== '',
                'api_key_tail'                => (string) $row->api_key !== '' ? substr((string) $row->api_key, -4) : null,
                'cached_uid'                  => $row->cached_uid,
                'default_invoice_journal_id'  => $row->default_invoice_journal_id ? (string) $row->default_invoice_journal_id : '',
                'default_bill_journal_id'     => $row->default_bill_journal_id ? (string) $row->default_bill_journal_id : '',
                'default_payment_journal_id'  => $row->default_payment_journal_id ? (string) $row->default_payment_journal_id : '',
                'default_product_id'          => $row->default_product_id ? (string) $row->default_product_id : '',
                'default_tax_id'              => $row->default_tax_id ? (string) $row->default_tax_id : '',
                'vat_percent'                 => (string) ($row->vat_percent ?? '15.00'),
                'ready'                       => $row->isReady(),
            ],
            'counts' => [
                'merchants_total'  => Merchant::where('company_id', $companyId)->count(),
                'merchants_synced' => Merchant::where('company_id', $companyId)->whereNotNull('odoo_partner_id')->count(),
                'merchants_failed' => Merchant::where('company_id', $companyId)->where('odoo_sync_status', 'failed')->count(),
            ],
            'vendors' => $vendors->map(fn ($v) => [
                'id'                => $v->id,
                'courier_key'       => $v->courier_key,
                'display_name'      => $v->display_name,
                'odoo_partner_id'   => $v->odoo_partner_id,
                'odoo_sync_status'  => $v->odoo_sync_status,
                'odoo_synced_at'    => optional($v->odoo_synced_at)->toIso8601String(),
            ])->values(),
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'submit'       => route('odoo.settings.update'),
                'test'         => route('odoo.settings.test'),
                'resync_all'   => route('odoo.settings.resync_all'),
                'integrations' => route('integrations.index'),
                'add_vendor'   => route('odoo.vendors.store'),
                'sync_vendor'  => route('odoo.vendors.sync', ['id' => '__ID__']),
            ],
            't' => $this->strings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'                     => ['nullable', 'boolean'],
            'host_url'                    => ['nullable', 'url', 'max:255'],
            'database'                    => ['nullable', 'string', 'max:64'],
            'username'                    => ['nullable', 'string', 'max:128'],
            'api_key'                     => ['nullable', 'string', 'max:255'],
            'default_invoice_journal_id'  => ['nullable', 'integer'],
            'default_bill_journal_id'     => ['nullable', 'integer'],
            'default_payment_journal_id'  => ['nullable', 'integer'],
            'default_product_id'          => ['nullable', 'integer'],
            'default_tax_id'              => ['nullable', 'integer'],
            'vat_percent'                 => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $row = Settings::forCompany(settings()->id ?? null);

        // If host/db/username/api_key change, drop the cached UID so next call re-authenticates.
        $invalidatesUid = in_array(true, [
            ($data['host_url'] ?? null)  !== null && $data['host_url']  !== $row->host_url,
            ($data['database'] ?? null)  !== null && $data['database']  !== $row->database,
            ($data['username'] ?? null)  !== null && $data['username']  !== $row->username,
            !empty($data['api_key']) && ! str_starts_with($data['api_key'], '••'),
        ], true);

        $row->enabled                     = (bool) ($data['enabled'] ?? false);
        $row->host_url                    = $data['host_url']  ?? $row->host_url;
        $row->database                    = $data['database']  ?? $row->database;
        $row->username                    = $data['username']  ?? $row->username;
        if (!empty($data['api_key']) && ! str_starts_with($data['api_key'], '••')) {
            $row->api_key = $data['api_key'];
        }
        $row->default_invoice_journal_id  = $data['default_invoice_journal_id']  ?? null;
        $row->default_bill_journal_id     = $data['default_bill_journal_id']     ?? null;
        $row->default_payment_journal_id  = $data['default_payment_journal_id']  ?? null;
        $row->default_product_id          = $data['default_product_id']          ?? null;
        $row->default_tax_id              = $data['default_tax_id']              ?? null;
        $row->vat_percent                 = $data['vat_percent']                 ?? 15;
        if ($invalidatesUid) $row->cached_uid = null;
        $row->save();

        Toastr::success('Odoo settings saved.', 'Success');
        return redirect()->route('odoo.settings.index');
    }

    public function test(Request $request)
    {
        try {
            $client = ApiClient::forCompany(settings()->id);
            $uid = $client->authenticate();
            Toastr::success("Connection OK — authenticated as UID {$uid}.", 'Success');
        } catch (Throwable $e) {
            Toastr::error('Odoo connection failed: ' . $e->getMessage(), 'Error');
        }
        return redirect()->route('odoo.settings.index');
    }

    public function resyncAll(Request $request)
    {
        $companyId = settings()->id;
        $merchantIds = Merchant::where('company_id', $companyId)->pluck('id');
        foreach ($merchantIds as $id) {
            SyncMerchantJob::dispatch($id);
        }
        Toastr::success("Queued Odoo resync for {$merchantIds->count()} merchants.", 'Success');
        return redirect()->route('odoo.settings.index');
    }

    public function storeVendor(Request $request)
    {
        $data = $request->validate([
            'courier_key'  => ['required', 'string', 'max:32'],
            'display_name' => ['nullable', 'string', 'max:128'],
        ]);
        $vendor = CourierPartner::firstOrNew([
            'company_id'  => settings()->id,
            'courier_key' => strtolower(trim($data['courier_key'])),
        ]);
        $vendor->display_name = $data['display_name'] ?? ucfirst($data['courier_key']);
        $vendor->save();

        SyncVendorJob::dispatch($vendor->id);
        Toastr::success('Vendor queued for Odoo sync.', 'Success');
        return redirect()->route('odoo.settings.index');
    }

    public function syncVendor(int $id)
    {
        $vendor = CourierPartner::findOrFail($id);
        SyncVendorJob::dispatch($vendor->id);
        Toastr::success('Vendor sync queued.', 'Success');
        return redirect()->route('odoo.settings.index');
    }

    private function strings(): array
    {
        return [
            'title'                => 'Odoo (ERP)',
            'breadcrumb_settings'  => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations' => 'Integrations',
            'help'                 => 'Push Rushly merchants, invoices, payments and courier bills to your Odoo ERP. Uses JSON-RPC: each tenant connects to its own Odoo instance (host + database + user + API key).',
            'connection_section'   => 'Connection',
            'host_url'             => 'Odoo host URL',
            'host_url_hint'        => 'e.g. https://mycompany.odoo.com (no trailing slash needed)',
            'database'             => 'Database',
            'database_hint'        => 'The Odoo database name (typically lowercase, e.g. mycompany)',
            'username'             => 'Username',
            'api_key'              => 'API key',
            'api_key_hint'         => 'Generate from Odoo: Settings → Users → your user → Account Security → New API Key.',
            'defaults_section'     => 'Default Odoo IDs',
            'defaults_help'        => 'Look these up in Odoo (most can be found in URL when viewing the record). Required for posting invoices/bills/payments.',
            'invoice_journal_id'   => 'Invoice journal ID',
            'bill_journal_id'      => 'Vendor bill journal ID',
            'payment_journal_id'   => 'Payment journal ID',
            'product_id'           => 'Default service product ID',
            'tax_id'               => 'Default tax record ID (15% VAT)',
            'vat_percent'          => 'VAT percent (fallback)',
            'status_section'       => 'Sync status',
            'merchants_total'      => 'Merchants total',
            'merchants_synced'     => 'Synced',
            'merchants_failed'     => 'Failed',
            'test_connection'      => 'Test connection',
            'resync_all'           => 'Resync all merchants',
            'vendors_section'      => 'Courier vendors',
            'vendors_help'         => 'Each courier must exist as a vendor partner before bills can be pushed.',
            'courier_key'          => 'Courier key',
            'display_name'         => 'Display name',
            'partner_id'           => 'Odoo partner ID',
            'sync_status'          => 'Status',
            'sync_again'           => 'Sync now',
            'add_vendor'           => 'Add courier vendor',
            'save'                 => __('levels.save') ?: 'Save',
        ];
    }
}
