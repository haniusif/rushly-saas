<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html,body { width:100%;height:100%;margin:0;padding:0;font-family:sans-serif;font-size:9pt;color:#111; }
    .wrap { width:100%;height:100%;box-sizing:border-box;padding:4px;border:2px solid #036c4a; }
    .top { background:#036c4a;color:#fff;text-align:center;padding:5px;font-weight:bold;font-size:11pt; }
    .row { border-bottom:1px solid #036c4a;padding:5px 6px; }
    .row:last-child { border-bottom:none; }
    .lbl { font-size:7.5pt;text-transform:uppercase;color:#036c4a; }
    .val { font-size:10.5pt;font-weight:bold; }
    .bar { text-align:center;margin:2px 0; }
    .bar img { width:90%;height:54px; }
    .cod { background:#facc15;color:#000;text-align:center;padding:9px;font-weight:bold;font-size:17pt;letter-spacing:.08em; }
    .grid { width:100%; }
    .grid td { vertical-align:top;padding:2px 4px;font-size:9pt; }
</style>
</head>
<body>
<div class="wrap">
    <div class="top">AWB · {{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div>

    <div class="row bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string)$data['awb'], 'C128') . '" alt="awb" />'; @endphp
        <div class="lbl">AWB · {{ $data['awb'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">Receiver / المستلم</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div>{{ $data['receiver']['addressLine1'] }}</div>
        <div>{{ $data['receiver']['city'] }} ({{ $data['receiver']['city_code'] }}) · {{ $data['receiver']['country'] }}</div>
        <div>{{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">Sender / المرسل</div>
        <div>{{ $data['sender']['name'] }} · {{ $data['sender']['phone'] }}</div>
    </div>

    <div class="row">
        <table class="grid">
            <tr>
                <td><div class="lbl">Order</div><div>{{ $data['orderNumber'] }}</div></td>
                <td><div class="lbl">Ref</div><div>{{ $data['reference_number'] }}</div></td>
                <td><div class="lbl">Date</div><div>{{ $data['date'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="row" style="padding:0;">
        @if ($data['isCod'])
            <div class="cod">COD {{ number_format((float) $data['codAmount'], 2) }} SAR</div>
        @else
            <div class="top">PRE-PAID</div>
        @endif
    </div>
</div>
</body>
</html>
