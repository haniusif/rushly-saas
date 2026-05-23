@extends('backend.partials.master')
@section('title'){{ __('Warehouse Map') }}@endsection

@push('styles')
<style>
  .zone-block { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px; margin-bottom:14px; }
  .zone-title { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#475569; margin-bottom:10px; }
  .aisle-row  { display:flex; gap:14px; align-items:flex-start; margin-bottom:14px; }
  .aisle-label { width:60px; text-align:right; padding-top:8px; font-weight:700; color:#6b7280; }
  .slots { display:flex; flex-wrap:wrap; gap:6px; flex:1; }
  .slot { background:#f3f4f6; padding:6px 10px; border-radius:6px; font-family:ui-monospace,Menlo,monospace; font-size:12px; border:1px solid #e5e7eb; }
  .slot.standard { background:#eff6ff; border-color:#dbeafe; color:#1d4ed8; }
  .slot.bulk     { background:#fefce8; border-color:#fef3c7; color:#92400e; }
  .slot.cold     { background:#ecfeff; border-color:#cffafe; color:#155e75; }
  .slot.hazmat   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.locations.index') }}">{{ __('Locations') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Map') }}</li>
            </ol></nav>
        </div>
        <form method="GET" class="form-inline">
            <select name="hub_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                <option value="">{{ __('All hubs') }}</option>
                @foreach ($hubs as $h)
                    <option value="{{ $h->id }}" {{ request('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @forelse ($tree as $zone => $aisles)
        <div class="zone-block">
            <div class="zone-title">📍 {{ __('Zone') }} {{ $zone }}</div>
            @foreach ($aisles as $aisle => $locs)
                <div class="aisle-row">
                    <div class="aisle-label">{{ $aisle }}</div>
                    <div class="slots">
                        @foreach ($locs as $loc)
                            <a class="slot {{ $loc->type }}" href="{{ route('wms.locations.show', $loc->id) }}"
                               title="{{ $loc->code }} — {{ $loc->type }}{{ $loc->capacity ? ' · cap '.$loc->capacity : '' }}">
                                {{ $loc->rack }}/{{ $loc->shelf }}{{ $loc->bin ? '/'.$loc->bin : '' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">
            {{ __('No locations yet. Add some from the Locations page to see them mapped here.') }}
        </div></div>
    @endforelse
</div>
@endsection
