{{--
    High-density layout — multi-panel air-waybill grid.

    Structure mirrors the classic international AWB: brand + horizontal barcode
    header, an origin/destination/date panel, a service-code strip
    (scope / product / pieces), a weights-and-charges row, then shipper and
    consignee panels with a COD box and a vertical barcode down the right edge,
    closing on a references footer.

    mPDF notes:
      - Everything is nested <table>s; mPDF has no flex/grid.
      - The right-hand rail (COD + rotated barcode) is a rowspan cell rather
        than absolute positioning, which mPDF places unreliably.
      - The vertical barcode is produced by DNS1D with the rotate transform
        applied via mPDF's supported `text-rotate`-free approach: the PNG is
        emitted at barcode height and rotated with a CSS transform that mPDF
        honours on block images.
      - Every key uses ?? so parcels predating the waybill fields still render.

    Carrier wordmark is an asset slot (public/assets/labels/carrier-logo.png),
    not markup — see the note in bold-barcode.blade.php.
--}}
@php
    $awb       = (string) ($data['awb'] ?? '');
    $tracking  = (string) ($data['trackingId'] ?? $awb);
    $barValue  = preg_replace('/\s+/', '', $tracking) ?: $awb;
    $currency  = $data['currency'] ?? 'SAR';
    $cod       = (float) ($data['codAmount'] ?? 0);
    $isCod     = ! empty($data['isCod']) && $cod > 0;
    $origin    = strtoupper((string) ($data['originCode'] ?? ''));
    $dest      = strtoupper((string) ($data['destinationCode'] ?? ($data['receiver']['city_code'] ?? '')));
    $weight    = (float) ($data['weight'] ?? 0);
    // Tenant logo, inlined as a data URI (mPDF prints with no network access).
    // Falls back to the tenant name as text when no logo is uploaded.
    // Scaled to fill the header cell without distorting — see bold-barcode.
    $logo      = labelLogoBox(128, 52);
    $brandName = optional(settings())->name ?: config('app.name');
    $wfmt      = fn ($w) => rtrim(rtrim(number_format($w, 2), '0'), '.');

    // Vertical barcode for the right rail. mPDF's CSS `transform: rotate()`
    // does not reflow the box, so a rotated <img> spills past the cell border
    // and gets clipped. Rotating the PNG itself with GD sidesteps the transform
    // entirely and yields a genuinely vertical image. Falls back to the
    // horizontal barcode if GD has no imagerotate.
    // Generated thin (widthFactor 1) so that after rotation the module axis is
    // its NATURAL pixel length — scaling that axis to a fixed height would
    // squeeze ~330px of modules into ~150px and destroy scannability. The image
    // is emitted at its real rotated dimensions instead.
    $vbar = null; $vbarW = 0; $vbarH = 0;
    $rawBar = base64_decode(DNS1D::getBarcodePNG($barValue, 'C128', 1, 24));
    if ($rawBar && function_exists('imagerotate') && ($src = @imagecreatefromstring($rawBar))) {
        $rot   = imagerotate($src, 90, 0);
        $vbarW = imagesx($rot);
        $vbarH = imagesy($rot);
        ob_start();
        imagepng($rot);
        $vbar = base64_encode((string) ob_get_clean());
        imagedestroy($src);
        imagedestroy($rot);
    }
@endphp
<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $awb }}</title>
<style>
    html,body   { width:100%;margin:0;padding:0;font-family:sans-serif;color:#000;font-size:8.5pt; }
    .sheet      { width:100%;box-sizing:border-box;border:2px solid #000; }
    table       { width:100%;border-collapse:collapse; }
    td          { vertical-align:top;padding:2px 4px; }
    .bx         { border:1px solid #000; }
    .bt         { border-top:1px solid #000; }
    .bb         { border-bottom:1px solid #000; }
    .bl         { border-left:1px solid #000; }
    .br         { border-right:1px solid #000; }
    .cap        { font-size:7pt; }
    .big        { font-size:16pt;font-weight:bold;line-height:1.1;white-space:nowrap; }
    .code       { font-size:13pt;font-weight:bold;text-align:center;line-height:1.25;white-space:nowrap;padding:2px 1px; }
    .brand      { font-size:12.5pt;font-weight:bold;color:#e2231a;letter-spacing:-.03em;
                  white-space:nowrap;overflow:hidden;line-height:1.15; }
    .barh       { text-align:center;padding:2px 4px 0; }
    .barh img   { width:92%;height:44px; }
    .barnum     { text-align:center;font-size:11.5pt;padding:0 0 2px; }
    .codttl     { text-align:center;font-size:10pt;padding:3px 0 1px; }
    .codval     { text-align:center;font-size:12.5pt;font-weight:bold;background:#d9d9d9;padding:3px 0; }
    .vbar       { text-align:center;padding:6px 0; }
    .vbarflat img { width:92%;height:22px; }
    .strong     { font-weight:bold; }
    .ftr td     { font-size:8.5pt;padding:3px 4px; }
</style>
</head>
<body>
<div class="sheet">

    {{-- ---------------- header: brand + barcode ---------------- --}}
    <table>
        <tr>
            <td class="br" style="width:32%;vertical-align:middle;">
                @if ($logo)
                    <img src="{{ $logo['uri'] }}" alt=""
                         style="width:{{ $logo['w'] }}px;height:{{ $logo['h'] }}px;">
                @else
                    <div class="brand">{{ \Illuminate\Support\Str::limit($brandName, 20, '') }}</div>
                @endif
            </td>
            <td>
                <div class="barh">
                    @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($barValue, 'C128', 3, 70) . '" alt="" />'; @endphp
                </div>
                <div class="barnum">{{ $tracking }}</div>
            </td>
        </tr>
    </table>

    {{-- ---------------- origin / destination / refs ---------------- --}}
    <table class="bt">
        <tr>
            <td class="br" style="width:21%;">
                <div class="cap">Origin:</div>
                <div class="big">{{ $origin ?: '—' }}</div>
            </td>
            <td class="br" style="width:56%;">
                <div class="cap">Date: &nbsp; <span class="strong">{{ $data['date'] ?? '' }}</span></div>
                <div class="cap" style="padding-top:3px;">Foreign Ref:</div>
                <div class="cap" style="padding-top:3px;">Ref1: {{ $data['reference_number'] ?: ($data['orderNumber'] ?? '') }}</div>
            </td>
            <td rowspan="2"></td>
        </tr>
        <tr>
            <td class="br bt">
                <div class="cap">Destination:</div>
                <div class="big">{{ $dest ?: '—' }}</div>
            </td>
            <td class="br bt" style="padding:0;">
                <table>
                    <tr>
                        <td class="code br" style="width:30%;">DOM</td>
                        <td class="code br" style="width:28%;">CDS</td>
                        <td class="code br" style="width:14%;">P</td>
                        <td class="code br" style="width:14%;">{{ $data['pieces'] ?? 1 }}</td>
                        <td class="code">{{ $data['currentPage'] ?? 1 }} / {{ $data['totalPages'] ?? 1 }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ---------------- weights ---------------- --}}
    <table class="bt">
        <tr>
            <td style="width:38%;">Weight: {{ $wfmt($weight) }} KG</td>
            <td>Chargeable: {{ $wfmt($weight) }} KG</td>
        </tr>
        <tr>
            <td class="bt">Services:</td>
            <td class="bt">Customs: {{ number_format((float) ($data['declaredValue'] ?? 0), 0) }} {{ $currency }}</td>
        </tr>
    </table>

    {{-- ---------------- shipper / consignee + right rail ---------------- --}}
    <table class="bt">
        <tr>
            {{-- left: parties --}}
            <td class="br" style="width:74%;padding:0;">
                <table>
                    <tr><td class="bb">
                        <span class="cap">Account:</span> <span class="strong">{{ $data['orderNumber'] ?? '' }}</span><br>
                        <span class="strong">{{ $data['sender']['name'] ?? '' }}</span><br>
                        {{ $data['sender']['addressLine1'] ?? '' }}<br>
                        @if (!empty($data['sender']['addressLine2'])){{ $data['sender']['addressLine2'] }}<br>@endif
                        {{ $data['sender']['city'] ?? '' }}<br>
                        {{ $data['sender']['country'] ?? '' }} &nbsp;&nbsp;&nbsp; {{ $data['sender']['phone'] ?? '' }}
                    </td></tr>
                    <tr><td style="height:210px;">
                        <span class="strong">{{ $data['receiver']['name'] ?? '' }}</span><br><br>
                        {{ $data['receiver']['addressLine1'] ?? '' }}<br>
                        @if (!empty($data['receiver']['state'])){{ $data['receiver']['state'] }}<br>@endif
                        <br>
                        {{ $data['receiver']['city'] ?? '' }}<br>
                        {{ $data['receiver']['country'] ?? '' }}<br>
                        {{ $data['receiver']['phone'] ?? '' }}<br>
                        Short Address Code: {{ $data['shortAddressCode'] ?? '-' }} &nbsp;&nbsp; Route: N/A<br>
                        Remarks: {{ $data['reference_number'] ?: '' }}
                    </td></tr>
                </table>
            </td>

            {{-- right rail: COD + vertical barcode --}}
            <td style="padding:0;">
                <div class="codttl">COD</div>
                <div class="codval">{{ $isCod ? number_format($cod, 0) : '0' }} {{ $currency }}</div>
                @if ($vbar)
                    <div class="vbar">
                        <img src="data:image/png;base64,{{ $vbar }}"
                             style="width:{{ $vbarW }}px;height:{{ $vbarH }}px;" alt="">
                    </div>
                @else
                    <div class="vbarflat" style="text-align:center;padding:8px 2px;">
                        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($barValue, 'C128', 2, 60) . '" alt="" />'; @endphp
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ---------------- references footer ---------------- --}}
    <table class="bt ftr">
        <tr>
            <td class="br" style="width:52%;">Description: {{ \Illuminate\Support\Str::limit($data['description'] ?? '', 40) }}</td>
            <td>Consignee Ref: {{ $data['reference_number'] ?: '' }}</td>
        </tr>
        <tr>
            <td class="br bt">Shipper Ref: {{ $data['reference_number'] ?: '' }}</td>
            <td class="bt">Consignee Ref2:</td>
        </tr>
    </table>
</div>
</body>
</html>
