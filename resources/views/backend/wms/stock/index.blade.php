@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Stock') }}@endsection

@push('styles')
<style>
  .row-low { background: #fef2f2; }
  .row-low td { font-weight: 600; }
  .pill-stock-ok  { background:#dcfce7; color:#166534; padding:2px 8px; border-radius:5px; font-size:11px; }
  .pill-stock-low { background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:5px; font-size:11px; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Stock') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.stock.export', request()->all()) }}" class="btn btn-sm btn-outline-secondary">⬇ {{ __('Export CSV') }}</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form class="form-inline" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm mr-2 mb-1" placeholder="{{ __('Search SKU / product…') }}" style="min-width:240px;">
            <select name="merchant_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any merchant') }}</option>
                @foreach ($merchants as $m)<option value="{{ $m->id }}" {{ request('merchant_id')==$m->id ? 'selected' : '' }}>{{ $m->business_name ?? ('#'.$m->id) }}</option>@endforeach
            </select>
            <select name="hub_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any hub') }}</option>
                @foreach ($hubs as $h)<option value="{{ $h->id }}" {{ request('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
            </select>
            <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
            <a href="{{ route('wms.stock.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>#</th>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Location') }}</th>
                    <th class="text-center">{{ __('Qty') }}</th>
                    <th class="text-center">{{ __('Reserved') }}</th>
                    <th class="text-center">{{ __('Available') }}</th>
                    <th>{{ __('Batch') }}</th>
                    <th>{{ __('Expiry') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $low = $r->product && $r->quantity <= (int)$r->product->reorder_point; @endphp
                        <tr class="{{ $low ? 'row-low' : '' }}">
                            <td>{{ $r->id }}</td>
                            <td><a href="{{ route('wms.products.show', $r->product_id) }}" class="text-monospace">{{ optional($r->product)->sku }}</a></td>
                            <td>{{ optional($r->product)->name }}</td>
                            <td class="text-monospace">
                                @if ($r->location)<a href="{{ route('wms.locations.show', $r->location_id) }}">{{ $r->location->code }}</a>@else — @endif
                            </td>
                            <td class="text-center">{{ $r->quantity }}</td>
                            <td class="text-center">{{ $r->reserved_qty }}</td>
                            <td class="text-center"><strong>{{ max(0, $r->quantity - $r->reserved_qty) }}</strong>
                                @if ($low) <span class="pill-stock-low ml-1">LOW</span> @endif
                            </td>
                            <td>{{ $r->batch_number ?? '—' }}</td>
                            <td>{{ $r->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No stock records. Create a GRN to receive inventory.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($rows->hasPages())<div class="card-footer">{{ $rows->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
