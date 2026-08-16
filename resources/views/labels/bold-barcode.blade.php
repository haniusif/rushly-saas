{{--
    Bold-barcode layout — express-courier waybill.

    Structure mirrors the standard 100×150mm express AWB: brand strip with
    service marker / 2D code / COD box, a sender|shipment-facts split, the
    ship-to block, then a jumbo destination-station code above a full-width
    Code128 and the payment terms footer.

    mPDF notes:
      - No flexbox/grid support. Every column is a <table>; widths are % and
        cells carry explicit vertical-align.
      - Barcodes are inline base64 PNGs (DNS1D/DNS2D); an <img> with a data URI
        is the only reliable image path here.
      - All data keys are read with ?? so the layout still renders for parcels
        created before the waybill fields were added to the builder.

    The carrier wordmark is intentionally an asset slot, not drawn in markup:
    drop the courier's own logo at public/assets/labels/carrier-logo.png and it
    is picked up; otherwise the tenant name is printed as text.
--}}
@php
    $awb        = (string) ($data['awb'] ?? '');
    $tracking   = (string) ($data['trackingId'] ?? $awb);
    $barValue   = preg_replace('/\s+/', '', $tracking) ?: $awb;
    $currency   = $data['currency'] ?? 'SAR';
    $cod        = (float) ($data['codAmount'] ?? 0);
    $isCod      = ! empty($data['isCod']) && $cod > 0;
    $dest       = strtoupper((string) ($data['destinationCode'] ?? ($data['receiver']['city_code'] ?? '')));
    // The destination band needs a 2-letter country CODE, not the display name
    // — "Saudi Arabia" next to a 44pt station code overflows the label and
    // pushes the barcode onto a second page. Data carries the display name, so
    // fold the common GCC names down and fall back to the first two letters.
    $ccMap      = ['saudi arabia'=>'SA','ksa'=>'SA','united arab emirates'=>'AE','uae'=>'AE',
                   'kuwait'=>'KW','bahrain'=>'BH','qatar'=>'QA','oman'=>'OM','egypt'=>'EG','jordan'=>'JO'];
    $rawCountry = trim((string) ($data['receiver']['country'] ?? ''));
    $country    = $data['receiver']['country_code']
                  ?? ($ccMap[strtolower($rawCountry)] ?? strtoupper(substr($rawCountry, 0, 2)));
    // Tenant logo, inlined as a data URI (mPDF prints with no network access).
    // Falls back to the tenant name as text when no logo is uploaded.
    // Scaled to fill the header cell without distorting: a square tenant logo
    // grows to the band height, a wide wordmark to the cell width.
    $logo       = labelLogoBox(150, 56);
    $brandName  = optional(settings())->name ?: config('app.name');
@endphp
<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $awb }}</title>
<style>
    html,body { width:100%;margin:0;padding:0;font-family:sans-serif;color:#000; }
    .sheet    { width:100%;box-sizing:border-box;border:2px solid #000;padding:0; }
    table     { width:100%;border-collapse:collapse; }
    td        { vertical-align:top; }

    /* ---- header strip ---- */
    .hd td        { padding:3px 5px;vertical-align:middle; }
    .brand        { font-size:15pt;font-weight:bold;letter-spacing:-.04em;line-height:1.05;
                    white-space:nowrap;overflow:hidden; }
    .brandsub     { font-size:8pt;font-weight:normal;letter-spacing:.28em;line-height:1.1; }
    .svc          { background:#000;color:#fff;font-size:26pt;font-weight:bold;
                    text-align:center;width:52px;line-height:1.15; }
    .codbox       { border:2px solid #000;text-align:center;width:96px; }
    .codbox .t    { font-size:9.5pt;font-weight:bold;border-bottom:1px solid #000;padding:1px 0; }
    .codbox .v    { font-size:14pt;font-weight:bold;background:#d9d9d9;padding:2px 0; }

    .rule         { border-top:2px solid #000;font-size:0;line-height:0; }
    .thin         { border-top:1px solid #000;font-size:0;line-height:0; }

    /* ---- address / facts ---- */
    .addr td      { padding:2px 6px;font-size:8.5pt;line-height:1.32; }
    .facts        { border-left:2px solid #000; }
    .shipto       { font-size:12pt;font-weight:bold; }
    .shipto .lbl  { font-weight:normal;font-size:10.5pt; }
    .care         { text-align:center;font-size:8.5pt;padding:2px 0; }

    /* ---- destination band ---- */
    .dest         { font-size:33pt;font-weight:bold;line-height:1.02;letter-spacing:-.02em;white-space:nowrap; }
    .dest .cc     { font-size:14pt;padding-right:4px; }
    .prod         { text-align:right;font-size:21pt;font-weight:bold;line-height:1;white-space:nowrap; }
    .prodsub      { text-align:right;font-size:8pt;font-weight:bold;white-space:nowrap; }

    .master       { font-size:11pt;font-weight:bold;padding:1px 6px; }
    .of           { text-align:right;font-size:11pt;font-weight:bold;padding:1px 6px; }

    .barwrap      { text-align:center;padding:2px 6px 0; }
    .barwrap img  { width:82%;height:46px; }
    .barnum       { text-align:center;font-size:16pt;font-weight:bold;letter-spacing:.05em;padding:0 0 2px; }

    .terms td     { font-size:8.5pt;padding:2px 6px; }
</style>
</head>
<body>
<div class="sheet">

    {{-- ---------------- header ---------------- --}}
    <table class="hd">
        <tr>
            <td style="width:36%;">
                @if ($logo)
                    <img src="{{ $logo['uri'] }}" alt=""
                         style="width:{{ $logo['w'] }}px;height:{{ $logo['h'] }}px;">
                @else
                    <div class="brand">{{ \Illuminate\Support\Str::limit($brandName, 16, '') }}</div>
                    <div class="brandsub">EXPRESS</div>
                @endif
            </td>
            <td class="svc">R</td>
            <td style="text-align:center;width:22%;">
                @php echo '<img src="data:image/png;base64,' . DNS2D::getBarcodePNG($barValue, 'QRCODE') . '" style="width:54px;height:54px;" alt="" />'; @endphp
            </td>
            <td style="text-align:right;">
                <table class="codbox">
                    <tr><td class="t">COD/{{ $currency }}</td></tr>
                    <tr><td class="v">{{ $isCod ? number_format($cod, 0) : '0' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ---------------- sender | shipment facts ---------------- --}}
    <table class="addr">
        <tr>
            <td style="width:62%;">
                {{ $data['sender']['phone'] ?? '' }}<br>
                {{ $data['sender']['name'] ?? '' }}<br>
                {{ $data['sender']['addressLine1'] ?? '' }}<br>
                @if (!empty($data['sender']['addressLine2'])){{ $data['sender']['addressLine2'] }}<br>@endif
                {{ $data['sender']['city'] ?? '' }}<br>
                {{ $data['sender']['country'] ?? '' }}
            </td>
            <td class="facts">
                Ship Date: {{ $data['date'] ?? '' }}<br>
                DV:{{ $currency }} {{ number_format((float) ($data['declaredValue'] ?? 0), 0) }}<br>
                WGT:{{ rtrim(rtrim(number_format((float) ($data['weight'] ?? 0), 2), '0'), '.') }} KG<br>
                PCs: {{ $data['pieces'] ?? 1 }}<br>
                <strong>{{ $data['reference_number'] ?: ($data['orderNumber'] ?? '') }}</strong>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ---------------- ship to ---------------- --}}
    <table class="addr">
        <tr><td class="shipto">
            <span class="lbl">Ship to:</span> &nbsp; {{ $data['receiver']['phone'] ?? '' }}
        </td></tr>
        <tr><td>
            {{ $data['receiver']['name'] ?? '' }}<br>
            {{ $data['receiver']['addressLine1'] ?? '' }}@if(!empty($data['receiver']['state'])), {{ $data['receiver']['state'] }}@endif<br>
            {{ $data['receiver']['city'] ?? '' }}<br>
            {{ $data['receiver']['country'] ?? '' }}
        </td></tr>
    </table>

    <div class="thin"></div>
    <div class="care">Handle with care</div>
    <div class="thin"></div>

    {{-- ---------------- destination band ---------------- --}}
    <table>
        <tr>
            <td style="padding:2px 6px;">
                <span class="dest"><span class="cc">{{ $country }}</span>&nbsp;{{ $dest }}</span>
            </td>
            <td style="padding:2px 6px;">
                <div class="prod">EDDL</div>
                <div class="prodsub">ECOM Delivery Lite</div>
            </td>
        </tr>
    </table>

    <div class="thin"></div>

    <table>
        <tr>
            <td class="master">MASTER#: {{ $tracking }}</td>
            <td class="of">{{ $data['currentPage'] ?? 1 }} of {{ $data['totalPages'] ?? 1 }}</td>
        </tr>
    </table>

    {{-- ---------------- barcode ---------------- --}}
    <div class="barwrap">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($barValue, 'C128', 3, 90) . '" alt="" />'; @endphp
    </div>
    <div class="barnum">{{ $tracking }}</div>

    <div class="rule"></div>

    {{-- ---------------- payment terms ---------------- --}}
    <table class="terms">
        <tr>
            <td>Payment Duty: Bill Consignee</td>
            <td style="text-align:right;">Payment Vat: Bill Consignee</td>
        </tr>
    </table>
</div>
</body>
</html>
