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
        $regenerateTpl = route('settings.public-tracking-api-keys.regenerate', ['id' => '__ID__']);
        $toggleTpl     = route('settings.public-tracking-api-keys.toggle', ['id' => '__ID__']);
        $destroyTpl    = route('settings.public-tracking-api-keys.destroy', ['id' => '__ID__']);

        return Inertia::render('Admin/Settings/PublicTrackingApiKeys/Index', [
            'keys'           => $keys,
            'endpoint'       => $endpoint,
            'sampleTracking' => $sampleTracking,
            'flash_key'      => $request->session()->get('new_api_key_plaintext'),
            'urls'           => [
                'store'          => route('settings.public-tracking-api-keys.store'),
                'regenerate_tpl' => $regenerateTpl,
                'toggle_tpl'     => $toggleTpl,
                'destroy_tpl'    => $destroyTpl,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:191'],
            'allowed_origins' => ['nullable', 'string', 'max:2000'],
        ]);

        [$plaintext, $hash, $prefix] = PublicTrackingApiKey::generate();

        $origins = $this->normalizeOrigins($data['allowed_origins'] ?? null);

        PublicTrackingApiKey::create([
            'company_id'      => optional(settings())->id,
            'name'            => $data['name'],
            'key_hash'        => $hash,
            'key_prefix'      => $prefix,
            'allowed_origins' => $origins,
            'is_active'       => true,
            'created_by'      => Auth::id(),
        ]);

        // Flash plaintext once so the UI can render a "copy this now"
        // panel. Never leaves the session.
        session()->flash('new_api_key_plaintext', $plaintext);

        Toastr::success('API key created. Copy it now — it will not be shown again.', 'Success');
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

    private function serialize(PublicTrackingApiKey $k): array
    {
        return [
            'id'              => $k->id,
            'name'            => $k->name,
            'key_prefix'      => $k->key_prefix,
            'allowed_origins' => $k->allowed_origins ?: [],
            'is_active'       => (bool) $k->is_active,
            'last_used_at'    => optional($k->last_used_at)->toIso8601String(),
            'request_count'   => (int) $k->request_count,
            'created_at'      => optional($k->created_at)->toIso8601String(),
        ];
    }
}
