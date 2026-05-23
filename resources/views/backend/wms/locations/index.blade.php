@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Locations') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Locations') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.locations.map') }}" class="btn btn-sm btn-outline-secondary mr-2">🗺 {{ __('Map view') }}</a>
        <a href="{{ route('wms.locations.create') }}" class="btn btn-sm btn-primary">+ {{ __('New Location') }}</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form class="form-inline" method="GET">
            <select name="hub_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any hub') }}</option>
                @foreach ($hubs as $h)
                    <option value="{{ $h->id }}" {{ request('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>
                @endforeach
            </select>
            <input type="text" name="zone"  value="{{ request('zone') }}"  class="form-control form-control-sm mr-2 mb-1" placeholder="{{ __('Zone') }}" style="width:80px;">
            <input type="text" name="aisle" value="{{ request('aisle') }}" class="form-control form-control-sm mr-2 mb-1" placeholder="{{ __('Aisle') }}" style="width:80px;">
            <select name="type" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any type') }}</option>
                @foreach ($types as $t)
                    <option value="{{ $t }}" {{ request('type')==$t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
            <a href="{{ route('wms.locations.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>#</th><th>{{ __('Code') }}</th><th>{{ __('Hub') }}</th>
                    <th>{{ __('Zone') }}</th><th>{{ __('Aisle') }}</th><th>{{ __('Rack') }}</th>
                    <th>{{ __('Shelf') }}</th><th>{{ __('Bin') }}</th>
                    <th>{{ __('Type') }}</th><th class="text-center">{{ __('Capacity') }}</th>
                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($locations as $loc)
                        <tr>
                            <td>{{ $loc->id }}</td>
                            <td><strong class="text-monospace">{{ $loc->code }}</strong></td>
                            <td>{{ optional($loc->hub)->name ?? '—' }}</td>
                            <td>{{ $loc->zone ?? '—' }}</td>
                            <td>{{ $loc->aisle ?? '—' }}</td>
                            <td>{{ $loc->rack }}</td>
                            <td>{{ $loc->shelf }}</td>
                            <td>{{ $loc->bin ?? '—' }}</td>
                            <td><span class="badge badge-info">{{ $loc->type }}</span></td>
                            <td class="text-center">{{ $loc->capacity ?? '—' }}</td>
                            <td class="text-right pr-3">
                                <a href="{{ route('wms.locations.edit', $loc->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">{{ __('No locations yet. Create one to organise your warehouse.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($locations->hasPages())<div class="card-footer">{{ $locations->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
