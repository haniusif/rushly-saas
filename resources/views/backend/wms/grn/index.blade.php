@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Receiving (GRN)') }}@endsection

@push('styles')
<style>
  .grn-pill { padding:3px 9px; border-radius:5px; font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; }
  .gp-draft        { background:#f3f4f6; color:#374151; }
  .gp-in_progress  { background:#e0f2fe; color:#075985; }
  .gp-completed    { background:#dcfce7; color:#166534; }
  .gp-discrepancy  { background:#fee2e2; color:#991b1b; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Receiving (GRN)') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.grn.create') }}" class="btn btn-sm btn-primary">+ {{ __('New GRN') }}</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-inline">
            <select name="status" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('All status') }}</option>
                @foreach (['draft','in_progress','completed','discrepancy'] as $s)
                    <option value="{{ $s }}" {{ request('status')==$s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="merchant_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any merchant') }}</option>
                @foreach ($merchants as $m)<option value="{{ $m->id }}" {{ request('merchant_id')==$m->id ? 'selected' : '' }}>{{ $m->business_name ?? ('#'.$m->id) }}</option>@endforeach
            </select>
            <select name="hub_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any hub') }}</option>
                @foreach ($hubs as $h)<option value="{{ $h->id }}" {{ request('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
            </select>
            <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
            <a href="{{ route('wms.grn.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>#</th>
                    <th>{{ __('GRN #') }}</th>
                    <th>{{ __('Merchant') }}</th>
                    <th>{{ __('Hub') }}</th>
                    <th>{{ __('Received By') }}</th>
                    <th class="text-center">{{ __('Items') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($grns as $g)
                        <tr>
                            <td>{{ $g->id }}</td>
                            <td><strong class="text-monospace">{{ $g->grn_number }}</strong></td>
                            <td>{{ optional($g->merchant)->business_name ?? '—' }}</td>
                            <td>{{ optional($g->hub)->name ?? '—' }}</td>
                            <td>{{ optional($g->receivedBy)->name ?? '—' }}</td>
                            <td class="text-center">{{ $g->items_count ?? $g->items()->count() }}</td>
                            <td><span class="grn-pill gp-{{ $g->status }}">{{ ucwords(str_replace('_',' ',$g->status)) }}</span></td>
                            <td><small class="text-muted">{{ $g->created_at?->diffForHumans() }}</small></td>
                            <td class="text-right pr-3">
                                <a href="{{ route('wms.grn.show', $g->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No GRNs yet. Click New GRN to receive a shipment.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($grns->hasPages())<div class="card-footer">{{ $grns->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
