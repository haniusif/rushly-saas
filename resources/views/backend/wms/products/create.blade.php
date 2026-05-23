@extends('backend.partials.master')
@section('title'){{ __('New Product') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header">
        <div class="page-breadcrumb">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.products.index') }}">{{ __('Products') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
            </ol></nav>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <form method="POST" action="{{ route('wms.products.store') }}" class="card">
                @csrf
                <div class="card-header"><h5 class="mb-0">{{ __('New Product') }}</h5></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>{{ __('Name') }} *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label>{{ __('SKU') }} *</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" required>
                            @error('sku')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label>{{ __('Barcode') }}</label>
                            <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}" placeholder="{{ __('auto if blank') }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>{{ __('Merchant') }} *</label>
                            <select name="merchant_id" class="form-control" required>
                                <option value="">—</option>
                                @foreach ($merchants as $m)
                                    <option value="{{ $m->id }}" {{ old('merchant_id')==$m->id ? 'selected' : '' }}>{{ $m->business_name ?? ('#'.$m->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{ __('Warehouse (Hub)') }} *</label>
                            <select name="hub_id" class="form-control" required>
                                <option value="">—</option>
                                @foreach ($hubs as $h)
                                    <option value="{{ $h->id }}" {{ old('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>{{ __('Unit') }} *</label>
                            <select name="unit" class="form-control" required>
                                @foreach ($units as $u)
                                    <option value="{{ $u }}" {{ old('unit', 'piece')==$u ? 'selected' : '' }}>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>{{ __('Weight (kg)') }}</label>
                            <input type="number" step="0.001" name="weight" class="form-control" value="{{ old('weight') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>{{ __('Reorder Point') }}</label>
                            <input type="number" name="reorder_point" class="form-control" value="{{ old('reorder_point', 0) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>{{ __('Category') }}</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category') }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3"><label>{{ __('Length (cm)') }}</label><input type="number" step="0.01" name="dim_l" class="form-control" value="{{ old('dim_l') }}"></div>
                        <div class="form-group col-md-3"><label>{{ __('Width (cm)') }}</label><input type="number" step="0.01" name="dim_w" class="form-control" value="{{ old('dim_w') }}"></div>
                        <div class="form-group col-md-3"><label>{{ __('Height (cm)') }}</label><input type="number" step="0.01" name="dim_h" class="form-control" value="{{ old('dim_h') }}"></div>
                        <div class="form-group col-md-3 d-flex align-items-end pb-2">
                            <div class="custom-control custom-switch mr-3">
                                <input type="checkbox" class="custom-control-input" id="trk" name="track_expiry">
                                <label class="custom-control-label" for="trk">{{ __('Track expiry') }}</label>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="act" name="is_active" checked>
                                <label class="custom-control-label" for="act">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('Description') }}</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer d-flex">
                    <a href="{{ route('wms.products.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    <button class="btn btn-primary ml-auto">{{ __('Create Product') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
