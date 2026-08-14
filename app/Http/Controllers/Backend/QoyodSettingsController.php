<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use App\Qoyod\Jobs\PushCourierBillJob;
use App\Qoyod\Jobs\PushInvoiceJob;
use App\Qoyod\Jobs\SyncMerchantJob;
use App\Qoyod\Jobs\SyncVendorJob;
use App\Qoyod\Models\CourierVendor;
use App\Qoyod\Models\Settings;
use App\Qoyod\Services\ApiClient;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class QoyodSettingsController extends Controller
{
    public function index()
    {
        $companyId = settings()->id ?? null;
        $row = Settings::forCompany($companyId);

        $vendors = CourierVendor::where('company_id', $companyId)
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Admin/Integrations/Qoyod/Index', [
            'settings' => [
                'enabled'              => (bool) $row->enabled,
                'api_key_set'          => (string) $row->api_key !== '',
                'api_key_tail'         => (string) $row->api_key !== '' ? substr((string) $row->api_key, -4) : null,
                'default_inventory_id' => $row->default_inventory_id ? (string) $row->default_inventory_id : '',
                'default_product_id'   => $row->default_product_id ? (string) $row->default_product_id : '',
                'default_account_id'   => $row->default_account_id ? (string) $row->default_account_id : '',
                'vat_percent'          => (string) ($row->vat_percent ?? '15.00'),
                'last_synced_at'       => optional($row->last_synced_at)->toIso8601String(),
                'ready'                => $row->isReady(),
            ],
            'counts' => [
                'merchants_total'  => Merchant::where('company_id', $companyId)->count(),
                'merchants_synced' => Merchant::where('company_id', $companyId)->whereNotNull('qoyod_customer_id')->count(),
                'merchants_failed' => Merchant::where('company_id', $companyId)->where('qoyod_sync_status', 'failed')->count(),
            ],
            'vendors' => $vendors->map(fn ($v) => [
                'id'                => $v->id,
                'courier_key'       => $v->courier_key,
                'display_name'      => $v->display_name,
                'qoyod_vendor_id'   => $v->qoyod_vendor_id,
                'qoyod_sync_status' => $v->qoyod_sync_status,
                'qoyod_synced_at'   => optional($v->qoyod_synced_at)->toIso8601String(),
                'qoyod_sync_error'  => $v->qoyod_sync_error,
            ])->values(),
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'submit'        => route('qoyod.settings.update'),
                'test'          => route('qoyod.settings.test'),
                'resync_all'    => route('qoyod.settings.resync_all'),
                'integrations'  => route('integrations.index'),
                'add_vendor'    => route('qoyod.vendors.store'),
                'sync_vendor'   => route('qoyod.vendors.sync', ['id' => '__ID__']),
            ],
            't' => $this->strings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'              => ['nullable', 'boolean'],
            'api_key'              => ['nullable', 'string', 'max:255'],
            'default_inventory_id' => ['nullable', 'integer'],
            'default_product_id'   => ['nullable', 'integer'],
            'default_account_id'   => ['nullable', 'integer'],
            'vat_percent'          => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $row = Settings::forCompany(settings()->id ?? null);
        $row->enabled              = (bool) ($data['enabled'] ?? false);
        // Only overwrite api_key if a fresh non-masked value is provided.
        if (!empty($data['api_key']) && ! str_starts_with($data['api_key'], '••')) {
            $row->api_key = $data['api_key'];
        }
        $row->default_inventory_id = $data['default_inventory_id'] ?? null;
        $row->default_product_id   = $data['default_product_id'] ?? null;
        $row->default_account_id   = $data['default_account_id'] ?? null;
        $row->vat_percent          = $data['vat_percent'] ?? 15;
        $row->save();

        Toastr::success('Qoyod settings saved.', 'Success');
        return redirect()->route('qoyod.settings.index');
    }

    public function test(Request $request)
    {
        try {
            $client = ApiClient::forCompany(settings()->id);
            $response = $client->get('accounts');
            $count = count($response['accounts'] ?? $response ?? []);
            Toastr::success("Connection OK — {$count} accounts visible.", 'Success');
        } catch (Throwable $e) {
            Toastr::error('Qoyod connection failed: ' . $e->getMessage(), 'Error');
        }
        return redirect()->route('qoyod.settings.index');
    }

    public function resyncAll(Request $request)
    {
        $companyId = settings()->id;
        $merchantIds = Merchant::where('company_id', $companyId)->pluck('id');
        foreach ($merchantIds as $id) {
            SyncMerchantJob::dispatch($id);
        }
        Toastr::success("Queued resync for {$merchantIds->count()} merchants.", 'Success');
        return redirect()->route('qoyod.settings.index');
    }

    public function storeVendor(Request $request)
    {
        $data = $request->validate([
            'courier_key'  => ['required', 'string', 'max:32'],
            'display_name' => ['nullable', 'string', 'max:128'],
        ]);
        $vendor = CourierVendor::firstOrNew([
            'company_id'  => settings()->id,
            'courier_key' => strtolower(trim($data['courier_key'])),
        ]);
        $vendor->display_name = $data['display_name'] ?? ucfirst($data['courier_key']);
        $vendor->save();

        SyncVendorJob::dispatch($vendor->id);
        Toastr::success('Vendor queued for sync.', 'Success');
        return redirect()->route('qoyod.settings.index');
    }

    public function syncVendor(int $id)
    {
        $vendor = CourierVendor::findOrFail($id);
        SyncVendorJob::dispatch($vendor->id);
        Toastr::success('Vendor sync queued.', 'Success');
        return redirect()->route('qoyod.settings.index');
    }

    private function strings(): array
    {
        return [
            'title'                => 'Qoyod (Accounting)',
            'breadcrumb_settings'  => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations' => 'Integrations',
            'help'                 => 'Push Rushly merchants, invoices, payments, and courier bills to Qoyod as the ZATCA-compliant accounting system of record. Each company has its own Qoyod account.',
            'connection_section'   => 'Connection',
            'api_key'              => 'API key',
            'api_key_hint'         => 'Generated from your Qoyod General Settings page.',
            'enabled'              => 'Enable Qoyod sync',
            'defaults_section'     => 'Default Qoyod IDs',
            'defaults_help'        => 'These IDs are used as the line-item product, the warehouse, and the bank account on every pushed invoice/bill/payment. Look them up in your Qoyod dashboard.',
            'inventory_id'         => 'Default inventory (warehouse) ID',
            'product_id'           => 'Default service product ID',
            'account_id'           => 'Default bank/cash account ID',
            'vat_percent'          => 'VAT percent',
            'status_section'       => 'Sync status',
            'merchants_total'      => 'Merchants total',
            'merchants_synced'     => 'Synced to Qoyod',
            'merchants_failed'     => 'Failed',
            'test_connection'      => 'Test connection',
            'resync_all'           => 'Resync all merchants',
            'vendors_section'      => 'Courier vendors',
            'vendors_help'         => 'Each courier (Aramex, Zajel, etc.) must exist as a Qoyod vendor before bills can be pushed. Add the couriers you use; the table syncs them to Qoyod.',
            'courier_key'          => 'Courier key',
            'display_name'         => 'Display name',
            'vendor_id'            => 'Qoyod vendor ID',
            'sync_status'          => 'Status',
            'sync_again'           => 'Sync now',
            'add_vendor'           => 'Add courier vendor',
            'save'                 => __('levels.save') ?: 'Save',
            'cancel'               => __('levels.cancel') ?: 'Cancel',
        ];
    }
}
