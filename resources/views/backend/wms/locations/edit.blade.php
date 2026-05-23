@extends('backend.partials.master')
@section('title'){{ __('Edit Location') }} — {{ $location->code }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.locations.index') }}">{{ __('Locations') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Edit') }} {{ $location->code }}</li>
        </ol></nav>
    </div></div>

    <div class="row justify-content-center"><div class="col-lg-7">
        <form method="POST" action="{{ route('wms.locations.update', $location->id) }}" class="card">
            @csrf @method('PUT')
            <div class="card-header"><h5 class="mb-0">{{ __('Edit') }} <span class="text-monospace">{{ $location->code }}</span></h5></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('Hub') }} *</label>
                    <select name="hub_id" class="form-control" required>
                        @foreach ($hubs as $h)<option value="{{ $h->id }}" {{ $location->hub_id==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>{{ __('Zone') }}</label><input type="text" name="zone" class="form-control" value="{{ $location->zone }}"></div>
                    <div class="form-group col-md-3"><label>{{ __('Aisle') }}</label><input type="text" name="aisle" class="form-control" value="{{ $location->aisle }}"></div>
                    <div class="form-group col-md-3"><label>{{ __('Rack') }} *</label><input type="text" name="rack" class="form-control" value="{{ $location->rack }}" required></div>
                    <div class="form-group col-md-3"><label>{{ __('Shelf') }} *</label><input type="text" name="shelf" class="form-control" value="{{ $location->shelf }}" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>{{ __('Bin') }}</label><input type="text" name="bin" class="form-control" value="{{ $location->bin }}"></div>
                    <div class="form-group col-md-3">
                        <label>{{ __('Type') }}</label>
                        <select name="type" class="form-control">
                            @foreach ($types as $t)<option value="{{ $t }}" {{ $location->type==$t ? 'selected' : '' }}>{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3"><label>{{ __('Capacity') }}</label><input type="number" name="capacity" class="form-control" value="{{ $location->capacity }}"></div>
                    <div class="form-group col-md-3"><label>{{ __('Code') }}</label><input type="text" name="code" class="form-control" value="{{ $location->code }}"></div>
                </div>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="act" name="is_active" {{ $location->is_active ? 'checked' : '' }}>
                    <label class="custom-control-label" for="act">{{ __('Active') }}</label>
                </div>
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('wms.locations.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button class="btn btn-primary ml-auto">{{ __('Save') }}</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
