@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Products') }}@endsection

@push('styles')
<style>
  .badge-stock-ok    { background: #dcfce7; color: #166534; }
  .badge-stock-low   { background: #fee2e2; color: #991b1b; }
  .barcode-cell { font-family: ui-monospace, Menlo, monospace; font-size: 12px; color: #475569; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex align-items-center">
                <div class="page-breadcrumb flex-grow-1">
                    <nav><ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Products') }}</li>
                    </ol></nav>
                </div>
                <a href="{{ route('wms.products.create') }}" class="btn btn-sm btn-primary">+ {{ __('New Product') }}</a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form class="form-inline" method="GET">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm mr-2 mb-1" placeholder="{{ __('Search name / SKU / barcode…') }}" style="min-width:240px;">
                <select name="merchant_id" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">{{ __('Any merchant') }}</option>
                    @foreach ($merchants as $m)
                        <option value="{{ $m->id }}" {{ request('merchant_id')==$m->id ? 'selected' : '' }}>{{ $m->business_name ?? ('#'.$m->id) }}</option>
                    @endforeach
                </select>
                <select name="hub_id" class="form-control form-control-sm mr-2 mb-1">
                    <option value="">{{ __('Any hub') }}</option>
                    @foreach ($hubs as $h)
                        <option value="{{ $h->id }}" {{ request('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
                <a href="{{ route('wms.products.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th>{{ __('Hub') }}</th>
                            <th>{{ __('Barcode') }}</th>
                            <th class="text-center">{{ __('On Hand') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-right pr-3">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $p)
                            @php $onHand = $p->total_qty; $low = $onHand <= (int)$p->reorder_point; @endphp
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td><strong>{{ $p->sku }}</strong></td>
                                <td>{{ $p->name }}</td>
                                <td>{{ optional($p->merchant)->business_name ?? '—' }}</td>
                                <td>{{ optional($p->hub)->name ?? '—' }}</td>
                                <td class="barcode-cell">{{ $p->barcode }}</td>
                                <td class="text-center"><strong>{{ $onHand }}</strong> <small class="text-muted">/{{ $p->reorder_point }}</small></td>
                                <td class="text-center">
                                    @if ($low)
                                        <span class="badge badge-stock-low">{{ __('LOW') }}</span>
                                    @else
                                        <span class="badge badge-stock-ok">{{ __('OK') }}</span>
                                    @endif
                                </td>
                                <td class="text-right pr-3">
                                    <a href="{{ route('wms.products.show', $p->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                    <a href="{{ route('wms.products.barcode', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">📊</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No products. Click New Product to add one.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($products->hasPages())
            <div class="card-footer">{{ $products->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
