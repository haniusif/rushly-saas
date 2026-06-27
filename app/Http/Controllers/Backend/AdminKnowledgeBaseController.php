<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class AdminKnowledgeBaseController extends Controller
{
    /**
     * Source of truth for which sections exist, their icons, and their
     * sub-pages. The matching content lives in lang/{locale}/kb_{slug}.php.
     *
     * `external_url` (when present) means clicking the section card jumps
     * to a dedicated page elsewhere instead of opening a hub detail view —
     * used for WMS which already has its own full KB.
     */
    private const SECTIONS = [
        'main' => [
            'icon' => 'LayoutDashboard',
            'subs' => ['dashboard'],
        ],
        'shipments' => [
            'icon' => 'Package',
            'subs' => ['parcels', 'bulk-action', 'ndr', 'abnormal'],
        ],
        'wms' => [
            'icon' => 'Warehouse',
            'subs' => [],
            'external_route' => 'wms.knowledge-base',
        ],
        'operations' => [
            'icon' => 'Truck',
            'subs' => ['couriers', 'tms', 'hubs', 'merchants', 'pickup-request'],
        ],
        'finance' => [
            'icon' => 'DollarSign',
            'subs' => ['payment-received', 'payout', 'accounts', 'wallet-request'],
        ],
        'hr' => [
            'icon' => 'UserCog',
            'subs' => ['users-roles', 'payroll', 'assets'],
        ],
        'productivity' => [
            'icon' => 'ListChecks',
            'subs' => ['todo', 'support', 'news', 'push-notifications', 'fraud'],
        ],
        'billing' => [
            'icon' => 'Receipt',
            'subs' => ['subscribe', 'subscription', 'reports'],
        ],
        'zatca' => [
            'icon' => 'FileText',
            'subs' => ['invoices', 'settings'],
        ],
        'cms' => [
            'icon' => 'Layout',
            'subs' => ['front-web'],
        ],
        'system' => [
            'icon' => 'History',
            'subs' => ['logs'],
        ],
        'settings' => [
            'icon' => 'Settings',
            'subs' => [
                'general', 'integrations', 'delivery-category', 'delivery-charge',
                'delivery-type', 'liquid-fragile', 'sms', 'notifications',
                'googlemap', 'social-login', 'payment-gateway', 'packaging',
                'assets-category', 'invoice-generate',
            ],
        ],
    ];

    private const SHOT_DIR = 'images/kb';

    public function index()
    {
        $sections = collect(self::SECTIONS)->map(function ($def, $slug) {
            $url = ($def['external_route'] ?? null) ? route($def['external_route']) : route('admin.kb.show', $slug);
            return [
                'slug'        => $slug,
                'label'       => __('kb_' . str_replace('-', '_', $slug) . '.label'),
                'overview'    => __('kb_' . str_replace('-', '_', $slug) . '.overview'),
                'icon'        => $def['icon'],
                'sub_count'   => count($def['subs']),
                'url'         => $url,
                'is_external' => !empty($def['external_route']),
            ];
        })->values();

        return Inertia::render('Admin/KnowledgeBase/Hub', [
            'sections' => $sections,
            't' => [
                'title'         => __('kb_chrome.title'),
                'subtitle'      => __('kb_chrome.subtitle'),
                'open_section'  => __('kb_chrome.open_section'),
                'sub_pages'     => __('kb_chrome.sub_pages'),
                'no_subs'       => __('kb_chrome.no_subs'),
            ],
        ]);
    }

    public function show(string $section)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        $def     = self::SECTIONS[$section];
        $langKey = 'kb_' . str_replace('-', '_', $section);
        $content = trans($langKey);

        if (!is_array($content)) {
            $content = ['label' => $section, 'overview' => '', 'sub_pages' => []];
        }

        $subs = collect($def['subs'])->map(function ($subSlug) use ($content) {
            $sub = $content['sub_pages'][$subSlug] ?? [];
            return array_merge(['slug' => $subSlug], $sub);
        })->values();

        $screenshots = collect($def['subs'])->mapWithKeys(function ($subSlug) use ($section) {
            $path = public_path(self::SHOT_DIR . '/' . $section . '/' . $subSlug . '.png');
            return [$subSlug => is_file($path) ? filemtime($path) : null];
        })->all();

        return Inertia::render('Admin/KnowledgeBase/Section', [
            'section' => [
                'slug'     => $section,
                'icon'     => $def['icon'],
                'label'    => $content['label']    ?? $section,
                'overview' => $content['overview'] ?? '',
                'subs'     => $subs,
            ],
            'screenshots' => $screenshots,
            'urls' => [
                'hub' => route('admin.kb.index'),
            ],
            't' => [
                'back_to_hub'   => __('kb_chrome.back_to_hub'),
                'key_fields'    => __('kb_chrome.key_fields'),
                'status_flow'   => __('kb_chrome.status_flow'),
                'pages_label'   => __('kb_chrome.pages_label'),
                'cross_links'   => __('kb_chrome.cross_links'),
                'notes'         => __('kb_chrome.notes'),
                'pending'       => __('kb_chrome.content_pending'),
            ],
        ]);
    }

    public function uploadScreenshot(Request $request, string $section, string $sub)
    {
        $this->assertValid($section, $sub);

        $request->validate([
            'screenshot' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        $dir = public_path(self::SHOT_DIR . '/' . $section);
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $dest = $dir . '/' . $sub . '.png';

        $upload = $request->file('screenshot');
        $ext    = strtolower($upload->getClientOriginalExtension());

        $img = match ($ext) {
            'png'         => @imagecreatefrompng($upload->getPathname()),
            'jpg', 'jpeg' => @imagecreatefromjpeg($upload->getPathname()),
            'webp'        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($upload->getPathname()) : null,
            default       => null,
        };

        if (!$img) {
            return back()->withErrors(['screenshot' => 'Could not read the uploaded image.']);
        }

        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagepng($img, $dest, 6);
        imagedestroy($img);
        @chmod($dest, 0644);

        return back();
    }

    public function deleteScreenshot(string $section, string $sub)
    {
        $this->assertValid($section, $sub);

        $path = public_path(self::SHOT_DIR . '/' . $section . '/' . $sub . '.png');
        if (is_file($path)) {
            @unlink($path);
        }

        return back();
    }

    private function assertValid(string $section, string $sub): void
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);
        abort_unless(in_array($sub, self::SECTIONS[$section]['subs'], true), 404);
    }
}
