@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Adjustments') }}@endsection
@push('styles')<style>
  .delta-up   { color:#166534; font-weight:700; }
  .delta-down { color:#991b1b; font-weight:700; }
  .ap-approved        { background:#dcfce7; color:#166534; padding:2px 8px; border-radius:5px; font-size:11px; }
  .ap-pending_approval{ background:#fff7ed; color:#9a3412; padding:2px 8px; border-radius:5px; font-size:11px; }
  .ap-rejected        { background:#f3f4f6; color:#6b7280; padding:2px 8px; border-radius:5px; font-size:11px; }
  .reason-pill        { background:#eff6ff; color:#1e40af; padding:2px 8px; border-radius:4px; font-size:11px; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Adjustments') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.adjustments.create') }}" class="btn btn-sm btn-primary">+ {{ __('New Adjustment') }}</a>
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-inline">
            <select name="reason" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any reason') }}</option>
                @foreach ($reasons as $r)<option value="{{ $r }}" {{ request('reason')==$r ? 'selected' : '' }}>{{ str_replace('_',' ',$r) }}</option>@endforeach
            </select>
            <select name="status" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any approval status') }}</option>
                <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Approved</option>
                <option value="pending_approval" {{ request('status')=='pending_approval' ? 'selected' : '' }}>Pending approval</option>
                <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
            <a href="{{ route('wms.adjustments.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>#</th><th>{{ __('SKU') }}</th><th>{{ __('Product') }}</th>
                    <th>{{ __('Location') }}</th>
                    <th class="text-center">{{ __('Before') }}</th>
                    <th class="text-center">{{ __('Change') }}</th>
                    <th class="text-center">{{ __('After') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th>{{ __('Approval') }}</th>
                    <th>{{ __('By') }}</th>
                    <th>{{ __('When') }}</th>
                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($adjustments as $a)
                        @php $cls = $a->quantity_change > 0 ? 'delta-up' : ($a->quantity_change < 0 ? 'delta-down' : ''); @endphp
                        <tr>
                            <td>{{ $a->id }}</td>
                            <td class="text-monospace">{{ optional($a->product)->sku }}</td>
                            <td>{{ optional($a->product)->name }}</td>
                            <td class="text-monospace">{{ optional($a->location)->code }}</td>
                            <td class="text-center">{{ $a->quantity_before }}</td>
                            <td class="text-center {{ $cls }}">{{ $a->quantity_change > 0 ? '+' : '' }}{{ $a->quantity_change }}</td>
                            <td class="text-center">{{ $a->quantity_after }}</td>
                            <td><span class="reason-pill">{{ str_replace('_',' ',$a->reason) }}</span></td>
                            <td><span class="ap-{{ $a->approval_status }}">{{ ucwords(str_replace('_',' ',$a->approval_status)) }}</span></td>
                            <td>{{ optional($a->adjustedBy)->name ?? '—' }}</td>
                            <td><small class="text-muted">{{ $a->created_at?->diffForHumans() }}</small></td>
                            <td class="text-right pr-3">
                                <a href="{{ route('wms.adjustments.show', $a->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-4">{{ __('No adjustments yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($adjustments->hasPages())<div class="card-footer">{{ $adjustments->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
