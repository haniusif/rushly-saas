<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use App\Services\MerchantApi\MerchantWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Self-service webhook endpoints for the logged-in merchant.
 *
 * Endpoints live in the rushly-api façade, not here — every action goes
 * through MerchantWebhookService over HTTP. merchant_id / company_id are
 * resolved from the authenticated merchant and NEVER taken from the request.
 */
class WebhookController extends Controller
{
    protected MerchantWebhookService $service;

    public function __construct(MerchantWebhookService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        [$merchantId, $companyId] = $this->identity();

        $error     = null;
        $endpoints = [];
        try {
            $endpoints = $this->service->list($merchantId, $companyId);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return Inertia::render('Merchant/Webhooks/Index', [
            'endpoints'             => $endpoints,
            'availableEnvironments' => $this->service->availableEnvironments(),
            'events'                => MerchantWebhookService::VALID_EVENTS,
            'docsUrl'               => $this->docsUrl(),
            'loadError'             => $error,
            // Set once, right after a successful create / rotate, via redirect flash.
            'createdSecret'         => $request->session()->get('created_secret'),
            'createdEndpoint'       => $request->session()->get('created_endpoint'),
        ]);
    }

    public function store(Request $request)
    {
        [$merchantId, $companyId] = $this->identity();

        $available = $this->service->availableEnvironments();

        $validated = $request->validate([
            'url'         => ['required', 'url', 'max:2048'],
            'events'      => ['required', 'array', 'min:1'],
            'events.*'    => [Rule::in(MerchantWebhookService::VALID_EVENTS)],
            'environment' => ['required', Rule::in($available)],
            'description' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $result = $this->service->create(
                $merchantId,
                $companyId,
                $validated['environment'],
                $validated['url'],
                $validated['events'],
                $validated['description'] ?? null,
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('merchant-panel.webhooks.index')
                ->with('error', $e->getMessage());
        }

        // The secret is returned ONCE by the façade — flash it so the index
        // page can reveal it a single time, then it is gone.
        return redirect()
            ->route('merchant-panel.webhooks.index')
            ->with('success', __('message.success'))
            ->with('created_secret', $result['secret'])
            ->with('created_endpoint', $result['endpoint']);
    }

    public function update(Request $request, string $uuid)
    {
        [$merchantId, $companyId] = $this->identity();

        $validated = $request->validate([
            'environment' => ['required', Rule::in($this->service->availableEnvironments())],
            'url'         => ['sometimes', 'required', 'url', 'max:2048'],
            'events'      => ['sometimes', 'required', 'array', 'min:1'],
            'events.*'    => [Rule::in(MerchantWebhookService::VALID_EVENTS)],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $fields = collect($validated)
            ->only(['url', 'events', 'is_active'])
            ->all();

        try {
            $this->service->update($merchantId, $companyId, $validated['environment'], $uuid, $fields);
        } catch (\Throwable $e) {
            return redirect()
                ->route('merchant-panel.webhooks.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('merchant-panel.webhooks.index')
            ->with('success', __('message.success'));
    }

    public function rotateSecret(Request $request, string $uuid)
    {
        [$merchantId, $companyId] = $this->identity();

        $validated = $request->validate([
            'environment' => ['required', Rule::in($this->service->availableEnvironments())],
        ]);

        try {
            $result = $this->service->rotateSecret($merchantId, $companyId, $validated['environment'], $uuid);
        } catch (\Throwable $e) {
            return redirect()
                ->route('merchant-panel.webhooks.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('merchant-panel.webhooks.index')
            ->with('success', __('message.success'))
            ->with('created_secret', $result['secret'])
            ->with('created_endpoint', $result['endpoint']);
    }

    public function destroy(Request $request, string $uuid)
    {
        [$merchantId, $companyId] = $this->identity();

        $validated = $request->validate([
            'environment' => ['required', Rule::in($this->service->availableEnvironments())],
        ]);

        try {
            $this->service->delete($merchantId, $companyId, $validated['environment'], $uuid);
        } catch (\Throwable $e) {
            return redirect()
                ->route('merchant-panel.webhooks.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('merchant-panel.webhooks.index')
            ->with('success', __('message.success'));
    }

    public function deliveries(Request $request, string $uuid)
    {
        [$merchantId, $companyId] = $this->identity();

        $validated = $request->validate([
            'environment' => ['required', Rule::in($this->service->availableEnvironments())],
        ]);

        try {
            $deliveries = $this->service->deliveries($merchantId, $companyId, $validated['environment'], $uuid);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $deliveries]);
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
