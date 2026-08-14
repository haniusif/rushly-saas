<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SettingsHubController extends Controller
{
    public function index(): View
    {
        $groups = $this->cards();
        return view('backend.settings.index', compact('groups'));
    }

    /**
     * Card catalogue grouped by domain. Each card declares the permission
     * required to see it, the route name, an icon class, and short copy.
     * The view filters by permission and renders nothing for empty groups.
     */
    private function cards(): array
    {
        return [
            'identity' => [
                'title' => __('settings_hub.group_identity'),
                'cards' => [
                    ['perm' => 'general_settings_read',    'route' => 'general-settings.index',     'icon' => 'ti ti-settings',      'gradient' => 'from-indigo-500 to-indigo-700', 'title' => __('menus.general_settings'),    'description' => __('settings_hub.general_desc')],
                    ['perm' => 'social_login_settings_update', 'route' => 'social.login.settings.index', 'icon' => 'ti ti-key',     'gradient' => 'from-sky-500 to-sky-700',       'title' => __('menus.social_login_settings'), 'description' => __('settings_hub.social_desc')],
                    ['perm' => 'integrations_read',        'route' => 'integrations.index',         'icon' => 'ti ti-plug',          'gradient' => 'from-cyan-500 to-cyan-700',      'title' => __('menus.integrations') ?: 'Integrations', 'description' => __('settings_hub.integrations_desc')],
                    ['perm' => 'mobile_apps_read',         'route' => 'mobile-apps.index',          'icon' => 'ti ti-device-mobile', 'gradient' => 'from-blue-500 to-blue-700',      'title' => __('mobile_apps.title'),           'description' => __('settings_hub.mobile_apps_desc')],
                ],
            ],

            'operations' => [
                'title' => __('settings_hub.group_operations'),
                'cards' => [
                    ['perm' => 'delivery_category_read',   'route' => 'delivery-category.index',    'icon' => 'ti ti-package',       'gradient' => 'from-emerald-500 to-emerald-700', 'title' => __('menus.delivery_category'),   'description' => __('settings_hub.delivery_category_desc')],
                    ['perm' => 'delivery_charge_read',     'route' => 'delivery-charge.index',      'icon' => 'ti ti-coin',          'gradient' => 'from-emerald-500 to-emerald-700', 'title' => __('menus.delivery_charge'),     'description' => __('settings_hub.delivery_charge_desc')],
                    ['perm' => 'delivery_type_read',       'route' => 'delivery-type.index',        'icon' => 'ti ti-route',         'gradient' => 'from-emerald-500 to-emerald-700', 'title' => __('menus.delivery_type'),       'description' => __('settings_hub.delivery_type_desc')],
                    ['perm' => 'liquid_fragile_read',      'route' => 'liquid-fragile.index',       'icon' => 'ti ti-flask',         'gradient' => 'from-amber-500 to-amber-700',     'title' => __('menus.liquid_fragile'),      'description' => __('settings_hub.liquid_desc')],
                    ['perm' => 'packaging_read',           'route' => 'packaging.index',            'icon' => 'ti ti-box',           'gradient' => 'from-amber-500 to-amber-700',     'title' => __('menus.packaging'),           'description' => __('settings_hub.packaging_desc')],
                    ['perm' => 'asset_category_read',      'route' => 'asset-category.index',       'icon' => 'ti ti-tags',          'gradient' => 'from-amber-500 to-amber-700',     'title' => __('menus.assets_category'),     'description' => __('settings_hub.assets_desc')],
                    ['perm' => 'label_template_manage',    'route' => 'label-templates.index',      'icon' => 'ti ti-id-badge-2',    'gradient' => 'from-violet-500 to-violet-700',   'title' => __('menus.menu_label_templates'),'description' => __('settings_hub.label_desc')],
                ],
            ],

            'communications' => [
                'title' => __('settings_hub.group_communications'),
                'cards' => [
                    ['perm' => 'sms_settings_read',          'route' => 'sms-settings.index',          'icon' => 'ti ti-message',  'gradient' => 'from-blue-500 to-blue-700', 'title' => __('menus.sms_settings'),         'description' => __('settings_hub.sms_settings_desc')],
                    ['perm' => 'sms_send_settings_read',     'route' => 'sms-send-settings.index',     'icon' => 'ti ti-send',     'gradient' => 'from-blue-500 to-blue-700', 'title' => __('menus.sms_send_settings'),    'description' => __('settings_hub.sms_send_desc')],
                    ['perm' => 'notification_settings_read', 'route' => 'notification-settings.index', 'icon' => 'ti ti-bell',     'gradient' => 'from-blue-500 to-blue-700', 'title' => __('menus.notification_settings'),'description' => __('settings_hub.notifications_desc')],
                    ['perm' => 'googlemap_settings_read',    'route' => 'googlemap-settings.index',    'icon' => 'ti ti-map-pin',  'gradient' => 'from-blue-500 to-blue-700', 'title' => __('menus.google_map_settings'),  'description' => __('settings_hub.googlemap_desc')],
                ],
            ],

            'finance' => [
                'title' => __('settings_hub.group_finance'),
                'cards' => [
                    ['perm' => 'invoice_generate_menually',      'route' => 'invoice.generate.menually.index', 'icon' => 'ti ti-file-invoice', 'gradient' => 'from-rose-500 to-rose-700', 'title' => __('menus.invoice_generate_menually'), 'description' => __('settings_hub.invoice_gen_desc')],
                    ['perm' => 'payout_setup_settings_read',     'route' => 'payout.setup.settings.index',    'icon' => 'ti ti-credit-card',  'gradient' => 'from-rose-500 to-rose-700', 'title' => __('menus.payout_setup'),              'description' => __('settings_hub.payout_desc')],
                    ['perm' => 'zatca_manage',                   'route' => 'zatca.settings.index',           'icon' => 'ti ti-receipt-tax',  'gradient' => 'from-rose-500 to-rose-700', 'title' => __('menus.menu_zatca_settings'),       'description' => __('settings_hub.zatca_desc')],
                ],
            ],
        ];
    }
}
