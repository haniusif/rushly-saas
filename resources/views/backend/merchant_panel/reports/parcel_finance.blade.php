@extends('backend.partials.master')
@section('title')
   {{ __('reports.parcel_finance_reports') }}
@endsection

@section('maincontent')
<div class="container-fluid dashboard-content">

  <!-- Page Header -->
  <div class="row">
    <div class="col-12">
      <div class="page-header">
        <div class="page-breadcrumb">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('dashboard.title') }}</a></li>
              <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('reports.title') }}</a></li>
              <li class="breadcrumb-item active" aria-current="page">{{ __('reports.parcel_finance_reports') }}</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
  <!-- /Page Header -->

  <!-- Filter Form -->
  <div class="card mb-3">
    <div class="card-body">
      <form action="{{ route('parcel.finance.reports') }}" method="GET">
        <div class="row">
          <div class="form-group col-xl-2 col-lg-3 col-md-6">
            <label>{{ __('parcel.date') }}</label>
            <input type="text" autocomplete="off" id="date" name="parcel_date"
              class="form-control date_range_picker"
              value="{{ old('parcel_date', request()->parcel_date) }}">
          </div>

          <div class="form-group col-xl-4 col-lg-4 col-md-6">
            <label>{{ __('parcel.status') }}</label>
            <select id="parcelStatus" name="parcel_status[]" class="form-control select2" multiple="multiple">
              @foreach (trans('parcelStatusFilter') as $key => $status)
                <option value="{{ $key }}" @if(request()->parcel_status && in_array($key, request()->parcel_status)) selected @endif>
                  {{ $status }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group col-12 d-flex align-items-end flex-wrap">
            <button type="submit" class="btn btn-primary btn-space mr-2 mb-2">
              <i class="fa fa-filter"></i> {{ __('levels.filter') }}
            </button>
            <a href="{{ route('parcel.reports') }}" class="btn btn-secondary btn-space mr-2 mb-2">
              <i class="fa fa-eraser"></i> {{ __('levels.clear') }}
            </a>
            <button type="button" id="exportExcel" class="btn btn-success btn-space mr-2 mb-2">
              <i class="fa fa-file-excel-o"></i> {{ __('Export Excel') }}
            </button>
            <button type="button" id="printTable" class="btn btn-info mb-2">
              <i class="fa fa-print"></i> {{ __('Print') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- /Filter Form -->


<!-- Current Balance Card -->
<div class="card mb-4">
  <div class="card-body d-flex justify-content-between align-items-center">
    <h5 class="mb-0 text-dark">
      <i class="fa fa-wallet text-success mr-2"></i> {{ __('Current Balance') }}
    </h5>
    <h4 class="mb-0 text-primary font-weight-bold">
      {{ number_format($current_balance, 2) }} AED
    </h4>
  </div>
</div>
<!-- /Current Balance Card -->


  <!-- Summary by Order Status -->
  @php
      $deliveredParcels = $parcels->filter(function($p){
          return strtolower($p->status_name) === 'delivered';
      });

      $totalDelivered = $deliveredParcels->count();
      $totalCash = $deliveredParcels->sum('cash_collection');
      $totalDelivery = $deliveredParcels->sum('delivery_charge');
      $totalCOD = $deliveredParcels->sum('cod_amount');
      $totalPayable = $deliveredParcels->sum('current_payable');
  @endphp

  <div class="card mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ __('Delivered Orders Summary') }}</h5>
      <span class="badge badge-light text-dark p-2">{{ $totalDelivered }} {{ __('Delivered Parcels') }}</span>
    </div>
    <div class="card-body p-0">
      <table class="table table-bordered mb-0">
        <thead class="thead-light">
          <tr>
            <th>{{ __('Total Delivered') }}</th>
            <th>{{ __('Total Cash Collection') }}</th>
            <th>{{ __('Total Delivery Charge') }}</th>
            <th>{{ __('Total COD Amount') }}</th>
            <th>{{ __('Total Payable') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ $totalDelivered }}</td>
            <td>{{ number_format($totalCash, 2) }}</td>
            <td>{{ number_format($totalDelivery, 2) }}</td>
            <td>{{ number_format($totalCOD, 2) }}</td>
            <td>{{ number_format($totalPayable, 2) }}</td>
          </tr>
          @if ($totalDelivered == 0)
          <tr>
            <td colspan="5" class="text-center text-muted">{{ __('No delivered records found') }}</td>
          </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
  <!-- /Summary by Order Status -->

  <!-- Table -->
  <div class="card">
    <div class="card-body table-responsive">
      <table id="parcelFinanceTable" class="table table-bordered table-striped table-hover">
        <thead class="thead-dark">
          <tr>
            <th>#</th>
            <th>{{ __('Invoice No') }}</th>
            <th>{{ __('Tracking ID') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Cash Collection') }}</th>
            <th>{{ __('Liquid Fragile Amount') }}</th>
            <th>{{ __('Packaging Amount') }}</th>
            <th>{{ __('Delivery Charge') }}</th>
            <th>{{ __('COD Charge') }}</th>
            <th>{{ __('COD Amount') }}</th>
            <th>{{ __('VAT (%)') }}</th>
            <th>{{ __('VAT Amount') }}</th>
            <th>{{ __('Total Delivery Amount') }}</th>
            <th>{{ __('Current Payable') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($parcels as $parcel)
          <tr @if(strtolower($parcel->status_name) === 'delivered') class="table-success" @endif>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $parcel->invoice_no }}</td>
            <td>{{ $parcel->tracking_id }}</td>
            <td>{{ ucfirst($parcel->status_name ?? 'Unknown') }}</td>
            <td>{{ number_format($parcel->cash_collection, 2) }}</td>
            <td>{{ number_format($parcel->liquid_fragile_amount, 2) }}</td>
            <td>{{ number_format($parcel->packaging_amount, 2) }}</td>
            <td>{{ number_format($parcel->delivery_charge, 2) }}</td>
            <td>{{ number_format($parcel->cod_charge, 2) }}</td>
            <td>{{ number_format($parcel->cod_amount, 2) }}</td>
            <td>{{ $parcel->vat }}</td>
            <td>{{ number_format($parcel->vat_amount, 2) }}</td>
            <td>{{ number_format($parcel->total_delivery_amount, 2) }}</td>
            <td>{{ number_format($parcel->current_payable, 2) }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="23" class="text-center text-muted">{{ __('No records found') }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
  #selectAssignType .select2-container .select2-selection--single {
    height: 32px !important;
  }
  table td {
    vertical-align: middle !important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
<script src="{{ static_asset('backend/js/reports/jquery.table2excel.min.js') }}"></script>

<script>
  $(function () {
    $('.select2').select2();

    // Export to Excel
    $('#exportExcel').on('click', function () {
      $("#parcelFinanceTable").table2excel({
        filename: "parcel_finance_reports_" + new Date().toISOString().slice(0,10) + ".xls"
      });
    });

    // Print Table
    $('#printTable').on('click', function () {
      var printContent = document.getElementById("parcelFinanceTable").outerHTML;
      var newWin = window.open("");
      newWin.document.write(`
        <html>
          <head>
            <title>{{ __('Parcel Finance Reports') }}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
          </head>
          <body onload="window.print()">
            <div class="container mt-4">
              <h4 class="text-center mb-3">{{ __('Parcel Finance Reports') }}</h4>
              ${printContent}
            </div>
          </body>
        </html>
      `);
      newWin.document.close();
    });
  });
</script>
@endpush
