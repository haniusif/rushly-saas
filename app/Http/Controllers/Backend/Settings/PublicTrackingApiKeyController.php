<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Models\PublicTrackingApiKey;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Admin surface for issuing per-tenant public tracking API keys.
 *
 * Plaintext keys are shown to the operator exactly once — right after
 * store() or regenerate() via a flash session value. The DB only stores
 * the sha256 hash + a short prefix for UI recognition.
 *
 * Reads are gated by `integrations_read` (route middleware); mutations
 * by `integrations_update`.
 */
class PublicTrackingApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $companyId = optional(settings())->id;

        $keys = PublicTrackingApiKey::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('id')
            ->get()
            ->map(fn (PublicTrackingApiKey $k) => $this->serialize($k))
            ->values();

        $endpoint = url('/api/public/tracking/{tracking_id}');
        // Sample tracking id for the docs block — first parcel for the
        // tenant, or a placeholder if the tenant has no parcels yet.
        $sampleTracking = \DB::table('parcels')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('tracking_id') ?: 'RXP-EXAMPLE-12345';

        // Template URLs the client fills in per row. We can't use nested
        // closures for parameterized routes here — Inertia tries to
        // resolve them via App::call() and blows up on the `$id` param.
        $updateTpl     = route('settings.public-tracking-api-keys.update', ['id' => '__ID__']);
        $regenerateTpl = route('settings.public-tracking-api-keys.regenerate', ['id' => '__ID__']);
        $toggleTpl     = route('settings.public-tracking-api-keys.toggle', ['id' => '__ID__']);
        $destroyTpl    = route('settings.public-tracking-api-keys.destroy', ['id' => '__ID__']);

        return Inertia::render('Admin/Settings/PublicTrackingApiKeys/Index', [
            'keys'                 => $keys,
            'endpoint'             => $endpoint,
            'sampleTracking'       => $sampleTracking,
            'flash_key'            => $request->session()->get('new_api_key_plaintext'),
            'responseFieldOptions' => PublicTrackingApiKey::RESPONSE_FIELD_OPTIONS,
            'alwaysOnFields'       => PublicTrackingApiKey::ALWAYS_ON_RESPONSE_FIELDS,
            'urls'                 => [
                'store'          => route('settings.public-tracking-api-keys.store'),
                'update_tpl'     => $updateTpl,
                'regenerate_tpl' => $regenerateTpl,
                'toggle_tpl'     => $toggleTpl,
                'destroy_tpl'    => $destroyTpl,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateForm($request);

        [$plaintext, $hash, $prefix] = PublicTrackingApiKey::generate();

        PublicTrackingApiKey::create([
            'company_id'      => optional(settings())->id,
            'name'            => $data['name'],
            'key_hash'        => $hash,
            'key_prefix'      => $prefix,
            'allowed_origins' => $this->normalizeOrigins($data['allowed_origins'] ?? null),
            'response_fields' => $this->normalizeResponseFields($data['response_fields'] ?? null),
            'is_active'       => true,
            'created_by'      => Auth::id(),
        ]);

        // Flash plaintext once so the UI can render a "copy this now"
        // panel. Never leaves the session.
        session()->flash('new_api_key_plaintext', $plaintext);

        Toastr::success('API key created. Copy it now — it will not be shown again.', 'Success');
        return back();
    }

    /**
     * Edit an existing key's metadata (name / origins / response fields)
     * without rotating its secret. Rotation is a separate endpoint on
     * purpose — changing what a key returns should not force integrations
     * to switch to a new secret.
     */
    public function update(Request $request, int $id)
    {
        $row  = $this->find($id);
        $data = $this->validateForm($request);
        $row->update([
            'name'            => $data['name'],
            'allowed_origins' => $this->normalizeOrigins($data['allowed_origins'] ?? null),
            'response_fields' => $this->normalizeResponseFields($data['response_fields'] ?? null),
        ]);
        Toastr::success('Key settings updated.', 'Success');
        return back();
    }

    public function regenerate(int $id)
    {
        $row = $this->find($id);
        [$plaintext, $hash, $prefix] = PublicTrackingApiKey::generate();
        $row->update([
            'key_hash'   => $hash,
            'key_prefix' => $prefix,
        ]);
        session()->flash('new_api_key_plaintext', $plaintext);
        Toastr::success('Key rotated. The previous key is now invalid.', 'Success');
        return back();
    }

    public function toggle(int $id)
    {
        $row = $this->find($id);
        $row->update(['is_active' => ! $row->is_active]);
        Toastr::success($row->is_active ? 'Key re-enabled.' : 'Key disabled.', 'Success');
        return back();
    }

    public function destroy(int $id)
    {
        $row = $this->find($id);
        $row->delete();
        Toastr::success('Key deleted.', 'Success');
        return back();
    }

    // -----------------------------------------------------------------

    private function find(int $id): PublicTrackingApiKey
    {
        $companyId = optional(settings())->id;
        $row = PublicTrackingApiKey::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('id', $id)
            ->first();
        abort_if(! $row, 404);
        return $row;
    }

    private function validateForm(Request $request): array
    {
        return $request->validate([
            'name'              => ['required', 'string', 'max:191'],
            'allowed_origins'   => ['nullable', 'string', 'max:2000'],
            'response_fields'   => ['nullable', 'array'],
            'response_fields.*' => ['string', 'in:' . implode(',', PublicTrackingApiKey::RESPONSE_FIELD_OPTIONS)],
        ]);
    }

    private function normalizeOrigins(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') return null;
        $items = collect(preg_split('/[\s,]+/', $raw))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();
        return empty($items) ? null : $items;
    }

    private function normalizeResponseFields(?array $raw): ?array
    {
        if ($raw === null) return null;
        $filtered = array_values(array_intersect($raw, PublicTrackingApiKey::RESPONSE_FIELD_OPTIONS));
        // Empty array = "expose nothing beyond the always-on fields" —
        // legitimate config (some merchants may only want status).
        return $filtered;
    }

    private function serialize(PublicTrackingApiKey $k): array
    {
        return [
            'id'              => $k->id,
            'name'            => $k->name,
            'key_prefix'      => $k->key_prefix,
            'allowed_origins' => $k->allowed_origins ?: [],
            'response_fields' => $k->response_fields, // null = all, [] = only always-on
            'is_active'       => (bool) $k->is_active,
            'last_used_at'    => optional($k->last_used_at)->toIso8601String(),
            'request_count'   => (int) $k->request_count,
            'created_at'      => optional($k->created_at)->toIso8601String(),
        ];
    }
}
