@extends('backend.partials.master')
@section('title'){{ __('New Cycle Count') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.cycle-counts.index') }}">{{ __('Cycle Counts') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
        </ol></nav>
    </div></div>

    <div class="row justify-content-center"><div class="col-lg-7">
        <form method="POST" action="{{ route('wms.cycle-counts.store') }}" class="card">
            @csrf
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">{{ __('New Cycle Count') }}</h5>
                <span class="text-monospace text-muted">{{ $next }}</span>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('Hub') }} *</label>
                    <select name="hub_id" class="form-control" required>
                        <option value="">—</option>
                        @foreach ($hubs as $h)<option value="{{ $h->id }}">{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>{{ __('Scope') }} *</label>
                        <select name="scope" class="form-control" required>
                            <option value="zone">{{ __('Zone (count one zone)') }}</option>
                            <option value="aisle">{{ __('Aisle (count one aisle)') }}</option>
                            <option value="full">{{ __('Full warehouse') }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ __('Zone (if scope = zone)') }}</label>
                        <input type="text" name="zone" class="form-control" placeholder="A">
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex">
                <a href="{{ route('wms.cycle-counts.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button class="btn btn-primary ml-auto">{{ __('Create Count Session') }}</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
