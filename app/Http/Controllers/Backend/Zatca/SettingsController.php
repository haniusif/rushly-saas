<?php

namespace App\Http\Controllers\Backend\Zatca;

use App\Enums\Zatca\ZatcaMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Zatca\UpdateSettingsRequest;
use App\Models\Backend\Zatca\ZatcaAuditLog;
use App\Repositories\Zatca\ZatcaSettingRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly ZatcaSettingRepositoryInterface $repo) {}

    public function index(): Response
    {
        $setting = $this->repo->forCurrentCompany();

        return Inertia::render('Admin/Zatca/Settings/Index', [
            'setting' => $this->serialize($setting),
            'lookups' => [
                'modes'      => ZatcaMode::options(),
                'currencies' => ['SAR' => 'SAR (﷼)', 'USD' => 'USD', 'AED' => 'AED'],
            ],
            'urls' => [
                'update' => route('zatca.settings.update'),
            ],
            't' => $this->translations(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $setting = $this->repo->update(
            (int) settings('company_id'),
            $request->validated(),
        );

        ZatcaAuditLog::create([
            'company_id' => $setting->company_id,
            'actor_id'   => optional(auth()->user())->id,
            'action'     => 'settings_updated',
            'payload'    => $request->validated(),
            'ip'         => $request->ip(),
        ]);

        Toastr::success(__('zatca.settings_saved'));
        return back();
    }

    private function serialize($s): array
    {
        return [
            'id'                => $s->id,
            'seller_name_en'    => (string) $s->seller_name_en,
            'seller_name_ar'    => (string) $s->seller_name_ar,
            'vat_number'        => (string) $s->vat_number,
            'cr_number'         => (string) $s->cr_number,
            'address_street_en' => (string) $s->address_street_en,
            'address_street_ar' => (string) $s->address_street_ar,
            'building_number'   => (string) $s->building_number,
            'district_en'       => (string) $s->district_en,
            'district_ar'       => (string) $s->district_ar,
            'city_en'           => (string) $s->city_en,
            'city_ar'           => (string) $s->city_ar,
            'postal_code'       => (string) $s->postal_code,
            'country_code'      => $s->country_code ?: 'SA',
            'vat_rate'          => (float) $s->vat_rate,
            'currency'          => $s->currency ?: 'SAR',
            'mode'              => $s->mode ?: 'sandbox',
            'enabled'           => (bool) $s->enabled,
            'auto_generate'     => (bool) $s->auto_generate,
            'invoice_prefix'    => $s->invoice_prefix ?: 'ZAT-',
            'is_ready'          => $s->isReady(),
            'last_invoice_counter' => (int) $s->last_invoice_counter,
        ];
    }

    private function translations(): array
    {
        return [
            'title'        => __('zatca.settings_title'),
            'subtitle'     => __('zatca.settings_subtitle'),
            'save'         => __('zatca.save'),
            'enabled'      => __('zatca.enabled'),
            'auto_generate'=> __('zatca.auto_generate'),
            'seller_info'  => __('zatca.seller_info'),
            'seller_name_en' => __('zatca.seller_name_en'),
            'seller_name_ar' => __('zatca.seller_name_ar'),
            'vat_number'     => __('zatca.vat_number'),
            'cr_number'      => __('zatca.cr_number'),
            'address'        => __('zatca.address'),
            'street_en'      => __('zatca.street_en'),
            'street_ar'      => __('zatca.street_ar'),
            'building_no'    => __('zatca.building_no'),
            'district_en'    => __('zatca.district_en'),
            'district_ar'    => __('zatca.district_ar'),
            'city_en'        => __('zatca.city_en'),
            'city_ar'        => __('zatca.city_ar'),
            'postal_code'    => __('zatca.postal_code'),
            'country_code'   => __('zatca.country_code'),
            'tax'            => __('zatca.tax'),
            'vat_rate'       => __('zatca.vat_rate'),
            'currency'       => __('zatca.currency'),
            'mode'           => __('zatca.mode'),
            'invoice_prefix' => __('zatca.invoice_prefix'),
            'ready_yes'      => __('zatca.ready_yes'),
            'ready_no'       => __('zatca.ready_no'),
        ];
    }
}
