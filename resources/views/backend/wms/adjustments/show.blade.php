@extends('backend.partials.master')
@section('title'){{ __('Adjustment') }} #{{ $adjustment->id }}@endsection
@push('styles')<style>
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:40%; color:#6b7280; font-size:13px; }
  .info-row dd { width:60%; margin:0; font-size:14px; font-weight:500; }
  .ap-approved        { background:#dcfce7; color:#166534; padding:3px 10px; border-radius:6px; font-size:13px; }
  .ap-pending_approval{ background:#fff7ed; color:#9a3412; padding:3px 10px; border-radius:6px; font-size:13px; }
  .ap-rejected        { background:#f3f4f6; color:#6b7280; padding:3px 10px; border-radius:6px; font-size:13px; }
  .delta { font-size:32px; font-weight:800; }
  .delta.up   { color:#166534; }
  .delta.down { color:#991b1b; }
  .gate-warn { background:#fff7ed; border-left:4px solid #f59e0b; padding:14px; border-radius:6px; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.adjustments.index') }}">{{ __('Adjustments') }}</a></li>
            <li class="breadcrumb-item active">#{{ $adjustment->id }}</li>
        </ol></nav>
    </div></div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0">{{ __('Adjustment') }} #{{ $adjustment->id }}</h5>
                    <span class="ap-{{ $adjustment->approval_status }}">{{ ucwords(str_replace('_',' ',$adjustment->approval_status)) }}</span>
                </div>
                <div class="card-body">
                    <div class="text-center my-3">
                        <div class="text-muted small">{{ $adjustment->quantity_before }} → {{ $adjustment->quantity_after }}</div>
                        <div class="delta {{ $adjustment->quantity_change > 0 ? 'up' : ($adjustment->quantity_change < 0 ? 'down' : '') }}">
                            {{ $adjustment->quantity_change > 0 ? '+' : '' }}{{ $adjustment->quantity_change }}
                        </div>
                        <div class="text-muted small">({{ $adjustment->percent_change }}% {{ __('of starting stock') }})</div>
                    </div>

                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Product') }}</dt><dd>{{ optional($adjustment->product)->sku }} — {{ optional($adjustment->product)->name }}</dd></div>
                        <div class="info-row"><dt>{{ __('Location') }}</dt><dd class="text-monospace">{{ optional($adjustment->location)->code }}</dd></div>
                        <div class="info-row"><dt>{{ __('Reason') }}</dt><dd>{{ str_replace('_',' ',$adjustment->reason) }}</dd></div>
                        <div class="info-row"><dt>{{ __('Reference') }}</dt><dd>{{ $adjustment->reference ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Submitted by') }}</dt><dd>{{ optional($adjustment->adjustedBy)->name ?? '—' }} <small class="text-muted">{{ $adjustment->created_at?->diffForHumans() }}</small></dd></div>
                        @if ($adjustment->approval_status !== 'pending_approval')
                            <div class="info-row"><dt>{{ __('Approval by') }}</dt><dd>{{ optional($adjustment->approvedBy)->name ?? '—' }} <small class="text-muted">{{ $adjustment->approved_at?->diffForHumans() }}</small></dd></div>
                        @endif
                    </dl>

                    @if ($adjustment->notes)
                        <h6 class="text-uppercase text-muted small mt-3">{{ __('Notes') }}</h6>
                        <p class="mb-0">{{ $adjustment->notes }}</p>
                    @endif

                    @if ($adjustment->photo)
                        <h6 class="text-uppercase text-muted small mt-3">{{ __('Evidence') }}</h6>
                        <a href="{{ url($adjustment->photo) }}" target="_blank">
                            <img src="{{ url($adjustment->photo) }}" style="max-width:280px;border-radius:8px;border:1px solid #e5e7eb;">
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if ($adjustment->approval_status === 'pending_approval')
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">{{ __('Dual-approval gate') }}</h6></div>
                    <div class="card-body">
                        <div class="gate-warn mb-3">
                            ⚠ {{ __('This adjustment is ±:p% of stock — exceeding the 20% threshold. A different supervisor (not the submitter) must approve before stock is updated.', ['p' => $adjustment->percent_change]) }}
                        </div>

                        @if (auth()->id() === (int) $adjustment->adjusted_by)
                            <div class="alert alert-secondary mb-0">
                                {{ __('You submitted this adjustment. A different supervisor must approve.') }}
                            </div>
                        @else
                            <form method="POST" action="{{ route('wms.adjustments.approve', $adjustment->id) }}" class="mb-2">
                                @csrf @method('PUT')
                                <button class="btn btn-success btn-block">✓ {{ __('Approve & apply') }}</button>
                            </form>
                            <form method="POST" action="{{ route('wms.adjustments.reject', $adjustment->id) }}">
                                @csrf @method('PUT')
                                <textarea name="note" rows="2" class="form-control mb-2" placeholder="{{ __('Rejection reason (optional)') }}"></textarea>
                                <button class="btn btn-outline-danger btn-block">✕ {{ __('Reject') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @elseif ($adjustment->approval_status === 'approved')
                <div class="card">
                    <div class="card-body text-center text-success">
                        <div style="font-size:48px;">✓</div>
                        <h5 class="mb-0">{{ __('Approved & applied') }}</h5>
                        <p class="text-muted mt-1 mb-0">{{ __('Stock was updated.') }}</p>
                    </div>
                </div>
            @elseif ($adjustment->approval_status === 'rejected')
                <div class="card">
                    <div class="card-body text-center text-muted">
                        <div style="font-size:48px;">✕</div>
                        <h5 class="mb-0">{{ __('Rejected') }}</h5>
                        <p class="mt-1 mb-0">{{ __('Stock was not changed.') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
