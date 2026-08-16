<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MobileAppsController extends Controller
{
    public function index(): Response
    {
        // Only DATA crosses to the front-end. The Blade version also shipped
        // `icon` (Tabler class) and `gradient` (Tailwind class) strings, which
        // cannot survive the move: Tailwind only generates classes it can see
        // in the source, so a gradient built at runtime in PHP would emit no
        // CSS at all. The React page keys its own icon/colour off `key`.
        return Inertia::render('Admin/Settings/MobileApps', [
            'apps' => collect($this->apps())->map(fn ($a) => [
                'key'         => $a['key'],
                'title'       => $a['title'],
                'audience'    => $a['audience'],
                'description' => $a['description'],
                'repo'        => $a['repo'],
            ])->values(),
            't' => [
                'title'        => __('mobile_apps.title') ?: 'Mobile apps',
                'subtitle'     => __('mobile_apps.subtitle'),
                'footer_note'  => __('mobile_apps.footer_note'),
                'settings'     => __('settings_hub.title') ?: 'Settings',
                'audience'     => 'Audience',
                'repository'   => 'Repository',
            ],
        ]);
    }

    /**
     * Static catalog of the 8 Flutter mobile apps that consume /api/v10/*.
     * Kept as an in-controller array (no DB / config) — matches the
     * shape used in RUSHLY_APPS_OVERVIEW.md and MOBILE_APPS.md.
     */
    private function apps(): array
    {
        return [
            [
                'key' => 'driver',
                'title' => __('mobile_apps.driver_title'),
                'audience' => __('mobile_apps.driver_audience'),
                'description' => __('mobile_apps.driver_desc'),
                'icon' => 'ti ti-truck-delivery',
                'gradient' => 'from-emerald-500 to-emerald-700',
                'repo' => 'rushly-driver-app',
            ],
            [
                'key' => 'merchant',
                'title' => __('mobile_apps.merchant_title'),
                'audience' => __('mobile_apps.merchant_audience'),
                'description' => __('mobile_apps.merchant_desc'),
                'icon' => 'ti ti-building-store',
                'gradient' => 'from-indigo-500 to-indigo-700',
                'repo' => 'rushly-merchant-app',
            ],
            [
                'key' => 'admin',
                'title' => __('mobile_apps.admin_title'),
                'audience' => __('mobile_apps.admin_audience'),
                'description' => __('mobile_apps.admin_desc'),
                'icon' => 'ti ti-shield-check',
                'gradient' => 'from-rose-500 to-rose-700',
                'repo' => 'rushly-admin-app',
            ],
            [
                'key' => 'supervisor',
                'title' => __('mobile_apps.supervisor_title'),
                'audience' => __('mobile_apps.supervisor_audience'),
                'description' => __('mobile_apps.supervisor_desc'),
                'icon' => 'ti ti-eye',
                'gradient' => 'from-sky-500 to-sky-700',
                'repo' => 'rushly-supervisor-app',
            ],
            [
                'key' => 'warehouse',
                'title' => __('mobile_apps.warehouse_title'),
                'audience' => __('mobile_apps.warehouse_audience'),
                'description' => __('mobile_apps.warehouse_desc'),
                'icon' => 'ti ti-building-warehouse',
                'gradient' => 'from-amber-500 to-amber-700',
                'repo' => 'rushly-warehouse-app',
            ],
            [
                'key' => 'sorting',
                'title' => __('mobile_apps.sorting_title'),
                'audience' => __('mobile_apps.sorting_audience'),
                'description' => __('mobile_apps.sorting_desc'),
                'icon' => 'ti ti-sort-descending',
                'gradient' => 'from-violet-500 to-violet-700',
                'repo' => 'rushly-sorting-app',
            ],
            [
                'key' => 'fleet',
                'title' => __('mobile_apps.fleet_title'),
                'audience' => __('mobile_apps.fleet_audience'),
                'description' => __('mobile_apps.fleet_desc'),
                'icon' => 'ti ti-steering-wheel',
                'gradient' => 'from-cyan-500 to-cyan-700',
                'repo' => 'rushly-fleet-app',
            ],
            [
                'key' => 'scanner',
                'title' => __('mobile_apps.scanner_title'),
                'audience' => __('mobile_apps.scanner_audience'),
                'description' => __('mobile_apps.scanner_desc'),
                'icon' => 'ti ti-scan',
                'gradient' => 'from-blue-500 to-blue-700',
                'repo' => 'rushly-scanner-app',
            ],
        ];
    }
}
