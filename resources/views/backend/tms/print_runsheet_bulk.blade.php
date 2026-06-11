<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Driver Run Sheets — Bulk</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f9f9f9;
      padding: 20px;
    }
    h3 { font-weight: bold; color: #333; margin-bottom: 4px; }
    .runsheet { background: #fff; border-radius: 8px; padding: 18px 22px; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 22px; }
    table th { background: #343a40; color: #fff; text-align: center; vertical-align: middle; }
    table td { vertical-align: middle; text-align: center; }
    .toolbar { position: sticky; top: 0; z-index: 10; background: #f9f9f9; padding: 10px 0; margin-bottom: 14px; border-bottom: 1px solid #e2e8f0; }
    @media print {
      .no-print { display: none !important; }
      body { background: #fff; padding: 0; }
      .runsheet { box-shadow: none; border: 0; padding: 0; margin: 0 0 12px; page-break-after: always; }
      .runsheet:last-child { page-break-after: auto; }
      .toolbar { display: none; }
    }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="toolbar d-flex justify-content-between align-items-center no-print">
    <h3 class="m-0">Driver Run Sheets — Bulk</h3>
    <div>
      <span class="badge bg-secondary me-2">{{ count($driverSheets) }} drivers</span>
      <button class="btn btn-primary" onclick="window.print()">
        <i class="fa fa-print me-2"></i> Print
      </button>
    </div>
  </div>

  @foreach ($driverSheets as $sheet)
    @php
      $shipments = $sheet['shipments'] ?? collect();
      $totalCash      = 0.0;
      $totalDelivered = 0;
      $codDelivered   = 0.0;
      foreach ($shipments as $item) {
          $p = $item->parcel ?? null;
          if (!$p) continue;
          $cod = (float) ($p->cash_collection ?? 0);
          $totalCash += $cod;
          if (in_array($p->status, [\App\Enums\ParcelStatus::DELIVERED, \App\Enums\ParcelStatus::PARTIAL_DELIVERED])) {
              $totalDelivered++;
              $codDelivered += $cod;
          }
      }
    @endphp
    <div class="runsheet">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h3 class="m-0">{{ $sheet['driverName'] }}</h3>
          <small class="text-muted">Date: {{ $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date }}</small>
        </div>
        <div>
          <span class="badge bg-info text-dark">{{ $shipments->count() }} shipments</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover mb-2">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Tracking ID</th>
              <th>Merchant</th>
              <th>Customer</th>
              <th>Address</th>
              <th>COD</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            @forelse($shipments as $index => $item)
              @php $parcel = $item->parcel ?? null; @endphp
              @if ($parcel)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                  <?php $bolded = preg_replace('/(\d{4})(?!.*\d)/', '<strong>$1</strong>', $parcel->tracking_id); ?>
                  #{!! $bolded !!}<br>
                  <?php
                    $awb = "$parcel->id";
                    echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($awb, 'C128') . '" alt="barcode" />';
                  ?>
                </td>
                <td>{{ $parcel->merchant->business_name ?? $parcel->merchant_id ?? '-' }}</td>
                <td>{{ $parcel->customer_name ?? '-' }}</td>
                <td style="text-align:left;">{{ $parcel->customer_address ?? '-' }}</td>
                <td>{{ number_format($parcel->cash_collection ?? 0, 2) }}</td>
                <td></td>
              </tr>
              @endif
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">No shipments found for this driver.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="text-end fw-bold">Total Cash Collection</td>
              <td></td>
              <td class="fw-bold">{{ number_format($totalCash, 2) }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="4" class="text-end fw-bold">Total Delivered</td>
              <td></td>
              <td class="fw-bold">{{ $totalDelivered }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="4" class="text-end fw-bold">COD Collection (Delivered)</td>
              <td></td>
              <td class="fw-bold">{{ number_format($codDelivered, 2) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  @endforeach
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script>
  // Auto-open print dialog when ?autoprint=1
  const params = new URLSearchParams(window.location.search);
  if (params.get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
  }
</script>
</body>
</html>
