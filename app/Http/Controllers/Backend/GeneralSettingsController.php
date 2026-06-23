<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeneralSettingsController extends Controller
{
    protected $repo, $currency;

    public function __construct(GeneralSettingsInterface $repo, CurrencyInterface $currency)
    {
        $this->repo     = $repo;
        $this->currency = $currency;
    }

    public function index()
    {
        $s          = $this->repo->all();
        $currencies = $this->currency->getActive();

        $themeFallback = [
            'sidebar_color'      => '#0f172a',
            'sidebar_text_color' => '#f1f5f9',
            'topbar_color'       => '#ffffff',
            'topbar_text_color'  => '#0f172a',
            'accent_color'       => '#0ea5e9',
        ];

        return Inertia::render('Admin/GeneralSettings/Index', [
            'settings' => [
                'name'                => (string) $s->name,
                'copyright'           => (string) $s->copyright,
                'show_landing_page'   => (bool) $s->show_landing_page,
                'phone'               => (string) $s->phone,
                'email'               => (string) $s->email,
                'address'             => (string) $s->address,
                'currency'            => (string) $s->currency,
                'par_track_prefix'    => strtoupper((string) $s->par_track_prefix),
                'invoice_prefix'      => strtoupper((string) $s->invoice_prefix),
                'primary_color'       => (string) ($s->primary_color ?? '#000000'),
                'text_color'          => (string) ($s->text_color ?? '#000000'),
                'login_layout'        => $s->login_layout ?: 'split',
                'sidebar_color'       => (string) ($s->sidebar_color ?? ''),
                'sidebar_text_color'  => (string) ($s->sidebar_text_color ?? ''),
                'topbar_color'        => (string) ($s->topbar_color ?? ''),
                'topbar_text_color'   => (string) ($s->topbar_text_color ?? ''),
                'accent_color'        => (string) ($s->accent_color ?? ''),
                'sidebar_style'       => (string) ($s->sidebar_style ?? ''),
                'font_family'         => (string) ($s->font_family ?? ''),
                'border_radius'       => (string) ($s->border_radius ?? ''),
                'density'             => (string) ($s->density ?? ''),
                'logo_image'          => $s->logo_image,
                'light_logo_image'    => $s->light_logo_image,
                'favicon_image'       => $s->favicon_image,
            ],
            'theme_fallbacks' => $themeFallback,
            'lookups' => [
                'currencies' => collect($currencies)->map(fn ($c) => [
                    'value' => $c->symbol,
                    'label' => $c->name . ' ' . $c->symbol,
                ])->values(),
                'login_layouts' => collect(['split','centered','fullbleed'])->map(fn ($k) => [
                    'value' => $k,
                    'label' => __('merchant.login_layout_' . $k) ?: ucfirst($k),
                ])->values(),
                'sidebar_styles' => collect(['dark','light','brand'])->map(fn ($k) => [
                    'value' => $k,
                    'label' => __('merchant.sidebar_style_' . $k) ?: ucfirst($k),
                ])->values(),
                'fonts' => collect(['inter','cairo','tajawal','roboto','system'])->map(fn ($k) => [
                    'value' => $k,
                    'label' => __('merchant.font_' . $k) ?: ucfirst($k),
                ])->values(),
                'border_radii' => collect(['sharp','default','rounded'])->map(fn ($k) => [
                    'value' => $k,
                    'label' => __('merchant.border_radius_' . $k) ?: ucfirst($k),
                ])->values(),
                'densities' => collect(['comfortable','dense'])->map(fn ($k) => [
                    'value' => $k,
                    'label' => __('merchant.density_' . $k) ?: ucfirst($k),
                ])->values(),
            ],
            'permissions' => [
                'update' => hasPermission('general_settings_update'),
            ],
            'urls' => [
                'submit'    => route('general-settings.update'),
                'dashboard' => route('dashboard.index'),
            ],
            't' => [
                'title'        => __('menus.general_settings') ?: 'General settings',
                'breadcrumb_settings' => __('menus.settings') ?: 'Settings',
                'save'         => __('levels.save_change') ?: 'Save changes',
                'nav_brand'    => __('levels.application_name') ?: 'Brand',
                'nav_contact'  => (__('levels.phone') ?: 'Phone') . ' / ' . (__('levels.email') ?: 'Email'),
                'nav_locale'   => __('levels.currency') ?: 'Localisation',
                'nav_theme'    => __('levels.primary_color') ?: 'Theme',
                'nav_logos'    => __('levels.logo') ?: 'Logos',
                'application_name' => __('levels.application_name') ?: 'Application name',
                'copyright'    => __('levels.copyright') ?: 'Copyright',
                'phone'        => __('levels.phone') ?: 'Phone',
                'email'        => __('levels.email') ?: 'Email',
                'address'      => __('levels.address') ?: 'Address',
                'currency'     => __('levels.currency') ?: 'Currency',
                'par_track_prefix' => (__('settings.parcel_tracking') ?: 'Tracking') . ' ' . (__('levels.prefix') ?: 'prefix'),
                'invoice_prefix'   => (__('invoice.invoice') ?: 'Invoice') . ' ' . (__('levels.prefix') ?: 'prefix'),
                'primary_color' => __('levels.primary_color') ?: 'Primary color',
                'text_color'    => __('levels.text_color') ?: 'Text color',
                'login_layout'  => __('merchant.login_layout') ?: 'Login layout',
                'login_layout_help' => __('merchant.login_layout_help') ?: '',
                'sidebar_color' => __('merchant.sidebar_color') ?: 'Sidebar background',
                'sidebar_text_color' => __('merchant.sidebar_text_color') ?: 'Sidebar text',
                'topbar_color'  => __('merchant.topbar_color') ?: 'Topbar background',
                'topbar_text_color' => __('merchant.topbar_text_color') ?: 'Topbar text',
                'accent_color'  => __('merchant.accent_color') ?: 'Accent',
                'sidebar_style' => __('merchant.sidebar_style') ?: 'Sidebar style',
                'font_family'   => __('merchant.font_family') ?: 'Font',
                'border_radius' => __('merchant.border_radius') ?: 'Corner radius',
                'density'       => __('merchant.density') ?: 'Density',
                'theme_section_colors' => __('merchant.theme_section_colors') ?: 'Colors',
                'theme_section_layout' => __('merchant.theme_section_layout') ?: 'Layout',
                'theme_inherit' => __('merchant.theme_inherit') ?: 'Inherit',
                'logo'          => __('levels.logo') ?: 'Logo',
                'light_logo'    => __('levels.light_logo') ?: 'Light logo',
                'favicon'       => __('levels.favicon') ?: 'Favicon',
                'show_landing'  => 'Show landing page on /',
                'show_landing_help' => 'When off, visiting / redirects to the login page instead of the public landing page.',
            ],
        ]);
    }

    public function update(Request $request)
    {
        if (settings()->id == 1 && env('DEMO')) {
            Toastr::error('Update system is disable for the demo mode.', __('message.error'));
            return redirect()->back();
        }
        $this->repo->update($request);
        Toastr::success(__('settings.save_change'), __('message.success'));
        return redirect()->route('general-settings.index');
    }
}
