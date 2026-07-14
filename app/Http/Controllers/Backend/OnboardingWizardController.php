<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Backend\Currency;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\GoogleMapSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * First-run setup wizard for a fresh tenant. Rendered inline (Inertia) after
 * an Admin logs in for the first time to a tenant whose
 * general_settings.onboarding_completed_at is still NULL.
 *
 * Steps are ordered but not enforced: any step can be skipped, and "Skip all"
 * stamps completion without saving anything. See RequireOnboarding middleware
 * for the redirect that steers users here.
 */
class OnboardingWizardController extends Controller
{
    /**
     * The canonical step list. Any change here should be mirrored in the
     * React shell (Admin/Onboarding/Index.jsx) which iterates on this same
     * ordering to render the sidebar.
     */
    public const STEPS = ['basics', 'delivery_category', 'delivery_charge', 'delivery_type', 'sms', 'google_maps'];

    public function index()
    {
        $s = $this->settings();

        $currencies = collect();
        try { $currencies = Currency::query()->orderBy('name')->get(); } catch (\Throwable $e) {}

        $existingCategory = null;
        try {
            $existingCategory = Deliverycategory::query()->orderBy('id')->first();
        } catch (\Throwable $e) {}

        $mapKey = '';
        try {
            $g = GoogleMapSetting::query()->first();
            $mapKey = (string) optional($g)->map_key;
        } catch (\Throwable $e) {}

        return Inertia::render('Admin/Onboarding/Index', [
            'settings' => [
                'name'     => (string) optional($s)->name,
                'phone'    => (string) optional($s)->phone,
                'email'    => (string) optional($s)->email,
                'address'  => (string) optional($s)->address,
                'currency' => (string) optional($s)->currency,
                'category_title' => (string) optional($existingCategory)->title,
                'map_key'  => $mapKey,
            ],
            'lookups' => [
                'currencies' => $currencies->map(fn ($c) => [
                    'value' => $c->symbol,
                    'label' => $c->name.' '.$c->symbol,
                ])->values(),
            ],
            'urls' => [
                'save_step'         => route('onboarding.save'),
                'skip_step'         => route('onboarding.skip'),
                'complete'          => route('onboarding.complete'),
                'delivery_charge'   => route('delivery-charge.index'),
                'delivery_type'     => route('delivery-type.index'),
                'sms_settings'      => route('sms-settings.index'),
                'dashboard'         => route('dashboard.index'),
            ],
            't' => $this->translations(),
        ]);
    }

    public function save(Request $request)
    {
        $step = (string) $request->input('step');
        abort_unless(in_array($step, self::STEPS, true), 422);

        switch ($step) {
            case 'basics':
                $s = $this->settings();
                $s->name     = (string) $request->input('name', $s->name);
                $s->phone    = (string) $request->input('phone', $s->phone);
                $s->email    = (string) $request->input('email', $s->email);
                $s->address  = (string) $request->input('address', $s->address);
                $s->currency = (string) $request->input('currency', $s->currency);
                $s->save();
                break;

            case 'delivery_category':
                $title = trim((string) $request->input('category_title'));
                if ($title !== '' && Schema::hasTable('delivery_categories')) {
                    Deliverycategory::firstOrCreate(
                        ['title' => $title],
                        ['position' => 1, 'status' => 1]
                    );
                }
                break;

            case 'google_maps':
                $key = trim((string) $request->input('map_key'));
                if ($key !== '' && class_exists(GoogleMapSetting::class)) {
                    $g = GoogleMapSetting::query()->first() ?: new GoogleMapSettings();
                    $g->map_key = $key;
                    $g->save();
                }
                break;

            // Link-out steps have no inline form; the client just POSTs the
            // step name to record advancement. Nothing to persist here.
            case 'delivery_charge':
            case 'delivery_type':
            case 'sms':
                break;
        }

        return redirect()->route('onboarding.index');
    }

    public function skip(Request $request)
    {
        // Skip is purely a client-side advance; no server state changes.
        // Kept as an endpoint so future analytics can hook here.
        return response()->json(['ok' => true]);
    }

    public function complete(Request $request)
    {
        $s = $this->settings();
        if ($s && $s->onboarding_completed_at === null) {
            DB::table('general_settings')
                ->where('id', $s->id)
                ->update(['onboarding_completed_at' => now()]);
        }
        return redirect()->route('dashboard.index');
    }

    private function settings(): ?GeneralSettings
    {
        try {
            $user = Auth::user();
            $id   = $user ? (int) $user->company_id : 1;
            return GeneralSettings::query()->find($id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function translations(): array
    {
        return [
            'title'          => __('onboarding.title') ?: 'Welcome — let’s set things up',
            'subtitle'       => __('onboarding.subtitle') ?: 'A few quick settings to get your account production-ready. You can skip any step and come back later.',
            'save_continue'  => __('onboarding.save_continue') ?: 'Save & continue',
            'skip_step'      => __('onboarding.skip_step') ?: 'Skip for now',
            'skip_all'       => __('onboarding.skip_all') ?: 'Skip all',
            'open_full_page' => __('onboarding.open_full_page') ?: 'Open full settings',
            'mark_done'      => __('onboarding.mark_done') ?: 'I’ve done this',
            'progress'       => __('onboarding.progress') ?: 'Progress',
            'steps' => [
                'basics'            => __('onboarding.basics') ?: 'Company basics',
                'delivery_category' => __('onboarding.delivery_category') ?: 'Delivery category',
                'delivery_charge'   => __('onboarding.delivery_charge') ?: 'Delivery charges',
                'delivery_type'     => __('onboarding.delivery_type') ?: 'Delivery types',
                'sms'               => __('onboarding.sms') ?: 'SMS settings',
                'google_maps'       => __('onboarding.google_maps') ?: 'Google Maps',
            ],
            'basics_hint'   => __('onboarding.basics_hint') ?: 'How your company appears to customers and on invoices.',
            'category_hint' => __('onboarding.category_hint') ?: 'The default weight/dimension bucket used to price a parcel. You can add more later.',
            'category_placeholder' => __('onboarding.category_placeholder') ?: 'e.g. Standard parcel',
            'linkout_hint'  => __('onboarding.linkout_hint') ?: 'This module has multiple related tables. Open the full settings page in a new tab, configure what you need, then click "I’ve done this" to continue.',
            'google_hint'   => __('onboarding.google_hint') ?: 'API key for address autocomplete and map pins. Restrict the key to your tenant domain in the Google Cloud console.',
            'name'          => __('levels.application_name') ?: 'Company name',
            'phone'         => __('levels.phone') ?: 'Phone',
            'email'         => __('levels.email') ?: 'Email',
            'address'       => __('levels.address') ?: 'Address',
            'currency'      => __('levels.currency') ?: 'Currency',
            'map_key'       => __('levels.map_key') ?: 'Google Maps API key',
        ];
    }
}
