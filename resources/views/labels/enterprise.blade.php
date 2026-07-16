<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;">
<head>
<meta charset="UTF-8">
<title>AWB {{ $data['awb'] }}</title>
<style>
    html, body { width:100%; height:100%; margin:0; padding:0; font-family:'Arial',sans-serif; font-size:9pt; color:#111; }
    .wrap { width:100%; height:100%; box-sizing:border-box; padding:8px; }
    .head-tbl { width:100%; border-collapse:collapse; }
    .head-tbl td { padding:6px 8px; border:1.5px solid #111; vertical-align:top; }
    .head-tbl .brand-cell { width:60%; }
    .head-tbl .brand { font-size:11pt; font-weight:900; letter-spacing:.02em; }
    .head-tbl .type { font-size:7pt; letter-spacing:.15em; text-transform:uppercase; color:#555; }
    .head-tbl .awb { font-size:16pt; font-weight:900; text-align:right; }
    .bar { text-align:center; padding:6px 0; border:1.5px solid #111; border-top:none; }
    .bar img { width:96%; height:52px; }
    .grid { width:100%; border-collapse:collapse; margin-top:6px; }
    .grid td { border:1px solid #111; padding:5px 8px; vertical-align:top; }
    .lbl { font-size:6.5pt; text-transform:uppercase; letter-spacing:.15em; color:#555; font-weight:800; }
    .val { font-size:10pt; font-weight:800; margin-top:1px; }
    .sub { font-size:8.5pt; margin-top:1px; }
    .cod { background:#111; color:#fff; text-align:center; padding:10px; font-weight:900; font-size:16pt; margin-top:6px; }
    .cc  { background:#e5e7eb; color:#111; text-align:center; padding:10px; font-weight:800; font-size:12pt; margin-top:6px; }
    .foot { display:table; width:100%; margin-top:4px; font-size:7.5pt; color:#333; }
    .foot > div { display:table-cell; padding:2px 4px; }
</style>
</head>
<body>
<div class="wrap">
    {{-- Header --}}
    <table class="head-tbl">
        <tr>
            <td class="brand-cell">
                <div class="type">Air Waybill</div>
                <div class="brand">{{ strtoupper($data['sender']['name'] ?: 'RUSHLY') }}</div>
                <div class="sub">Pg {{ $data['currentPage'] }} of {{ $data['totalPages'] }}</div>
            </td>
            <td>
                <div class="lbl">AWB Number</div>
                <div class="awb">{{ $data['awb'] }}</div>
            </td>
        </tr>
    </table>

    {{-- Barcode --}}
    <div class="bar">
        @php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG((string) $data['awb'], 'C128') . '" alt="awb" />'; @endphp
    </div>

    {{-- Sender / Receiver --}}
    <table class="grid">
        <tr>
            <td style="width:50%;">
                <div class="lbl">Sender</div>
                <div class="val">{{ $data['sender']['name'] }}</div>
                <div class="sub">{{ $data['sender']['addressLine1'] }}</div>
                <div class="sub">📞 {{ $data['sender']['phone'] }}</div>
            </td>
            <td>
                <div class="lbl">Receiver</div>
                <div class="val">{{ $data['receiver']['name'] }}</div>
                <div class="sub">{{ $data['receiver']['addressLine1'] }}</div>
                <div class="sub">{{ $data['receiver']['city'] }} · {{ $data['receiver']['country'] }}</div>
                <div class="sub">📞 {{ $data['receiver']['phone'] }}</div>
            </td>
        </tr>
    </table>

    {{-- Package + refs --}}
    <table class="grid">
        <tr>
            <td><div class="lbl">Order</div><div>{{ $data['orderNumber'] }}</div></td>
            <td><div class="lbl">Reference</div><div>{{ $data['reference_number'] }}</div></td>
            <td><div class="lbl">Date</div><div>{{ $data['date'] }}</div></td>
            <td><div class="lbl">Pieces</div><div>{{ $data['currentPage'] }}/{{ $data['totalPages'] }}</div></td>
        </tr>
    </table>

    {{-- COD --}}
    @if ($data['isCod'])
        <div class="cod">COD · {{ number_format((float) $data['codAmount'], 2) }}</div>
    @else
        <div class="cc">PRE-PAID</div>
    @endif

    {{-- Footer --}}
    <div class="foot">
        <div>Signature ______________________</div>
        <div>Date ______________________</div>
    </div>
</div>
</body>
</html>
