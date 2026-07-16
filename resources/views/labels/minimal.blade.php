<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html, body { width:100%; height:100%; margin:0; padding:0; font-family:'Georgia',serif; color:#111; }
    .wrap { width:100%; height:100%; box-sizing:border-box; padding:14px; }
    .head { font-size:8pt; letter-spacing:.28em; text-transform:uppercase; color:#666; }
    .awb  { font-size:22pt; font-weight:400; letter-spacing:.06em; margin-top:4px; }
    .bar  { text-align:center; margin:14px 0; }
    .bar img { width:100%; height:60px; }
    .lbl  { font-size:7pt; letter-spacing:.24em; text-transform:uppercase; color:#666; margin-top:14px; }
    .val  { font-size:12pt; font-weight:700; margin-top:2px; }
    .sub  { font-size:9.5pt; margin-top:2px; }
    .cod  { margin-top:18px; padding-top:12px; border-top:1px solid #111; text-align:center; font-size:18pt; font-weight:700; letter-spacing:.05em; }
    .cc   { margin-top:18px; padding-top:12px; border-top:1px solid #111; text-align:center; font-size:12pt; font-weight:400; letter-spacing:.24em; text-transform:uppercase; color:#666; }
    .foot { margin-top:12px; font-size:8pt; color:#666; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head">Air Waybill</div>
    <div class="awb">{{ $data['awb'] }}</div>

    <div class="bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string) $data['awb'], 'C128') . '" alt="awb" />'; @endphp
    </div>

    <div class="lbl">Recipient</div>
    <div class="val">{{ $data['receiver']['name'] }}</div>
    <div class="sub">{{ $data['receiver']['addressLine1'] }}</div>
    <div class="sub">{{ $data['receiver']['city'] }}, {{ $data['receiver']['country'] }}</div>
    <div class="sub">{{ $data['receiver']['phone'] }}</div>

    <div class="lbl">Sender</div>
    <div class="sub">{{ $data['sender']['name'] }} · {{ $data['sender']['phone'] }}</div>

    <div class="foot">
        Order {{ $data['orderNumber'] }} · Ref {{ $data['reference_number'] }} · {{ $data['date'] }}
    </div>

    @if ($data['isCod'])
        <div class="cod">COD {{ number_format((float) $data['codAmount'], 2) }}</div>
    @else
        <div class="cc">Pre-paid</div>
    @endif
</div>
</body>
</html>
