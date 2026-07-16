<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
@php
    // Tenant brand colors — used ONLY for accent surfaces (header bar, COD
    // block, divider). Body text is always a fixed dark color because some
    // tenants set text_color to #fff for their light portal theme, and
    // that would make the label body invisible on the printed white paper.
    $isHex = fn ($v) => is_string($v) && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $v);
    try {
        $primary   = $isHex(settings()->primary_color) ? settings()->primary_color : '#2563eb';
        $secondary = $isHex(settings()->accent_color)  ? settings()->accent_color  : '#7c3aed';
    } catch (\Throwable $e) {
        $primary = '#2563eb'; $secondary = '#7c3aed';
    }
    $textDark = '#0f172a'; // fixed — never inherit tenant text_color for print
@endphp
<style>
    html, body { width:100%; height:100%; margin:0; padding:0; font-family:'Helvetica','Arial',sans-serif; color:{{ $textDark }}; }
    .wrap { width:100%; height:100%; box-sizing:border-box; padding:4px; }
    .head { background:{{ $primary }}; color:#fff; padding:8px 10px; border-radius:4px 4px 0 0; }
    .head .brand { font-size:9pt; font-weight:800; letter-spacing:.06em; }
    .head .awb { font-size:14pt; font-weight:900; }
    .bar { text-align:center; padding:6px 0; background:#fff; }
    .bar img { width:94%; height:46px; }
    .row { padding:6px 10px; border-bottom:2px solid {{ $secondary }}22; }
    .lbl { font-size:7pt; text-transform:uppercase; letter-spacing:.1em; color:{{ $secondary }}; font-weight:800; }
    .val { font-size:10pt; font-weight:800; margin-top:1px; }
    .sub { font-size:9pt; }
    .cod { background:{{ $secondary }}; color:#fff; padding:10px; text-align:center; font-weight:900; font-size:14pt; border-radius:0 0 4px 4px; }
    .cc  { background:#f1f5f9; color:{{ $textDark }}; padding:10px; text-align:center; font-weight:800; font-size:12pt; border-radius:0 0 4px 4px; }
    /* Plain block layout — mPDF's `display:table-cell` was silently
       dropping the inner text nodes for this template. */
    .meta { width:100%; }
    .meta .cell { display:inline-block; width:32%; font-size:8pt; vertical-align:top; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div class="brand">{{ strtoupper($data['sender']['name'] ?: 'SHIPMENT') }}</div>
        <div class="awb">{{ $data['awb'] }}</div>
    </div>

    <div class="bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string) $data['awb'], 'C128') . '" alt="awb" />'; @endphp
    </div>

    <div class="row">
        <div class="lbl">Ship to</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div class="sub">{{ $data['receiver']['addressLine1'] }}</div>
        <div class="sub">{{ $data['receiver']['city'] }} · {{ $data['receiver']['country'] }}</div>
        <div class="sub">{{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">From</div>
        <div class="sub"><strong>{{ $data['sender']['name'] }}</strong> · {{ $data['sender']['phone'] }}</div>
    </div>

    <div class="row">
        <div class="meta">
            <span class="cell"><span class="lbl">Order</span><br>{{ $data['orderNumber'] }}</span>
            <span class="cell"><span class="lbl">Ref</span><br>{{ $data['reference_number'] }}</span>
            <span class="cell"><span class="lbl">Date</span><br>{{ $data['date'] }}</span>
        </div>
    </div>

    @if ($data['isCod'])
        <div class="cod">COD · {{ number_format((float) $data['codAmount'], 2) }}</div>
    @else
        <div class="cc">PRE-PAID</div>
    @endif
</div>
</body>
</html>
