@extends('backend.partials.master')
@section('title'){{ __('New Adjustment') }}@endsection
@push('styles')<style>
  .current-qty { background:#f9fafb; border:1px dashed #d1d5db; border-radius:8px; padding:14px 18px; text-align:center; }
  .current-qty .lbl { color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; }
  .current-qty .num { font-size:32px; font-weight:800; color:#111827; line-height:1; margin-top:4px; }
  .approval-hint { background:#fff7ed; border-left:4px solid #f59e0b; padding:10px 14px; border-radius:6px; font-size:13px; color:#9a3412; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.adjustments.index') }}">{{ __('Adjustments') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol></nav>
    </div></div>

    <div class="row justify-content-center"><div class="col-lg-8">
        <form method="POST" action="{{ route('wms.adjustments.store') }}" enctype="multipart/form-data" class="card">
            @csrf
            <div class="card-header"><h5 class="mb-0">{{ __('New Stock Adjustment') }}</h5></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ __('Product') }} *</label>
                        <select name="product_id" class="form-control" required id="prodSelect">
                            <option value="">—</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ (string)$preProduct===(string)$p->id ? 'selected' : '' }}>{{ $p->sku }} — {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ __('Location') }} *</label>
                        <select name="location_id" class="form-control" required id="locSelect">
                            <option value="">—</option>
                            @foreach ($locations as $l)
                                <option value="{{ $l->id }}" {{ (string)$preLocation===(string)$l->id ? 'selected' : '' }}>{{ $l->code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($currentQty !== null)
                    <div class="current-qty mb-3">
                        <div class="lbl">{{ __('Current stock at this location') }}</div>
                        <div class="num">{{ $currentQty }}</div>
                    </div>
                @endif

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>{{ __('New quantity (after)') }} *</label>
                        <input type="number" name="quantity_after" id="qtyAfter" min="0" class="form-control" required>
                        <small class="text-muted">{{ __('Set the corrected on-hand quantity.') }}</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ __('Reason') }} *</label>
                        <select name="reason" class="form-control" required>
                            @foreach ($reasons as $r)<option value="{{ $r }}">{{ str_replace('_',' ',$r) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ __('Reference') }}</label>
                        <input type="text" name="reference" class="form-control" placeholder="{{ __('e.g. CC-2025-00012') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('Notes') }}</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>{{ __('Photo (evidence)') }}</label>
                    <input type="file" name="photo" accept="image/*" class="form-control-file">
                </div>

                <div class="approval-hint">
                    ⚠ {{ __('Adjustments of ±20% or more of current stock require a second supervisor to approve before the change is applied.') }}
                </div>
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('wms.adjustments.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button class="btn btn-primary ml-auto">{{ __('Submit Adjustment') }}</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
