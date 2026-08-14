<?php

namespace App\Http\Controllers\Backend\Commerce;

use App\Commerce\Exceptions\ConnectionTestFailedException;
use App\Commerce\Factory\CommerceProviderFactory;
use App\Commerce\Models\CommerceConnection;
use App\Commerce\Models\CommerceProvider;
use App\Commerce\Repositories\CommerceConnectionRepository;
use App\Commerce\Services\ConnectionService;
use App\Http\Controllers\Controller;
use App\Models\Backend\Merchant;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * /admin/commerce/connections — generic CRUD for commerce connections.
 *
 * Lives under the existing tenant `admin/` route group so the middleware
 * stack (auth + IsInstalled + subscription + permission) applies
 * unchanged. Permissions reuse `integrations_read` / `integrations_update`
 * (same model used by Shipping connections + the legacy Integrations page)
 * until Phase 9 introduces per-module permissions.
 *
 * The entire surface is gated by config('features.commerce_layer'). When
 * the flag is off, every endpoint 404s — the routes still resolve so other
 * code can call `route('commerce.connections.index')` without exception.
 */
class ConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionService $service,
        private readonly CommerceConnectionRepository $repo,
        private readonly CommerceProviderFactory $factory,
    ) {
        // Gate user-visible behavior on the feature flag, but skip during
        // console reflection (route:list / route:cache instantiate the
        // controller and would otherwise 404 on every command).
        if (! app()->runningInConsole()) {
            abort_unless(config('features.commerce_layer'), 404);
        }
    }

    public function index()
    {
        $companyId   = (int) (settings()->id ?? 0);
        $connections = $this->repo->listForCompany($companyId);
        $providers   = CommerceProvider::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('Admin/Commerce/Connections/Index', [
            'connections' => $connections->map(fn (CommerceConnection $c) => $this->serialize($c))->values(),
            'providers'   => $providers->map(fn ($p) => [
                'code'     => $p->code,
                'name'     => $p->name,
                'logo_url' => $this->logoUrlWithVersion($p->logo_url),
                'supports' => $p->supports ?: [],
            ])->values(),
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'create'       => route('commerce.connections.create'),
                'index'        => route('commerce.connections.index'),
                'integrations' => route('integrations.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function create(Request $request)
    {
        $providerCode = (string) $request->query('provider', '');
        $provider     = CommerceProvider::where('code', $providerCode)->where('status', 'active')->firstOrFail();

        return Inertia::render('Admin/Commerce/Connections/Edit', [
            'mode'     => 'create',
            'provider' => $this->serializeProvider($provider),
            'connection' => null,
            'merchants'  => $this->merchantOptions(),
            'urls' => [
                'submit' => route('commerce.connections.store', ['provider' => $provider->code]),
                'test'   => route('commerce.connections.test'),
                'index'  => route('commerce.connections.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function store(Request $request, string $provider)
    {
        $data = $this->validateForm($request);

        try {
            $conn = $this->service->store(
                companyId:    (int) (settings()->id ?? 0),
                providerCode: $provider,
                input:        $data,
            );
        } catch (ConnectionTestFailedException $e) {
            return back()
                ->withInput()
                ->withErrors(['connection' => $e->getMessage()]);
        }

        Toastr::success('Connection added.', 'Success');
        return redirect()->route('commerce.connections.edit', $conn->id);
    }

    public function edit(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $conn      = $this->repo->findForCompany($id, $companyId);
        abort_if(! $conn, 404);

        return Inertia::render('Admin/Commerce/Connections/Edit', [
            'mode'       => 'edit',
            'provider'   => $this->serializeProvider($conn->provider),
            'connection' => $this->serialize($conn, includeSecretMasks: true),
            'merchants'  => $this->merchantOptions(),
            'urls' => [
                'submit'      => route('commerce.connections.update', $conn->id),
                'test'        => route('commerce.connections.test'),
                'set_default' => route('commerce.connections.set_default', $conn->id),
                'destroy'     => route('commerce.connections.destroy', $conn->id),
                'index'       => route('commerce.connections.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $conn      = $this->repo->findForCompany($id, $companyId);
        abort_if(! $conn, 404);

        $data = $this->validateForm($request, edit: true);
        $this->service->update($conn, $data);

        Toastr::success('Connection updated.', 'Success');
        return back();
    }

    public function destroy(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $conn      = $this->repo->findForCompany($id, $companyId);
        abort_if(! $conn, 404);

        $conn->delete();
        Toastr::success('Connection removed.', 'Success');
        return redirect()->route('commerce.connections.index');
    }

    public function setDefault(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $conn      = $this->repo->findForCompany($id, $companyId);
        abort_if(! $conn, 404);

        $this->service->setDefault($conn);
        Toastr::success('Default connection updated.', 'Success');
        return back();
    }

    /**
     * AJAX endpoint — test a candidate connection without persisting.
     *
     * On edit, secret fields posted as the `__keep__` sentinel or empty
     * are hydrated from the stored row so the test exercises the real
     * stored credentials. Strictly scoped to the tenant's own rows.
     */
    public function test(Request $request)
    {
        $providerCode = (string) $request->input('provider');
        if (! $providerCode || ! in_array($providerCode, $this->factory->codes(), true)) {
            return response()->json(['ok' => false, 'message' => 'unknown provider'], 422);
        }
        $companyId    = (int) (settings()->id ?? 0);
        $connectionId = (int) $request->input('connection_id', 0);

        $input = $request->only([
            'connection_name', 'domain', 'remote_store_id', 'merchant_id',
            'access_token', 'refresh_token', 'token_expires_at',
            'api_key', 'api_secret', 'webhook_secret', 'settings',
        ]);

        if ($connectionId > 0) {
            $existing = $this->repo->findForCompany($connectionId, $companyId);
            if ($existing) {
                foreach (['access_token', 'refresh_token', 'api_key', 'api_secret', 'webhook_secret'] as $field) {
                    $v = $input[$field] ?? null;
                    if ($v === null || $v === '' || $v === '__keep__' || str_starts_with((string) $v, '••')) {
                        $modelCol = $field . '_encrypted';
                        $input[$field] = $existing->{$modelCol};
                    }
                }
                foreach (['domain', 'remote_store_id'] as $field) {
                    if (empty($input[$field])) $input[$field] = $existing->{$field};
                }
                if (empty($input['merchant_id'])) {
                    $input['merchant_id'] = $existing->merchant_id;
                }
            }
        }

        $candidate = $this->service->candidateFromInput($companyId, $providerCode, $input);
        $result    = $this->service->test($candidate);

        return response()->json([
            'ok'          => $result->ok,
            'message'     => $result->message,
            'diagnostics' => $result->diagnostics,
        ]);
    }

    // -------------------------------------------------------------------

    private function validateForm(Request $request, bool $edit = false): array
    {
        return $request->validate([
            'connection_name'   => ['required', 'string', 'max:100'],
            'domain'            => ['nullable', 'string', 'max:255'],
            'remote_store_id'   => ['nullable', 'string', 'max:191'],
            'merchant_id'       => ['nullable', 'integer', 'exists:merchants,id'],
            'access_token'      => ['nullable', 'string', 'max:4096'],
            'refresh_token'     => ['nullable', 'string', 'max:4096'],
            'token_expires_at'  => ['nullable', 'date'],
            'api_key'           => ['nullable', 'string', 'max:512'],
            'api_secret'        => ['nullable', 'string', 'max:512'],
            'webhook_secret'    => ['nullable', 'string', 'max:512'],
            'settings'          => ['nullable', 'array'],
            'status'            => ['nullable', 'in:active,paused,invalid,reauth_required'],
        ]);
    }

    private function logoUrlWithVersion(?string $url): ?string
    {
        if (! $url) return null;
        if (! str_starts_with($url, '/')) return $url;
        $path = public_path(ltrim($url, '/'));
        if (! file_exists($path)) return $url;
        return $url . '?v=' . filemtime($path);
    }

    private function merchantOptions(): array
    {
        $companyId = (int) (settings()->id ?? 0);
        return Merchant::query()
            ->where('company_id', $companyId)
            ->orderBy('business_name')
            ->limit(500)
            ->get(['id', 'business_name'])
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->business_name])
            ->values()
            ->toArray();
    }

    private function serializeProvider(CommerceProvider $p): array
    {
        return [
            'code'     => $p->code,
            'name'     => $p->name,
            'logo_url' => $this->logoUrlWithVersion($p->logo_url),
            'supports' => $p->supports ?: [],
        ];
    }

    private function serialize(CommerceConnection $c, bool $includeSecretMasks = false): array
    {
        $mask = fn (?string $v) => $includeSecretMasks && $v ? '••••••' : '';

        return [
            'id'                    => $c->id,
            'provider'              => ['code' => $c->provider->code, 'name' => $c->provider->name, 'supports' => $c->provider->supports ?: []],
            'connection_name'       => $c->connection_name,
            'domain'                => $c->domain,
            'remote_store_id'       => $c->remote_store_id,
            'merchant_id'           => $c->merchant_id,
            'access_token_masked'   => $mask($c->access_token_encrypted),
            'refresh_token_masked'  => $mask($c->refresh_token_encrypted),
            'token_expires_at'      => optional($c->token_expires_at)->toIso8601String(),
            'api_key_masked'        => $mask($c->api_key_encrypted),
            'api_secret_masked'     => $mask($c->api_secret_encrypted),
            'webhook_secret_masked' => $mask($c->webhook_secret_encrypted),
            'settings'              => $c->settings ?: [],
            'status'                => $c->status,
            'is_default'            => $c->is_default,
            'last_tested_at'        => optional($c->last_tested_at)->toIso8601String(),
            'last_sync_at'          => optional($c->last_sync_at)->toIso8601String(),
            'last_event_at'         => optional($c->last_event_at)->toIso8601String(),
        ];
    }

    private function strings(): array
    {
        return [
            'page_title'              => 'Commerce connections',
            'breadcrumb_settings'     => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations' => 'Integrations',
            'breadcrumb_commerce'     => 'Commerce',
            'help'                    => 'Tenant-scoped commerce connections. Each connection feeds orders from one storefront install (Salla merchant, Shopify shop, etc.) into one Rushly merchant.',
            'add_integration'         => 'Add integration',
            'no_connections'          => 'No connections yet — add one to start receiving orders from your storefronts.',
            'pick_provider'           => 'Pick a provider',
            'connection_name'         => 'Connection name',
            'connection_name_hint'    => 'A label to tell connections apart (e.g. "KSA store", "Backup storefront").',
            'remote_store_id'         => 'Remote store id',
            'remote_store_id_hint'    => 'The storefront identifier on the provider side (Salla merchant id, Shopify shop domain, …).',
            'domain'                  => 'Storefront domain',
            'domain_hint'             => 'Optional. Your storefront URL — display only.',
            'merchant_id'             => 'Rushly merchant',
            'merchant_id_hint'        => 'Which Rushly merchant this storefront feeds parcels into.',
            'access_token'            => 'Access token',
            'access_token_edit_hint'  => 'Leave blank to keep the stored token.',
            'refresh_token'           => 'Refresh token',
            'token_expires_at'        => 'Token expires at',
            'api_key'                 => 'API key',
            'api_secret'              => 'API secret',
            'webhook_secret'          => 'Webhook secret',
            'webhook_secret_hint'     => 'HMAC secret the storefront uses to sign inbound webhook deliveries.',
            'status'                  => 'Status',
            'is_default'              => 'Default for this provider',
            'set_default'             => 'Make default',
            'last_tested'             => 'Last tested',
            'last_synced'             => 'Last synced',
            'last_event'              => 'Last event',
            'test_connection'         => 'Test connection',
            'save'                    => __('levels.save') ?: 'Save',
            'cancel'                  => __('levels.cancel') ?: 'Cancel',
            'delete'                  => __('levels.delete') ?: 'Delete',
            'add'                     => 'Add',
            'back'                    => __('levels.back') ?: 'Back',
            'oauth_install_note'      => 'OAuth install flow ships in Phase 3. For now, paste tokens manually (mint via the legacy Salla install flow if needed).',
        ];
    }
}
