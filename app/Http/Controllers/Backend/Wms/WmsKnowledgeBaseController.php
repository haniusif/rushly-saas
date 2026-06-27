<?php

namespace App\Http\Controllers\Backend\Wms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class WmsKnowledgeBaseController extends Controller
{
    private const SLUGS = [
        'dashboard', 'products', 'locations', 'stock', 'grn',
        'adjustments', 'cycle-counts', 'damage', 'fulfillment', 'outbound',
    ];

    private const SHOT_DIR = 'images/wms-kb';

    public function index()
    {
        return Inertia::render('Admin/Wms/KnowledgeBase/Index', [
            'urls' => [
                'dashboard'    => route('wms.dashboard'),
                'products'     => route('wms.products.index'),
                'locations'    => route('wms.locations.index'),
                'stock'        => route('wms.stock.index'),
                'grn'          => route('wms.grn.index'),
                'adjustments'  => route('wms.adjustments.index'),
                'cycle_counts' => route('wms.cycle-counts.index'),
                'damage'       => route('wms.damage.index'),
                'fulfillment'  => route('wms.fulfillment.index'),
                'outbound'     => route('wms.outbound.index'),
            ],
            // mtime if the screenshot exists, null otherwise. Used by the
            // frontend both to decide whether to render the image and to
            // cache-bust after re-upload.
            'screenshots' => collect(self::SLUGS)->mapWithKeys(function ($slug) {
                $path = public_path(self::SHOT_DIR . '/' . $slug . '.png');
                return [$slug => is_file($path) ? filemtime($path) : null];
            })->all(),
            'can_update' => (function () {
                $user = Auth::user();
                return $user && in_array('knowledge_base_update', (array) ($user->permissions ?? []), true);
            })(),
        ]);
    }

    public function uploadScreenshot(Request $request, string $slug)
    {
        abort_unless(in_array($slug, self::SLUGS, true), 404);

        $request->validate([
            'screenshot' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        $dir = public_path(self::SHOT_DIR);
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $dest   = $dir . '/' . $slug . '.png';
        $upload = $request->file('screenshot');
        $ext    = strtolower($upload->getClientOriginalExtension());

        // Normalise everything to PNG via GD so the JSX can keep hard-coding .png.
        $src = match ($ext) {
            'png'         => @imagecreatefrompng($upload->getPathname()),
            'jpg', 'jpeg' => @imagecreatefromjpeg($upload->getPathname()),
            'webp'        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($upload->getPathname()) : null,
            default       => null,
        };

        if (!$src) {
            return back()->withErrors(['screenshot' => 'Could not read the uploaded image.']);
        }

        imagealphablending($src, false);
        imagesavealpha($src, true);
        imagepng($src, $dest, 6);
        imagedestroy($src);
        @chmod($dest, 0644);

        return back();
    }

    public function deleteScreenshot(string $slug)
    {
        abort_unless(in_array($slug, self::SLUGS, true), 404);

        $path = public_path(self::SHOT_DIR . '/' . $slug . '.png');
        if (is_file($path)) {
            @unlink($path);
        }

        return back();
    }
}
