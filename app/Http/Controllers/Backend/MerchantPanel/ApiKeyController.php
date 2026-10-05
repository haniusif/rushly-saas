<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Services\MerchantApi\MerchantApiKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Self-service Rushly Merchant API keys for the logged-in merchant.
 *
 * Keys live in the rushly-api façade, not here — every action goes through
 * MerchantApiKeyService over HTTP. merchant_id / company_id are resolved from
 * the authenticated merchant and NEVER taken from the request.
 */
class ApiKeyController extends Controller
{
    protected MerchantApiKeyService $service;

    public function __construct(MerchantApiKeyService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        [$merchantId, $companyId] = $this->identity();

        $error = null;
        $keys  = [];
        try {
            $keys = $this->service->list($merchantId, $companyId);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return Inertia::render('Merchant/ApiKeys/Index', [
            'keys'                  => $keys,
            'availableEnvironments' => $this->service->availableEnvironments(),
            'scopes'                => MerchantApiKeyService::VALID_SCOPES,
            'docsUrl'               => $this->docsUrl(),
            'loadError'             => $error,
            // Set once, right after a successful create, via redirect flash.
            'createdToken'          => $request->session()->get('created_token'),
            'createdClient'         => $request->session()->get('created_client'),
        ]);
    }

    public function store(Request $request)
    {
        [$merchantId, $companyId] = $this->identity();

        $available = $this->service->availableEnvironments();

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'environment' => ['required', Rule::in($available)],
            'scopes'      => ['required', 'array', 'min:1'],
            'scopes.*'    => [Rule::in(MerchantApiKeyService::VALID_SCOPES)],
            'expires_at'  => ['nullable', 'date'],
        ]);

        try {
            $result = $this->service->create(
                $merchantId,
                $companyId,
                $validated['name'],
                $validated['environment'],
                $validated['scopes'],
                $validated['expires_at'] ?? null,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('merchant-panel.api-keys.index')
                ->with('error', $e->getMessage());
        }

        // The token is returned ONCE by the façade — flash it so the index
        // page can reveal it a single time, then it is gone.
        return redirect()
            ->route('merchant-panel.api-keys.index')
            ->with('success', __('message.success'))
            ->with('created_token', $result['token'])
            ->with('created_client', $result['client']);
    }

    public function revoke(Request $request, string $uuid)
    {
        [$merchantId, $companyId] = $this->identity();

        $validated = $request->validate([
            'environment' => ['required', Rule::in($this->service->availableEnvironments())],
        ]);

        try {
            $this->service->revoke($merchantId, $companyId, $validated['environment'], $uuid);
        } catch (\Throwable $e) {
            return redirect()
                ->route('merchant-panel.api-keys.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('merchant-panel.api-keys.index')
            ->with('success', __('message.success'));
    }

    /**
     * The authenticated merchant's [merchant_id, company_id]. Mirrors the
     * other MerchantPanel controllers (Auth::user()->merchant for the merchant
     * row, Auth::user()->company_id for the tenant).
     *
     * @return array{0:int,1:int}
     */
    private function identity(): array
    {
        $user = Auth::user();

        return [(int) $user->merchant->id, (int) $user->company_id];
    }

    private function docsUrl(): ?string
    {
        if ($explicit = config('merchant_api.docs_url')) {
            return $explicit;
        }

        $base = config('merchant_api.live.url') ?: config('merchant_api.test.url');

        return $base ? rtrim($base, '/').'/docs' : null;
    }
}
