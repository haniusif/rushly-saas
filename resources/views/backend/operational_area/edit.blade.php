@extends('backend.partials.master')
@section('title')
    المناطق التشغيلية {{ __('levels.edit') }}
@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('operational_areas.index') }}" class="breadcrumb-link">المناطق التشغيلية</a></li>
                            <li class="breadcrumb-item active">{{ __('levels.edit') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="pageheader-title">تعديل منطقة تشغيلية</h2>
                    <form action="{{ route('operational_areas.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $area->id }}">

                        <div class="form-group">
                            <label>الاسم <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $area->name) }}" required>
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>الرمز</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $area->code) }}">
                            @error('code') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>{{ __('levels.status') }} <span class="text-danger">*</span></label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                @foreach(trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ (old('status', $area->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('levels.update') }}</button>
                        <a href="{{ route('operational_areas.index') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
