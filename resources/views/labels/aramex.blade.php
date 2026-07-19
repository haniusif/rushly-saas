<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html,body { width:100%;height:100%;margin:0;padding:0;font-family:sans-serif;font-size:9pt;color:#000; }
    .wrap { width:100%;height:100%;box-sizing:border-box;padding:3px;border:2px solid #000; }
    .strip { background:#c8102e;color:#fff;padding:5px 6px;font-weight:bold;font-size:10pt;letter-spacing:.04em; }
    .row { border-bottom:1px solid #000;padding:4px 6px; }
    .row:last-child { border-bottom:none; }
    .lbl { font-size:7pt;text-transform:uppercase;color:#222; }
    .val { font-size:11pt;font-weight:bold; }
    .bar { text-align:center;margin:2px 0; }
    .bar img { width:90%;height:48px; }
    .cod { background:#000;color:#fff;text-align:center;padding:8px;font-weight:bold;font-size:15pt;letter-spacing:.06em; }
    .grid { width:100%; }
    .grid td { vertical-align:top;padding:2px 4px;font-size:8.5pt;width:33%; }
</style>
</head>
<body>
<div class="wrap">
    <div class="strip">STANDARD AWB · {{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div>

    <div class="row bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string)$data['awb'], 'C128') . '" alt="awb" />'; @endphp
        <div class="lbl">AWB · {{ $data['awb'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">Consignee</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div>{{ $data['receiver']['addressLine1'] }}</div>
        <div>{{ $data['receiver']['city'] }} ({{ $data['receiver']['city_code'] }}) · {{ $data['receiver']['country'] }}</div>
        <div>{{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">Shipper</div>
        <div>{{ $data['sender']['name'] }} · {{ $data['sender']['phone'] }}</div>
        <div>{{ $data['sender']['addressLine1'] }}</div>
    </div>

    <div class="row">
        <table class="grid">
            <tr>
                <td><div class="lbl">Reference</div><div>{{ $data['reference_number'] }}</div></td>
                <td><div class="lbl">Order</div><div>{{ $data['orderNumber'] }}</div></td>
                <td><div class="lbl">Date</div><div>{{ $data['date'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="row" style="padding:0;">
        @if ($data['isCod'])
            <div class="cod">COD · {{ number_format((float) $data['codAmount'], 2) }}</div>
        @else
            <div class="strip" style="text-align:center;">PRE-PAID</div>
        @endif
    </div>
</div>
</body>
</html>
