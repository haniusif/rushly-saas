<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html, body { width:100%; height:100%; margin:0; padding:0; font-family:'Arial',sans-serif; font-size:8.5pt; color:#000; }
    .wrap { width:100%; height:100%; box-sizing:border-box; padding:3px; }
    .row { padding:2px 4px; border-bottom:1px dashed #000; }
    .row:last-child { border-bottom:none; }
    .lbl { font-size:6.5pt; text-transform:uppercase; letter-spacing:.04em; color:#333; }
    .val { font-size:9pt; font-weight:bold; }
    .bar { text-align:center; padding:2px 0; }
    .bar img { width:98%; height:36px; }
    .cod { background:#000; color:#fff; text-align:center; padding:4px; font-weight:bold; font-size:12pt; }
    .grid { width:100%; }
    .grid td { vertical-align:top; padding:1px 3px; font-size:8pt; }
</style>
</head>
<body>
<div class="wrap">
    <div class="row" style="padding:2px 4px;text-align:center;">
        <strong>{{ $data['awb'] }}</strong>
        <div class="lbl">{{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div>
    </div>
    <div class="row bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string) $data['awb'], 'C128') . '" alt="awb" />'; @endphp
    </div>
    <div class="row">
        <div class="lbl">TO</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div>{{ $data['receiver']['addressLine1'] }}, {{ $data['receiver']['city'] }}</div>
        <div>{{ $data['receiver']['phone'] }}</div>
    </div>
    <div class="row">
        <div class="lbl">FROM</div>
        <div>{{ $data['sender']['name'] }} · {{ $data['sender']['phone'] }}</div>
    </div>
    <div class="row">
        <table class="grid">
            <tr>
                <td><div class="lbl">ORD</div><div>{{ $data['orderNumber'] }}</div></td>
                <td><div class="lbl">REF</div><div>{{ $data['reference_number'] }}</div></td>
            </tr>
        </table>
    </div>
    @if ($data['isCod'])
        <div class="cod">COD {{ number_format((float) $data['codAmount'], 2) }}</div>
    @endif
</div>
</body>
</html>
