@extends('backend.partials.master')
@section('title'){{ __('New Outbound') }}@endsection
@push('styles')<style>.items-table input,.items-table select{font-size:13px;}</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.outbound.index') }}">{{ __('Outbound') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol></nav>
    </div></div>

    <form method="POST" action="{{ route('wms.outbound.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">{{ __('New Outbound') }}</h5>
                <span class="text-monospace text-muted">{{ $nextNumber }}</span>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>{{ __('Type') }} *</label>
                        <select name="type" class="form-control" required>
                            @foreach ($types as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ __('Merchant') }} *</label>
                        <select name="merchant_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach ($merchants as $m)<option value="{{ $m->id }}">{{ $m->business_name ?? ('#'.$m->id) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ __('Warehouse (Hub)') }} *</label>
                        <select name="hub_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach ($hubs as $h)<option value="{{ $h->id }}">{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('Items to ship out') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">+ {{ __('Add item') }}</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 items-table">
                        <thead class="thead-light"><tr>
                            <th style="width:40px">#</th>
                            <th>{{ __('Product') }} *</th>
                            <th>{{ __('Location') }} *</th>
                            <th style="width:130px">{{ __('Quantity') }} *</th>
                            <th style="width:140px">{{ __('Batch') }}</th>
                            <th style="width:50px"></th>
                        </tr></thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex">
            <a href="{{ route('wms.outbound.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
            <button class="btn btn-primary ml-auto">{{ __('Save (draft)') }}</button>
        </div>
    </form>
</div>
@push('scripts')
<script>
(function ($) {
  const PRODUCTS  = @json($productOptions);
  const LOCATIONS = @json($locationOptions);
  let idx = 0;
  function esc(s) { return $('<div>').text(s ?? '').html(); }
  function rowHtml(i) {
    const prodOpts = PRODUCTS.map(p => `<option value="${p.id}">${esc(p.sku)} — ${esc(p.name)}</option>`).join('');
    const locOpts  = LOCATIONS.map(l => `<option value="${l.id}">${esc(l.code)}</option>`).join('');
    return `<tr data-row="${i}">
      <td class="text-muted">${i + 1}</td>
      <td><select name="items[${i}][product_id]" class="form-control" required><option value="">—</option>${prodOpts}</select></td>
      <td><select name="items[${i}][location_id]" class="form-control" required><option value="">—</option>${locOpts}</select></td>
      <td><input type="number" min="1" name="items[${i}][quantity]" class="form-control" required></td>
      <td><input type="text" name="items[${i}][batch_number]" class="form-control"></td>
      <td><button type="button" class="btn btn-sm btn-link text-danger remove-row">×</button></td>
    </tr>`;
  }
  $('#addRow').on('click', () => $('#itemsBody').append(rowHtml(idx++)));
  $(document).on('click', '.remove-row', function () { $(this).closest('tr').remove(); });
  $('#addRow').click();
})(jQuery);
</script>
@endpush
@endsection
