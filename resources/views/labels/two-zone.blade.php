<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html,body { width:100%;height:100%;margin:0;padding:0;font-family:sans-serif;font-size:9.5pt;color:#111; }
    .wrap { width:100%;height:100%;box-sizing:border-box;padding:5px;border:1px solid #111; }
    .head { background:#0a2540;color:#fff;padding:6px;text-align:center;font-weight:bold;font-size:11pt;letter-spacing:.05em; }
    .zone { padding:5px;border-bottom:1px dashed #333; }
    .zone:last-child { border-bottom:none; }
    .lbl { font-size:7.5pt;text-transform:uppercase;color:#555;letter-spacing:.04em; }
    .val { font-size:10.5pt;font-weight:bold; }
    .bar { text-align:center;margin:3px 0; }
    .bar img { width:88%;height:46px; }
    .cod { background:#0a2540;color:#fff;text-align:center;padding:7px;font-weight:bold;font-size:14pt; }
    .grid { width:100%; }
    .grid td { vertical-align:top;padding:2px 4px;font-size:9pt; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head">EXPRESS · {{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div>

    <div class="zone bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string)$data['awb'], 'C128') . '" alt="awb" />'; @endphp
        <div class="lbl">AWB {{ $data['awb'] }}</div>
    </div>

    <div class="zone">
        <div class="lbl">Deliver to / إلى</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div>{{ $data['receiver']['addressLine1'] }}</div>
        <div>{{ $data['receiver']['city'] }} · {{ $data['receiver']['state'] }}</div>
        <div>{{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="zone">
        <div class="lbl">Pickup / من</div>
        <div>{{ $data['sender']['name'] }}</div>
        <div>{{ $data['sender']['phone'] }}</div>
    </div>

    <div class="zone">
        <table class="grid">
            <tr>
                <td><div class="lbl">Reference</div><div>{{ $data['reference_number'] }}</div></td>
                <td><div class="lbl">Order</div><div>{{ $data['orderNumber'] }}</div></td>
            </tr>
            <tr>
                <td colspan="2"><div class="lbl">Date</div><div>{{ $data['date'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="zone" style="padding:0;">
        @if ($data['isCod'])
            <div class="cod">COD {{ number_format((float) $data['codAmount'], 2) }}</div>
        @else
            <div class="head" style="background:#1e7d57;">PRE-PAID / CC</div>
        @endif
    </div>
</div>
</body>
</html>
