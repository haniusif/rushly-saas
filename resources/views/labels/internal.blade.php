<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html,body { width:100%;height:100%;margin:0;padding:0;font-family:sans-serif;font-size:9.5pt;color:#0f172a; }
    .wrap { width:100%;height:100%;box-sizing:border-box;padding:5px;border:1px solid #4f46e5;border-radius:4px; }
    .top { background:linear-gradient(90deg,#4f46e5,#7c3aed);color:#fff;text-align:center;padding:6px;font-weight:bold;font-size:11pt;letter-spacing:.05em;border-radius:3px; }
    .row { border-bottom:1px dashed #cbd5e1;padding:5px 4px; }
    .row:last-child { border-bottom:none; }
    .lbl { font-size:7.5pt;text-transform:uppercase;color:#64748b;letter-spacing:.04em; }
    .val { font-size:10.5pt;font-weight:bold;color:#0f172a; }
    .bar { text-align:center;margin:3px 0; }
    .bar img { width:90%;height:46px; }
    .cod { background:#0f172a;color:#fff;text-align:center;padding:8px;font-weight:bold;font-size:15pt;border-radius:3px; }
    .cc  { background:#10b981;color:#fff;text-align:center;padding:8px;font-weight:bold;font-size:13pt;border-radius:3px; }
    .grid { width:100%; }
    .grid td { vertical-align:top;padding:2px 4px;font-size:9pt; }
    .pill { display:inline-block;background:#e0e7ff;color:#3730a3;padding:1px 6px;border-radius:8px;font-size:7.5pt;font-weight:bold; }
</style>
</head>
<body>
<div class="wrap">
    <div class="top">INTERNAL · {{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div>

    <div class="row bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string)$data['awb'], 'C128') . '" alt="awb" />'; @endphp
        <div class="lbl">AWB <span class="pill">#{{ $data['awb'] }}</span></div>
    </div>

    <div class="row">
        <div class="lbl">Recipient</div>
        <div class="val">{{ $data['receiver']['name'] }}</div>
        <div>{{ $data['receiver']['addressLine1'] }}</div>
        <div>{{ $data['receiver']['city'] }} · {{ $data['receiver']['country'] }}</div>
        <div>{{ $data['receiver']['phone'] }}</div>
    </div>

    <div class="row">
        <div class="lbl">Origin</div>
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

    <div class="row" style="padding:0;border:none;">
        @if ($data['isCod'])
            <div class="cod">COD · {{ number_format((float) $data['codAmount'], 2) }}</div>
        @else
            <div class="cc">PRE-PAID</div>
        @endif
    </div>
</div>
</body>
</html>
