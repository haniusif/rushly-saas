<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html, body { width:100%; height:100%; margin:0; padding:0; font-family:'Helvetica','Arial',sans-serif; color:#0a0a0a; }
    .wrap { width:100%; height:100%; box-sizing:border-box; padding:6px; }
    .head { background:#111; color:#fff; padding:10px 12px; }
    .head .brand { font-size:8pt; text-transform:uppercase; letter-spacing:.15em; opacity:.7; }
    .head .awb { font-size:18pt; font-weight:900; letter-spacing:.02em; margin-top:2px; }
    .bar { text-align:center; padding:8px 0 4px 0; }
    .bar img { width:96%; height:56px; }
    .zone { padding:6px 10px; border-top:2px solid #111; }
    .lbl { font-size:7pt; text-transform:uppercase; letter-spacing:.12em; color:#666; }
    .val { font-size:11pt; font-weight:800; margin-top:1px; }
    .sub { font-size:9pt; color:#111; }
    .cod { background:#fbbf24; color:#111; padding:12px 10px; text-align:center; font-weight:900; font-size:16pt; border-top:2px solid #111; }
    .cc  { background:#e5e5e5; color:#0a0a0a; padding:12px 10px; text-align:center; font-weight:800; font-size:12pt; border-top:2px solid #111; }
    .meta { display:table; width:100%; margin-top:2px; }
    .meta div { display:table-cell; font-size:8pt; color:#333; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div class="brand">{{ $data['sender']['name'] ?: 'SHIPMENT' }}</div>
        <div class="awb">{{ $data['awb'] }}</div>
    </div>

    <div class="bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string) $data['awb'], 'C128') . '" alt="awb" />'; @endphp
    </div>

    <div class="zone">
        <div class="lbl">Ship to</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div class="sub">{{ $data['receiver']['addressLine1'] }}</div>
        <div class="sub">{{ $data['receiver']['city'] }} · {{ $data['receiver']['country'] }}</div>
        <div class="sub" style="margin-top:2px;">📞 {{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="zone">
        <div class="lbl">From</div>
        <div class="sub"><strong>{{ $data['sender']['name'] }}</strong> · {{ $data['sender']['phone'] }}</div>
        <div class="sub">{{ $data['sender']['addressLine1'] }}</div>
    </div>

    <div class="zone">
        <div class="meta">
            <div><strong>Order</strong> {{ $data['orderNumber'] }}</div>
            <div><strong>Ref</strong> {{ $data['reference_number'] }}</div>
            <div><strong>Date</strong> {{ $data['date'] }}</div>
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
