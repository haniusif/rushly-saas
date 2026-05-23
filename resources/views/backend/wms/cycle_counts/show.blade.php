@extends('backend.partials.master')
@section('title'){{ $count->count_number }}@endsection
@push('styles')<style>
  .cc-pill { padding:3px 9px; border-radius:5px; font-size:12px; font-weight:600; text-transform:uppercase; }
  .cc-open        { background:#fff7ed; color:#9a3412; }
  .cc-in_progress { background:#e0f2fe; color:#075985; }
  .cc-completed   { background:#dcfce7; color:#166534; }
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:40%; color:#6b7280; font-size:13px; }
  .info-row dd { width:60%; margin:0; font-size:14px; font-weight:500; }
  .count-input { width:100px; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.cycle-counts.index') }}">{{ __('Cycle Counts') }}</a></li>
                <li class="breadcrumb-item active text-monospace">{{ $count->count_number }}</li>
            </ol></nav>
        </div>
        @if ($count->status === 'open')
            <form method="POST" action="{{ route('wms.cycle-counts.update', $count->id) }}" class="d-inline">
                @csrf @method('PUT')
                <input type="hidden" name="action" value="start">
                <button class="btn btn-sm btn-warning">▶ {{ __('Start count') }}</button>
            </form>
        @elseif ($count->status === 'in_progress')
            <form method="POST" action="{{ route('wms.cycle-counts.update', $count->id) }}" class="d-inline">
                @csrf @method('PUT')
                <input type="hidden" name="action" value="complete">
                <button class="btn btn-sm btn-success">✓ {{ __('Complete count') }}</button>
            </form>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 text-monospace">{{ $count->count_number }}</h5>
                    <span class="cc-pill cc-{{ $count->status }}">{{ ucfirst(str_replace('_',' ',$count->status)) }}</span>
                </div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Hub') }}</dt><dd>{{ optional($count->hub)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Scope') }}</dt><dd>{{ ucfirst($count->scope) }}{{ $count->zone ? ' / '.$count->zone : '' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Assigned') }}</dt><dd>{{ optional($count->assignedTo)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Started') }}</dt><dd>{{ $count->started_at?->toDateTimeString() ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Completed') }}</dt><dd>{{ $count->completed_at?->toDateTimeString() ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Rows to count') }}</dt><dd>{{ $stockRows->count() }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Count sheet') }}</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="thead-light"><tr>
                            <th>#</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th class="text-center">{{ __('Expected') }}</th>
                            <th class="text-center">{{ __('Counted') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($stockRows as $i => $s)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-monospace">{{ optional($s->product)->sku }}</td>
                                    <td>{{ optional($s->product)->name }}</td>
                                    <td class="text-monospace">{{ optional($s->location)->code }}</td>
                                    <td class="text-center">{{ $s->quantity }}</td>
                                    <td class="text-center">
                                        <input type="number" min="0" class="form-control form-control-sm count-input d-inline-block" placeholder="—">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No stock rows in this scope.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        {{ __('After counting, any discrepancies should be logged as Stock Adjustments — they go through the dual-approval gate automatically when ≥20%.') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
