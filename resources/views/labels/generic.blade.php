<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html,body { width:100%;height:100%;margin:0;padding:0;font-family:sans-serif;font-size:9.5pt;color:#111; }
    .wrap { width:100%;height:100%;box-sizing:border-box;padding:4px;border:1px solid #111; }
    .row { border-bottom:1px solid #111;padding:4px 5px; }
    .row:last-child { border-bottom:none; }
    .lbl { font-size:7.5pt;text-transform:uppercase;color:#555;letter-spacing:.05em; }
    .val { font-size:10pt;font-weight:bold; }
    .bar { text-align:center;margin:2px 0; }
    .bar img { width:88%;height:42px; }
    .cod { background:#000;color:#fff;text-align:center;padding:6px;font-weight:bold;font-size:14pt; }
    .cc  { background:#eee;color:#111;text-align:center;padding:6px;font-weight:bold;font-size:12pt; }
    .grid { width:100%; }
    .grid td { vertical-align:top;padding:2px 4px;font-size:9pt; }
</style>
</head>
<body>
@php
    // Tenant logo → base64 data URI so mPDF renders it without needing
    // network access at print time. Falls back to text-only header when
    // the tenant hasn't uploaded a logo yet.
    $__logoDataUri = null;
    // Try tenant logo first; fall back to the platform default under
    // public/images/default/logo.png. Fully silent on any error.
    $__logoCandidates = [];
    try {
        $tenantPath = optional(optional(settings())->rxlogo)->original;
        if (is_string($tenantPath) && $tenantPath !== '') $__logoCandidates[] = $tenantPath;
    } catch (\Throwable $e) {}
    $__logoCandidates[] = 'images/default/logo.png';

    foreach ($__logoCandidates as $__candidate) {
        $__abs = public_path($__candidate);
        if (is_file($__abs)) {
            $__mime = @mime_content_type($__abs) ?: 'image/png';
            $__logoDataUri = 'data:' . $__mime . ';base64,' . base64_encode(@file_get_contents($__abs));
            break;
        }
    }
@endphp
<div class="wrap">
    <div class="row" style="text-align:center;">
        @if ($__logoDataUri)
            <img src="{{ $__logoDataUri }}" alt="logo" style="max-height:24px;max-width:60%;display:inline-block;vertical-align:middle;margin-bottom:2px;" />
            <div style="font-size:9pt;color:#333;margin-top:1px;">{{ __('Shipping Label') }}</div>
        @else
            <strong style="font-size:11pt;">{{ __('Shipping Label') }}</strong>
        @endif
        <div class="lbl">AWB · {{ $data['awb'] }} · {{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div>
    </div>

    <div class="row bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string)$data['awb'], 'C128') . '" alt="awb" />'; @endphp
    </div>

    <div class="row">
        <div class="lbl">Ship to / المستلم</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div>{{ $data['receiver']['addressLine1'] }}</div>
        <div>{{ $data['receiver']['city'] }} {{ $data['receiver']['city_code'] }} · {{ $data['receiver']['country'] }}</div>
        <div>{{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">From / المرسل</div>
        <div>{{ $data['sender']['name'] }} · {{ $data['sender']['phone'] }}</div>
        <div>{{ $data['sender']['addressLine1'] }}</div>
    </div>

    <div class="row">
        <table class="grid">
            <tr>
                <td><div class="lbl">Order #</div><div>{{ $data['orderNumber'] }}</div></td>
                <td><div class="lbl">Ref</div><div>{{ $data['reference_number'] }}</div></td>
                <td><div class="lbl">Date</div><div>{{ $data['date'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="row" style="padding:0;">
        @if ($data['isCod'])
            <div class="cod">COD {{ number_format((float) $data['codAmount'], 2) }}</div>
        @else
            <div class="cc">CC · PRE-PAID</div>
        @endif
    </div>
</div>
</body>
</html>
