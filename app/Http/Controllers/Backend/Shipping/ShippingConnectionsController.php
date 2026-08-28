<?php

namespace App\Http\Controllers\Backend\Shipping;

use App\Http\Controllers\Controller;
use App\Shipping\DTOs\ConnectionDTO;
use App\Shipping\Exceptions\ConnectionTestFailedException;
use App\Shipping\Factory\ShippingProviderFactory;
use App\Shipping\Models\ShippingConnection;
use App\Shipping\Models\ShippingProvider;
use App\Shipping\Repositories\ShippingConnectionRepository;
use App\Shipping\Services\ConnectionService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * /admin/shipping/connections — generic CRUD for shipping connections.
 *
 * Lives under the existing tenant `admin/` route group so the middleware
 * stack (auth + IsInstalled + subscription + permission) applies unchanged.
 * Permissions reuse `integrations_read` / `integrations_update` — same model
 * used by the other integration pages.
 */
class ShippingConnectionsController extends Controller
{
    public function __construct(
        private readonly ConnectionService $service,
        private readonly ShippingConnectionRepository $repo,
        private readonly ShippingProviderFactory $factory,
    ) {}

    public function index()
    {
        $companyId   = (int) (settings()->id ?? 0);
        $connections = $this->repo->listForCompany($companyId);
        $providers   = ShippingProvider::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('Admin/Shipping/Connections/Index', [
            'connections' => $connections->map(fn (ShippingConnection $c) => $this->serialize($c))->values(),
            'providers'   => $providers->map(fn ($p) => [
                'code'     => $p->code,
                'name'     => $p->name,
                'logo_url' => $this->logoUrlWithVersion($p->logo_url),
            ])->values(),
            'permissions' => [
                'update' => hasPermission('integrations_update'),
            ],
            'urls' => [
                'create'        => route('shipping.connections.create'),
                'index'         => route('shipping.connections.index'),
                'integrations'  => route('integrations.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function create(Request $request)
    {
        $providerCode = (string) $request->query('provider', 'logestechs');
        $provider     = ShippingProvider::where('code', $providerCode)->firstOrFail();

        return Inertia::render('Admin/Shipping/Connections/Edit', [
            'mode'     => 'create',
            'provider' => [
                'code'     => $provider->code,
                'name'     => $provider->name,
                'logo_url' => $this->logoUrlWithVersion($provider->logo_url),
            ],
            'connection' => null,
            'fields'     => self::formSpec($provider->code),
            'urls' => [
                'submit'           => route('shipping.connections.store', ['provider' => $provider->code]),
                'test'             => route('shipping.connections.test'),
                'resolve_domain'   => route('shipping.connections.resolve_domain', ['provider' => $provider->code]),
                'index'            => route('shipping.connections.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function store(Request $request, string $provider)
    {
        $data = $this->validateForm($request, false, $provider);

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
        return redirect()->route('shipping.connections.edit', $conn->id);
    }

    public function edit(int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $conn      = $this->repo->findForCompany($id, $companyId);
        abort_if(! $conn, 404);

        return Inertia::render('Admin/Shipping/Connections/Edit', [
            'mode'       => 'edit',
            'provider'   => ['code' => $conn->provider->code, 'name' => $conn->provider->name, 'logo_url' => $this->logoUrlWithVersion($conn->provider->logo_url)],
            'connection' => $this->serialize($conn, includePasswordMask: true),
            'fields'     => self::formSpec($conn->provider->code),
            'urls' => [
                'submit'         => route('shipping.connections.update', $conn->id),
                'test'           => route('shipping.connections.test'),
                'resolve_domain' => route('shipping.connections.resolve_domain', ['provider' => $conn->provider->code]),
                'set_default'    => route('shipping.connections.set_default', $conn->id),
                'destroy'        => route('shipping.connections.destroy', $conn->id),
                'index'          => route('shipping.connections.index'),
            ],
            't' => $this->strings(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $companyId = (int) (settings()->id ?? 0);
        $conn      = $this->repo->findForCompany($id, $companyId);
        abort_if(! $conn, 404);

        $data = $this->validateForm($request, edit: true, providerCode: $conn->provider->code);
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
        return redirect()->route('shipping.connections.index');
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

    /** AJAX endpoint — paste a domain, get back the remote company id. */
    public function resolveDomain(Request $request, string $provider)
    {
        $domain = trim((string) $request->input('domain'));
        if ($domain === '') {
            return response()->json(['ok' => false, 'message' => 'domain is required'], 422);
        }
        try {
            $id = $this->service->resolveCompanyByDomain($provider, $domain);
            return response()->json(['ok' => $id !== null, 'remote_company_id' => $id]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * AJAX endpoint — test a candidate connection without persisting.
     *
     * When called from the edit page, `connection_id` is supplied and the
     * password input is blank or the `__keep__` sentinel (because we never
     * round-trip the plaintext to the form). In that case, hydrate the
     * password — and any other unprovided field — from the stored row so the
     * test exercises real credentials.
     */
    public function test(Request $request)
    {
        $providerCode = (string) $request->input('provider');
        if (! $providerCode || ! in_array($providerCode, $this->factory->codes(), true)) {
            return response()->json(['ok' => false, 'message' => 'unknown provider'], 422);
        }
        $companyId = (int) (settings()->id ?? 0);

        $email           = trim((string) $request->input('email', '')) ?: null;
        $password        = (string) $request->input('password', '');
        $remoteCompanyId = trim((string) $request->input('remote_company_id', '')) ?: null;
        $domain          = trim((string) $request->input('domain', '')) ?: null;
        $connectionId    = (int) $request->input('connection_id', 0);

        // Edit-context hydration: when the caller left the password blank or
        // sent the sentinel, pull the saved password (and any other unfilled
        // fields) off the matching connection. Strictly scoped to the
        // tenant's own rows.
        $settings = (array) $request->input('settings', []);

        if ($connectionId > 0) {
            $existing = $this->repo->findForCompany($connectionId, $companyId);
            if ($existing) {
                if ($password === '' || $password === '__keep__') {
                    $password = (string) $existing->password_encrypted;
                }
                $email           = $email           ?: $existing->email;
                $remoteCompanyId = $remoteCompanyId ?: $existing->remote_company_id;
                $domain          = $domain          ?: $existing->domain;

                // Settings need the same hydration, and for the same reason.
                // A secret is never sent to the browser — the edit form shows
                // the '••••••' mask — so testing a saved connection posts the
                // mask back. Without this, Test failed with invalid_client on
                // a connection whose stored credentials were perfectly good,
                // which reads as "your credentials are broken" when nothing is.
                // Blank and masked values fall back to what is stored; a
                // retyped value still wins so a NEW secret can be tested
                // before it is saved.
                $stored = is_array($existing->settings) ? $existing->settings : [];
                foreach ($stored as $key => $value) {
                    $posted = $settings[$key] ?? '';
                    if ($posted === '' || $posted === null
                        || (is_string($posted) && str_starts_with($posted, '••'))) {
                        $settings[$key] = $value;
                    }
                }
            }
        }

        $candidate = new ConnectionDTO(
            id:              null,
            companyId:       $companyId,
            providerCode:    $providerCode,
            connectionName:  (string) $request->input('connection_name', 'Default'),
            remoteCompanyId: $remoteCompanyId,
            domain:          $domain,
            email:           $email,
            password:        $password !== '' ? $password : null,
            settings:        $settings,
        );

        $result = $this->service->test($candidate);

        return response()->json([
            'ok'          => $result->ok,
            'message'     => $result->message,
            'diagnostics' => $result->diagnostics,
        ]);
    }

    // -------------------------------------------------------------------

    /**
     * The field spec for a provider, from config/shipping.php.
     *
     * Falls back to the Logestechs-shaped set so a provider added without a
     * spec still gets a usable form rather than an empty one.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function formSpec(string $providerCode): array
    {
        $spec = config('shipping.providers.' . $providerCode . '.form');

        if (is_array($spec) && $spec !== []) {
            return $spec;
        }

        return [
            ['name' => 'domain',            'label' => 'Domain',     'type' => 'text'],
            ['name' => 'remote_company_id', 'label' => 'Company ID', 'type' => 'text', 'mono' => true],
            ['name' => 'email',             'label' => 'Email',      'type' => 'email',    'required' => true],
            ['name' => 'password',          'label' => 'Password',   'type' => 'password', 'required' => true, 'secret' => true],
        ];
    }

    /**
     * Build validation rules from the provider's spec rather than hardcoding
     * one provider's credential shape for all of them.
     *
     * Two rules that are easy to get wrong and matter:
     *
     *  - A `secret` field is never required on EDIT. The browser is never sent
     *    the stored value, so demanding it back would force the operator to
     *    retype a client secret to change an unrelated field.
     *  - `settings.*` fields are validated by their dotted path, which is what
     *    Laravel needs to reach into the nested array.
     */
    private function validateForm(Request $request, bool $edit = false, ?string $providerCode = null): array
    {
        $rules = [
            'connection_name' => ['required', 'string', 'max:100'],
            'settings'        => ['nullable', 'array'],
            'status'          => ['nullable', 'in:active,paused'],
        ];

        foreach (self::formSpec($providerCode ?? 'logestechs') as $field) {
            $name     = (string) ($field['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $required = (bool) ($field['required'] ?? false);
            $secret   = (bool) ($field['secret'] ?? false);

            $rule = [];
            $rule[] = ($required && ! ($edit && $secret)) ? 'required' : 'nullable';
            $rule[] = ($field['type'] ?? 'text') === 'email' ? 'email' : 'string';
            $rule[] = 'max:' . (int) ($field['max'] ?? 255);

            $rules[$name] = $rule;
        }

        return $request->validate($rules);
    }

    /**
     * Append the file's mtime as a query parameter so Cloudflare / browsers
     * pick up a replaced logo immediately instead of serving the cached bytes
     * for the full max-age window. Returns the URL unchanged if the path
     * doesn't resolve to a local file.
     */
    private function logoUrlWithVersion(?string $url): ?string
    {
        if (! $url) return null;
        // Only version local /images/... paths — leave fully-qualified URLs alone.
        if (! str_starts_with($url, '/')) return $url;
        $path = public_path(ltrim($url, '/'));
        if (! file_exists($path)) return $url;
        return $url . '?v=' . filemtime($path);
    }

    private function serialize(ShippingConnection $c, bool $includePasswordMask = false): array
    {
        return [
            'id'                => $c->id,
            'provider'          => ['code' => $c->provider->code, 'name' => $c->provider->name],
            'connection_name'   => $c->connection_name,
            'domain'            => $c->domain,
            'remote_company_id' => $c->remote_company_id,
            'email'             => $c->email,
            'password_masked'   => $includePasswordMask && $c->password_encrypted ? '••••••' : '',
            // Settings are redacted, not passed through. They carry provider
            // credentials — an OAuth client secret, an API key — and this array
            // is serialised into the Inertia page props, i.e. straight into the
            // HTML the browser receives. Anything the spec marks `secret` is
            // replaced with a has-a-value flag so the form can show "already
            // set" without ever shipping the value.
            'settings'          => $this->redactSettings($c),
            'status'            => $c->status,
            'is_default'        => $c->is_default,
            'is_ready'          => $c->isReady(),
            'last_tested_at'    => optional($c->last_tested_at)->toIso8601String(),
            'last_sync_at'      => optional($c->last_sync_at)->toIso8601String(),
        ];
    }

    /**
     * Settings with every `secret` field stripped.
     *
     * A secret that has a stored value becomes the '••••••' mask, so the form
     * can render "already set, leave blank to keep" without the plaintext ever
     * reaching the browser. Non-secret settings pass through so they can
     * prefill normally.
     *
     * @return array<string, mixed>
     */
    private function redactSettings(ShippingConnection $c): array
    {
        $settings = is_array($c->settings) ? $c->settings : [];

        foreach (self::formSpec($c->provider->code) as $field) {
            if (! ($field['secret'] ?? false)) {
                continue;
            }
            $name = (string) ($field['name'] ?? '');
            if (! str_starts_with($name, 'settings.')) {
                continue;
            }
            $key = substr($name, strlen('settings.'));
            if (($settings[$key] ?? '') !== '') {
                $settings[$key] = '••••••';
            }
        }

        return $settings;
    }

    private function strings(): array
    {
        return [
            'page_title'             => 'Shipping connections',
            'breadcrumb_settings'    => __('menus.settings') ?: 'Settings',
            'breadcrumb_integrations'=> 'Integrations',
            'breadcrumb_shipping'    => 'Shipping',
            'help'                   => 'Tenant-scoped shipping connections. Each connection talks to one provider account; bulk- and single-assign flows pick the default for each provider.',
            'add_integration'        => 'Add integration',
            'no_connections'         => 'No connections yet — add one to start handing parcels off to a courier.',
            'pick_provider'          => 'Pick a provider',
            'connection_name'        => 'Connection name',
            'connection_name_hint'   => 'A label to tell connections apart (e.g. "Riyadh main", "Backup acct").',
            'domain'                 => 'Domain',
            'domain_hint'            => 'Paste your provider subdomain (e.g. salesksa.logestechs.com). We resolve the company id automatically.',
            'remote_company_id'      => 'Provider company id',
            'remote_company_id_hint' => 'The id the provider uses for your account. Auto-filled from domain when possible.',
            'email'                  => 'Account email',
            'password'               => 'Account password',
            'password_edit_hint'     => 'Leave blank to keep the existing password.',
            'status'                 => 'Status',
            'is_default'             => 'Default for this provider',
            'set_default'            => 'Make default',
            'last_tested'            => 'Last tested',
            'last_synced'            => 'Last synced',
            'test_connection'        => 'Test connection',
            'resolve_domain'         => 'Resolve from domain',
            'save'                   => __('levels.save') ?: 'Save',
            'cancel'                 => __('levels.cancel') ?: 'Cancel',
            'delete'                 => __('levels.delete') ?: 'Delete',
            'add'                    => 'Add',
            'back'                   => __('levels.back') ?: 'Back',
            'ready'                  => 'Ready',
            'needs_config'           => 'Needs config',
            'connected'              => 'Connected',
        ];
    }
}
