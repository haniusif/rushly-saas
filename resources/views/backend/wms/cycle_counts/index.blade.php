@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Cycle Counts') }}@endsection
@push('styles')<style>
  .cc-pill { padding:3px 8px; border-radius:5px; font-size:11.5px; font-weight:600; text-transform:uppercase; }
  .cc-open        { background:#fff7ed; color:#9a3412; }
  .cc-in_progress { background:#e0f2fe; color:#075985; }
  .cc-completed   { background:#dcfce7; color:#166534; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Cycle Counts') }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.cycle-counts.create') }}" class="btn btn-sm btn-primary">+ {{ __('New Count') }}</a>
    </div>

    <div class="card"><div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light"><tr>
                <th>#</th><th>{{ __('Count #') }}</th><th>{{ __('Hub') }}</th>
                <th>{{ __('Scope') }}</th><th>{{ __('Assigned') }}</th>
                <th>{{ __('Status') }}</th><th>{{ __('Started') }}</th>
                <th>{{ __('Completed') }}</th>
                <th class="text-right pr-3">{{ __('Actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($counts as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td><strong class="text-monospace">{{ $c->count_number }}</strong></td>
                        <td>{{ optional($c->hub)->name ?? '—' }}</td>
                        <td>{{ ucfirst($c->scope) }}{{ $c->zone ? ' / '.$c->zone : '' }}</td>
                        <td>{{ optional($c->assignedTo)->name ?? '—' }}</td>
                        <td><span class="cc-pill cc-{{ $c->status }}">{{ ucfirst(str_replace('_',' ',$c->status)) }}</span></td>
                        <td><small class="text-muted">{{ $c->started_at?->diffForHumans() ?? '—' }}</small></td>
                        <td><small class="text-muted">{{ $c->completed_at?->diffForHumans() ?? '—' }}</small></td>
                        <td class="text-right pr-3">
                            <a href="{{ route('wms.cycle-counts.show', $c->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No cycle counts yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($counts->hasPages())<div class="card-footer">{{ $counts->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
