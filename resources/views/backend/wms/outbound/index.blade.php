@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Outbound') }}@endsection
@push('styles')
<style>
  .ob-pill { padding:3px 8px; border-radius:5px; font-size:11.5px; font-weight:600; text-transform:uppercase; }
  .ob-pending   { background:#fff7ed; color:#9a3412; }
  .ob-processing{ background:#e0f2fe; color:#075985; }
  .ob-completed { background:#dcfce7; color:#166534; }
  .ob-cancelled { background:#f3f4f6; color:#6b7280; }
  .type-pill { padding:2px 8px; border-radius:4px; font-size:11px; background:#f3f4f6; color:#374151; }
</style>
@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Outbound') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.outbound.create') }}" class="btn btn-sm btn-primary">+ {{ __('New Outbound') }}</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-inline">
            <select name="type" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any type') }}</option>
                @foreach (['fulfillment','manual','transfer','return_to_merchant'] as $t)
                    <option value="{{ $t }}" {{ request('type')==$t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any status') }}</option>
                @foreach (['pending','processing','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status')==$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="merchant_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any merchant') }}</option>
                @foreach ($merchants as $m)<option value="{{ $m->id }}" {{ request('merchant_id')==$m->id ? 'selected' : '' }}>{{ $m->business_name ?? ('#'.$m->id) }}</option>@endforeach
            </select>
            <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
            <a href="{{ route('wms.outbound.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>#</th><th>{{ __('Outbound #') }}</th><th>{{ __('Type') }}</th>
                    <th>{{ __('Merchant') }}</th><th>{{ __('Hub') }}</th>
                    <th>{{ __('Processed By') }}</th><th>{{ __('Status') }}</th>
                    <th>{{ __('Completed') }}</th>
                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($outbounds as $o)
                        <tr>
                            <td>{{ $o->id }}</td>
                            <td><strong class="text-monospace">{{ $o->outbound_number }}</strong></td>
                            <td><span class="type-pill">{{ str_replace('_', ' ', $o->type) }}</span></td>
                            <td>{{ optional($o->merchant)->business_name ?? '—' }}</td>
                            <td>{{ optional($o->hub)->name ?? '—' }}</td>
                            <td>{{ optional($o->processedBy)->name ?? '—' }}</td>
                            <td><span class="ob-pill ob-{{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
                            <td><small class="text-muted">{{ $o->completed_at?->diffForHumans() ?? '—' }}</small></td>
                            <td class="text-right pr-3">
                                <a href="{{ route('wms.outbound.show', $o->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No outbound records yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($outbounds->hasPages())<div class="card-footer">{{ $outbounds->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
