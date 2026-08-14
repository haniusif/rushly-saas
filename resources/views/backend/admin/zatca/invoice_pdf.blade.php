<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $z->invoice_number }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: dejavusans, sans-serif; color: #1f2937; font-size: 10pt; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 16px; }
        .h-title { font-size: 18pt; color: #1d4ed8; font-weight: bold; margin: 0; }
        .h-ar    { font-size: 14pt; color: #1d4ed8; margin: 0; }
        .meta { width: 100%; margin-bottom: 14px; }
        .meta td { vertical-align: top; padding: 2px 4px; }
        .label { color: #6b7280; font-size: 8pt; text-transform: uppercase; letter-spacing: .04em; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.lines th, table.lines td { border: 1px solid #d1d5db; padding: 6px 8px; }
        table.lines th { background: #f3f4f6; text-align: left; font-size: 9pt; }
        .totals { width: 50%; margin-left: 50%; margin-top: 10px; }
        .totals td { padding: 4px 8px; }
        .totals .grand { background: #1d4ed8; color: white; font-weight: bold; }
        .qr { text-align: center; margin-top: 18px; }
        .qr svg { width: 130px; height: 130px; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #d1d5db; font-size: 8pt; color: #6b7280; }
        .arabic { direction: rtl; font-family: dejavusans, sans-serif; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width:100%">
            <tr>
                <td style="width:60%">
                    <p class="h-title">{{ $setting?->seller_name_en ?: $z->buyer_name }}</p>
                    <p class="h-ar arabic">{{ $setting?->seller_name_ar }}</p>
                </td>
                <td style="width:40%; text-align:right">
                    <div class="label">Tax Invoice / فاتورة ضريبية</div>
                    <div style="font-size:14pt; font-weight:bold;">{{ $z->invoice_number }}</div>
                    <div class="label">{{ ucfirst($z->invoice_type) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td style="width:50%">
                <div class="label">Seller VAT / الرقم الضريبي</div>
                <div>{{ $setting?->vat_number }}</div>
                @if ($setting?->cr_number)
                    <div class="label" style="margin-top:6px">CR Number / السجل التجاري</div>
                    <div>{{ $setting->cr_number }}</div>
                @endif
                <div class="label" style="margin-top:6px">Address / العنوان</div>
                <div>
                    {{ trim(($setting?->building_number ? $setting->building_number . ' ' : '') . ($setting?->address_street_en ?? '')) }}<br>
                    {{ trim(($setting?->district_en ? $setting->district_en . ', ' : '') . ($setting?->city_en ?? '') . ' ' . ($setting?->postal_code ?? '')) }}<br>
                    {{ $setting?->country_code }}
                </div>
            </td>
            <td style="width:50%; text-align:right">
                <div class="label">Issued / التاريخ</div>
                <div>{{ optional($z->issued_at)->format('Y-m-d H:i') }} UTC</div>
                <div class="label" style="margin-top:6px">UUID</div>
                <div style="font-size:8pt">{{ $z->uuid }}</div>
                <div class="label" style="margin-top:6px">Buyer / المشتري</div>
                <div>{{ $z->buyer_name ?: '—' }}</div>
                @if ($z->buyer_vat_number)
                    <div class="label" style="margin-top:4px">Buyer VAT</div>
                    <div>{{ $z->buyer_vat_number }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th style="width:50%">Description / الوصف</th>
                <th style="text-align:right">Net / الصافي</th>
                <th style="text-align:right">VAT ({{ rtrim(rtrim(number_format($z->vat_rate, 2), '0'), '.') }}%)</th>
                <th style="text-align:right">Total / المجموع</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Logistics services — Invoice #{{ optional($z->invoice)->invoice_id ?? $z->invoice_id }}</td>
                <td style="text-align:right">{{ number_format($z->subtotal, 2) }}</td>
                <td style="text-align:right">{{ number_format($z->vat_amount, 2) }}</td>
                <td style="text-align:right">{{ number_format($z->total_inclusive, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Subtotal / الصافي</td><td style="text-align:right">{{ number_format($z->subtotal, 2) }} {{ $z->currency }}</td></tr>
        <tr><td class="label">VAT / الضريبة</td><td style="text-align:right">{{ number_format($z->vat_amount, 2) }} {{ $z->currency }}</td></tr>
        <tr class="grand"><td>Total / الإجمالي</td><td style="text-align:right">{{ number_format($z->total_inclusive, 2) }} {{ $z->currency }}</td></tr>
    </table>

    <div class="qr">
        {!! $qrSvg !!}
        <div class="label" style="margin-top:4px">ZATCA Phase 1 QR — التحقق عبر تطبيق فاتورة</div>
    </div>

    <div class="footer">
        Generated {{ optional($z->generated_at)->format('Y-m-d H:i:s') }} UTC · Hash {{ substr($z->hash ?? '', 0, 16) }}…<br>
        This is a tax invoice compliant with ZATCA Phase 1 (Generation). For inquiries, scan the QR with the Fatoora app.
    </div>
</body>
</html>
