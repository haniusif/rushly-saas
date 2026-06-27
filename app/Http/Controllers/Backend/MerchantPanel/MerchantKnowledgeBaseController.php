<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

/**
 * Merchant-facing operator handbook. Mirrors the admin KB pattern
 * (App\Http\Controllers\Backend\AdminKnowledgeBaseController) but documents
 * the merchant panel — shipments, accounting, wallet, reports, settings.
 *
 * Reads are open to any logged-in merchant. Screenshot upload / delete is
 * gated by the existing knowledge_base_update permission (admins only) so
 * screenshots are curated centrally and merchants see the same help across
 * tenants. The middleware on the screenshot routes enforces this.
 */
class MerchantKnowledgeBaseController extends Controller
{
    private const SECTIONS = [
        'dashboard'     => ['icon' => 'LayoutDashboard', 'subs' => ['overview']],
        'support'       => ['icon' => 'MessageCircle',   'subs' => ['tickets']],
        'wallet'        => ['icon' => 'Wallet',          'subs' => ['my-wallet']],
        'accounting'    => ['icon' => 'Receipt',         'subs' => ['payments-received', 'payout', 'invoice', 'zatca-invoices', 'zatca-settings']],
        'shipments'     => ['icon' => 'Package',         'subs' => ['list', 'create', 'import', 'details']],
        'shipment-bank' => ['icon' => 'PiggyBank',       'subs' => ['bank']],
        'reports'       => ['icon' => 'BarChart3',       'subs' => ['shipments-report', 'total-summary', 'account-transactions', 'statements']],
        'settings'      => ['icon' => 'Settings',        'subs' => ['cod-charges', 'delivery-charges', 'pickup-points']],
    ];

    private const SHOT_DIR = 'images/mkb';

    private function canUpdate(): bool
    {
        $user = Auth::user();
        return $user && in_array('knowledge_base_update', (array) ($user->permissions ?? []), true);
    }

    public function index()
    {
        $sections = collect(self::SECTIONS)->map(function ($def, $slug) {
            $langKey = 'mkb_' . str_replace('-', '_', $slug);
            return [
                'slug'      => $slug,
                'icon'      => $def['icon'],
                'label'     => __($langKey . '.label'),
                'overview'  => __($langKey . '.overview'),
                'sub_count' => count($def['subs']),
                'url'       => route('merchant-panel.kb.show', $slug),
            ];
        })->values();

        return Inertia::render('Merchant/KnowledgeBase/Hub', [
            'sections'   => $sections,
            'can_update' => $this->canUpdate(),
            't' => [
                'title'    => __('mkb_chrome.title'),
                'subtitle' => __('mkb_chrome.subtitle'),
                'sub_pages'=> __('mkb_chrome.sub_pages'),
                'no_subs'  => __('mkb_chrome.no_subs'),
            ],
        ]);
    }

    public function show(string $section)
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        $def     = self::SECTIONS[$section];
        $langKey = 'mkb_' . str_replace('-', '_', $section);
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

        return Inertia::render('Merchant/KnowledgeBase/Section', [
            'section' => [
                'slug'     => $section,
                'icon'     => $def['icon'],
                'label'    => $content['label']    ?? $section,
                'overview' => $content['overview'] ?? '',
                'subs'     => $subs,
            ],
            'screenshots' => $screenshots,
            'can_update'  => $this->canUpdate(),
            'urls'        => ['hub' => route('merchant-panel.kb.index')],
            't' => [
                'back_to_hub' => __('mkb_chrome.back_to_hub'),
                'key_fields'  => __('mkb_chrome.key_fields'),
                'status_flow' => __('mkb_chrome.status_flow'),
                'pages_label' => __('mkb_chrome.pages_label'),
                'cross_links' => __('mkb_chrome.cross_links'),
                'notes'       => __('mkb_chrome.notes'),
                'pending'     => __('mkb_chrome.content_pending'),
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
