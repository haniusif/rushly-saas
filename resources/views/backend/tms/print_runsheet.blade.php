<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Driver Run Sheet</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      background: #f9f9f9;
      padding: 20px;
    }
    h3 {
      font-weight: bold;
      color: #333;
    }
    table th {
      background: #343a40;
      color: #fff;
      text-align: center;
      vertical-align: middle;
    }
    table td {
      vertical-align: middle;
      text-align: center;
    }
    .table-summary {
      margin-top: 20px;
      font-weight: bold;
    }
    @media print {
      .no-print { display: none; }
      body { background: #fff; }
    }
  </style>
</head>

<body>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Driver Run Sheet</h3>
    <button class="btn btn-primary no-print" onclick="window.print()">
      <i class="fa fa-print me-2"></i> Print
    </button>
  </div>

  <p>
    <strong>Date:</strong> {{ $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date }}<br>
    <strong>Driver ID:</strong> {{ $driver_id }}
  </p>

  <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Tracking ID</th>
          <th>Merchant</th>
          <th>Customer</th>
          <th>Address</th>
          <th>COD</th>
        
        
        </tr>
      </thead>
      <tbody>
        @forelse($assignedShipments as $index => $item)
          @php
            $parcel = $item->parcel ?? null;
          @endphp
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>
            
                    <?php 
                                             $bolded = preg_replace('/(\d{4})(?!.*\d)/', '<strong>$1</strong>', $parcel->tracking_id);
                                             ?>
                                            #{!! $bolded !!}
                                            <br>
                                              <?php
                $awb = "$parcel->id";
                echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($awb, 'C128') . '" alt="barcode" />';
            ?>
            
            </td>
            <td>{{ $parcel->merchant->business_name ?? $parcel->merchant_id ?? '-' }}</td>
            <td>{{ $parcel->customer_name ?? '-' }}</td>
            <td style="text-align:left;">{{ $parcel->customer_address ?? '-' }}</td>
            <td>{{ number_format($parcel->cash_collection ?? 0, 2) }}</td>
      
          </tr>
        @empty
          <tr>
            <td colspan="15" class="text-center text-muted">No shipments found for this driver.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @php
    $totalCash = $assignedShipments->sum(fn($s) => $s->parcel->cash_collection ?? 0);


  @endphp

  <div class="table-summary">
    <div>Total Cash Collection: {{ number_format($totalCash, 2) }}</div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>
