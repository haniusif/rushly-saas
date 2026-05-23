@extends('backend.partials.master')
@section('title'){{ __('Report Damage') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.damage.index') }}">{{ __('Damage Reports') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol></nav>
    </div></div>

    <div class="row justify-content-center"><div class="col-lg-8">
        <form method="POST" action="{{ route('wms.damage.store') }}" enctype="multipart/form-data" class="card">
            @csrf
            <div class="card-header"><h5 class="mb-0">{{ __('Report Damage') }}</h5></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ __('Product') }} *</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->sku }} — {{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ __('Location') }} *</label>
                        <select name="location_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->code }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>{{ __('Quantity Damaged') }} *</label>
                        <input type="number" name="quantity_damaged" min="1" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ __('Cause') }} *</label>
                        <select name="cause" class="form-control" required>
                            <option value="transit_damage">transit damage</option>
                            <option value="handling">handling</option>
                            <option value="water">water</option>
                            <option value="expiry">expiry</option>
                            <option value="unknown">unknown</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>{{ __('Action Taken') }}</label>
                        <select name="action_taken" class="form-control">
                            <option value="">{{ __('Decide later') }}</option>
                            <option value="written_off">written off</option>
                            <option value="returned_to_merchant">returned to merchant</option>
                            <option value="quarantine">quarantine</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('Notes') }}</label>
                    <textarea name="notes" rows="3" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('Photos (up to 5)') }}</label>
                    <input type="file" name="photos[]" accept="image/*" multiple class="form-control-file">
                </div>
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('wms.damage.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button class="btn btn-primary ml-auto">{{ __('Save Report') }}</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
