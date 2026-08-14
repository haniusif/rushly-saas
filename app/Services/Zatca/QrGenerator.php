<?php

namespace App\Services\Zatca;

use Illuminate\Support\Facades\Storage;
use Milon\Barcode\DNS2D;

final class QrGenerator
{
    /** Returns the base64 payload as an SVG string (inline-renderable). */
    public function svg(string $payload, int $size = 4): string
    {
        $generator = new DNS2D();
        $generator->setStorPath(storage_path('framework/barcodes/'));
        return $generator->getBarcodeSVG($payload, 'QRCODE', $size, $size, 'black');
    }

    /** Write a PNG file under storage disk `public` and return the relative path. */
    public function writePng(string $payload, string $relativePath, int $size = 4): string
    {
        $generator = new DNS2D();
        $generator->setStorPath(storage_path('framework/barcodes/'));
        $png = $generator->getBarcodePNG($payload, 'QRCODE', $size, $size, [0, 0, 0]);

        Storage::disk('public')->put($relativePath, base64_decode($png));

        return $relativePath;
    }

    /** Inline data-URI suitable for direct <img src="..."> embedding (PDF or HTML). */
    public function dataUri(string $payload, int $size = 4): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->svg($payload, $size));
    }
}
