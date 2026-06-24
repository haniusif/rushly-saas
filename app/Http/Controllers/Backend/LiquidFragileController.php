<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LiquidFragileController extends Controller
{
    public function index()
    {
        return $this->renderPage('view');
    }

    public function edit()
    {
        return $this->renderPage('edit');
    }

    public function update(Request $request)
    {
        $liquid = Config::companywise()->where('key', 'fragile_liquid_charge')->first();
        $liquid->value = $request->charge;
        $liquid->save();
        if ($liquid) {
            Toastr::success('Liquid/Fragile updated successfully.', __('message.success'));
            return redirect()->route('liquid-fragile.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back();
    }

    public function status(Request $request)
    {
        $liquid = Config::companywise()->where('key', 'fragile_liquid_status')->first();
        $liquid->value = $liquid->value == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE;
        $liquid->save();
        return $liquid;
    }

    private function renderPage(string $mode)
    {
        return Inertia::render('Admin/LiquidFragile/Index', [
            'mode'   => $mode,
            'charge' => (float) (settingHelper('fragile_liquid_charge') ?? 0),
            'active' => settingHelper('fragile_liquid_status') == Status::ACTIVE,
            'currency' => settings()->currency,
            'permissions' => [
                'update'        => hasPermission('liquid_fragile_update'),
                'status_change' => hasPermission('liquid_status_change'),
            ],
            'urls' => [
                'edit'   => route('liquid.fragile.edit'),
                'index'  => route('liquid-fragile.index'),
                'status' => route('liquid-fragile.status'),
                'update' => route('liquid.fragile.update'),
            ],
            't' => [
                'title'    => __('menus.liquid_fragile') ?: 'Liquid / Fragile',
                'view'     => __('levels.view') ?: 'View',
                'edit'     => __('levels.edit') ?: 'Edit',
                'update'   => __('levels.update') ?: 'Update',
                'cancel'   => __('levels.cancel') ?: 'Cancel',
                'back'     => __('levels.back') ?: 'Back',
                'name'     => __('liquid.title') ?: 'Type',
                'name_value' => __('placeholder.Liquid_Fragile') ?: 'Liquid / Fragile',
                'status'   => __('levels.status') ?: 'Status',
                'charge'   => __('liquid.charge') ?: 'Charge',
                'actions'  => __('levels.actions') ?: 'Actions',
            ],
        ]);
    }
}
