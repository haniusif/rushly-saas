@extends('backend.partials.master')
@section('title'){{ __('New GRN') }}@endsection

@push('styles')
<style>
  .items-table th, .items-table td { vertical-align:middle !important; }
  .items-table input, .items-table select { font-size:13px; }
  .add-row { width:40px; }
  .row-num { color:#9ca3af; font-weight:600; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.grn.index') }}">{{ __('Receiving (GRN)') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol></nav>
    </div></div>

    <form method="POST" action="{{ route('wms.grn.store') }}" id="grnForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">{{ __('New GRN') }}</h5>
                <span class="text-monospace text-muted">{{ $nextNumber }}</span>
            </div>
            <div class="card-body">
                <div class="form-row">
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
                    <div class="form-group col-md-4">
                        <label>{{ __('Reference / PO #') }}</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="{{ __('Optional') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('Notes') }}</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('Line items') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">+ {{ __('Add item') }}</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 items-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>{{ __('Product') }} *</th>
                                <th>{{ __('Location') }} *</th>
                                <th style="width:110px;">{{ __('Expected') }} *</th>
                                <th style="width:110px;">{{ __('Received') }}</th>
                                <th style="width:140px;">{{ __('Batch') }}</th>
                                <th style="width:150px;">{{ __('Expiry') }}</th>
                                <th style="width:130px;">{{ __('Condition') }}</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex">
            <a href="{{ route('wms.grn.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
            <button class="btn btn-primary ml-auto">{{ __('Save GRN (draft)') }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function ($) {
  // Server-injected product + location collections for the row template.
  const PRODUCTS  = @json($productOptions);
  const LOCATIONS = @json($locationOptions);

  let rowIdx = 0;
  function rowHtml(i) {
    const prodOpts = PRODUCTS.map(p => `<option value="${p.id}" data-merchant="${p.merchant_id}" data-hub="${p.hub_id}">${esc(p.sku)} — ${esc(p.name)}</option>`).join('');
    const locOpts  = LOCATIONS.map(l => `<option value="${l.id}" data-hub="${l.hub_id}">${esc(l.code)}</option>`).join('');
    return `
      <tr data-row="${i}">
        <td class="row-num">${i + 1}</td>
        <td><select name="items[${i}][product_id]" class="form-control prod" required><option value="">—</option>${prodOpts}</select></td>
        <td><select name="items[${i}][location_id]" class="form-control loc" required><option value="">—</option>${locOpts}</select></td>
        <td><input type="number" min="1" name="items[${i}][expected_qty]" class="form-control" required></td>
        <td><input type="number" min="0" name="items[${i}][received_qty]" class="form-control"></td>
        <td><input type="text" name="items[${i}][batch_number]" class="form-control"></td>
        <td><input type="date" name="items[${i}][expiry_date]" class="form-control"></td>
        <td>
          <select name="items[${i}][condition]" class="form-control">
            <option value="good">good</option>
            <option value="damaged">damaged</option>
            <option value="expired">expired</option>
          </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove">×</button></td>
      </tr>
    `;
  }
  function esc(s) { return $('<div>').text(s ?? '').html(); }

  $('#addRow').on('click', function () {
    $('#itemsBody').append(rowHtml(rowIdx++));
  });
  $(document).on('click', '.remove-row', function () {
    $(this).closest('tr').remove();
  });

  // Default received_qty to expected_qty if user types in expected first
  $(document).on('input', 'input[name$="[expected_qty]"]', function () {
    const $tr = $(this).closest('tr');
    const $rec = $tr.find('input[name$="[received_qty]"]');
    if (!$rec.val()) $rec.val($(this).val());
  });

  // Start with one empty row
  $('#addRow').click();
})(jQuery);
</script>
@endpush
@endsection
