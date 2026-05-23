@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Damage Reports') }}@endsection
@push('styles')<style>
  .cause-pill { background:#fef2f2; color:#991b1b; padding:2px 8px; border-radius:4px; font-size:11px; }
  .action-pill { background:#eff6ff; color:#1e40af; padding:2px 8px; border-radius:4px; font-size:11px; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Damage Reports') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.damage.create') }}" class="btn btn-sm btn-primary">+ {{ __('Report Damage') }}</a>
    </div>

    <div class="card"><div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light"><tr>
                <th>#</th><th>{{ __('SKU') }}</th><th>{{ __('Product') }}</th>
                <th>{{ __('Location') }}</th>
                <th class="text-center">{{ __('Qty') }}</th>
                <th>{{ __('Cause') }}</th>
                <th>{{ __('Action') }}</th>
                <th>{{ __('Reported By') }}</th>
                <th>{{ __('When') }}</th>
                <th class="text-right pr-3">{{ __('Actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($damages as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td class="text-monospace">{{ optional($d->product)->sku }}</td>
                        <td>{{ optional($d->product)->name }}</td>
                        <td class="text-monospace">{{ optional($d->location)->code }}</td>
                        <td class="text-center"><strong>{{ $d->quantity_damaged }}</strong></td>
                        <td><span class="cause-pill">{{ str_replace('_',' ',$d->cause) }}</span></td>
                        <td>@if ($d->action_taken)<span class="action-pill">{{ str_replace('_',' ',$d->action_taken) }}</span>@else — @endif</td>
                        <td>{{ optional($d->reportedBy)->name ?? '—' }}</td>
                        <td><small class="text-muted">{{ $d->created_at?->diffForHumans() }}</small></td>
                        <td class="text-right pr-3">
                            <a href="{{ route('wms.damage.show', $d->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No damage reports.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($damages->hasPages())<div class="card-footer">{{ $damages->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
