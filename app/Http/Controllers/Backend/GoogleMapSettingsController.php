<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleMapSetting\StoreRequest;
use App\Repositories\GoogleMapSettings\GoogleMapSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Inertia\Inertia;

class GoogleMapSettingsController extends Controller
{
    protected $repo;

    public function __construct(GoogleMapSettingsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $settings = $this->repo->all();

        return Inertia::render('Admin/GoogleMap/Index', [
            'map_key' => (string) optional($settings)->map_key,
            'urls'    => [
                'submit'       => route('googlemap-settings.update'),
                'integrations' => route('integrations.index'),
                'console'      => 'https://console.cloud.google.com/google/maps-apis/credentials',
            ],
            't' => [
                'title'      => __('menus.google_map_settings') ?: 'Google Map settings',
                'breadcrumb_settings' => __('menus.settings') ?: 'Settings',
                'map_key'    => __('levels.map_key') ?: 'Maps API key',
                'map_help'   => 'Restrict the key to your tenant domain(s) and enable Places + Geocoding + Maps JavaScript APIs in the Google Cloud console.',
                'save'       => __('levels.save_change') ?: 'Save changes',
                'back'       => __('levels.back') ?: 'Back',
                'open_console' => 'Open Google Cloud console',
                'enabled_apis' => 'Required Google APIs',
                'apis_list'  => 'Maps JavaScript · Places · Geocoding · Routes (optional)',
            ],
        ]);
    }

    public function update(StoreRequest $request)
    {
        $this->repo->update($request);
        Toastr::success(__('settings.save_change'), __('message.success'));
        return redirect()->route('googlemap-settings.index');
    }
}
