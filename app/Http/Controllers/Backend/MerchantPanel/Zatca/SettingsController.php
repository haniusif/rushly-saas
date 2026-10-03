<?php

namespace App\Http\Controllers\Backend\MerchantPanel\Zatca;

use App\Enums\Zatca\ZatcaMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Zatca\UpdateSettingsRequest;
use App\Models\Backend\Zatca\ZatcaAuditLog;
use App\Repositories\Zatca\ZatcaSettingRepositoryInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly ZatcaSettingRepositoryInterface $repo) {}

    public function index(): View
    {
        $setting = $this->repo->forCurrentCompany();
        $modes   = ZatcaMode::options();

        return view('backend.merchant_panel.zatca.settings', compact('setting', 'modes'));
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
}
