@extends('backend.partials.master')
@section('title'){{ __('New Location') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.locations.index') }}">{{ __('Locations') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol></nav>
    </div></div>

    <div class="row justify-content-center"><div class="col-lg-7">
        <form method="POST" action="{{ route('wms.locations.store') }}" class="card" id="locForm">
            @csrf
            <div class="card-header"><h5 class="mb-0">{{ __('New Location') }}</h5></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('Hub') }} *</label>
                    <select name="hub_id" class="form-control" required>
                        <option value="">—</option>
                        @foreach ($hubs as $h)<option value="{{ $h->id }}">{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>{{ __('Zone') }}</label><input type="text" name="zone" id="f_zone" class="form-control" placeholder="A"></div>
                    <div class="form-group col-md-3"><label>{{ __('Aisle') }}</label><input type="text" name="aisle" id="f_aisle" class="form-control" placeholder="A1"></div>
                    <div class="form-group col-md-3"><label>{{ __('Rack') }} *</label><input type="text" name="rack" id="f_rack" class="form-control" required placeholder="R01"></div>
                    <div class="form-group col-md-3"><label>{{ __('Shelf') }} *</label><input type="text" name="shelf" id="f_shelf" class="form-control" required placeholder="S02"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>{{ __('Bin') }}</label><input type="text" name="bin" id="f_bin" class="form-control" placeholder="BIN-003"></div>
                    <div class="form-group col-md-3">
                        <label>{{ __('Type') }}</label>
                        <select name="type" class="form-control">
                            @foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3"><label>{{ __('Capacity (units)') }}</label><input type="number" name="capacity" class="form-control"></div>
                    <div class="form-group col-md-3"><label>{{ __('Code') }}</label><input type="text" name="code" id="f_code" class="form-control" placeholder="{{ __('auto') }}"></div>
                </div>
                <small class="text-muted">{{ __('Code is auto-built from Zone-Aisle-Rack-Shelf-Bin if left blank.') }}</small>
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('wms.locations.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button class="btn btn-primary ml-auto">{{ __('Create Location') }}</button>
            </div>
        </form>
    </div></div>
</div>
@push('scripts')
<script>
// Live-preview auto code as user types.
(function ($) {
  function buildCode() {
    const parts = ['#f_zone','#f_aisle','#f_rack','#f_shelf','#f_bin'].map(s => ($(s).val() || '').trim().toUpperCase()).filter(Boolean);
    return parts.join('-');
  }
  $('#f_zone, #f_aisle, #f_rack, #f_shelf, #f_bin').on('input', function () {
    if (!$('#f_code').val().trim()) $('#f_code').attr('placeholder', buildCode() || 'auto');
  });
})(jQuery);
</script>
@endpush
@endsection
