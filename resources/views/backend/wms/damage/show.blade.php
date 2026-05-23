@extends('backend.partials.master')
@section('title'){{ __('Damage Report') }} #{{ $report->id }}@endsection
@push('styles')<style>
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:40%; color:#6b7280; font-size:13px; }
  .info-row dd { width:60%; margin:0; font-size:14px; font-weight:500; }
  .ph-grid { display:flex; gap:8px; flex-wrap:wrap; }
  .ph-grid img { max-width:180px; border-radius:6px; border:1px solid #e5e7eb; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.damage.index') }}">{{ __('Damage Reports') }}</a></li>
            <li class="breadcrumb-item active">#{{ $report->id }}</li>
        </ol></nav>
    </div></div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Damage Report') }} #{{ $report->id }}</h5></div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Product') }}</dt><dd>{{ optional($report->product)->sku }} — {{ optional($report->product)->name }}</dd></div>
                        <div class="info-row"><dt>{{ __('Location') }}</dt><dd class="text-monospace">{{ optional($report->location)->code }}</dd></div>
                        <div class="info-row"><dt>{{ __('Quantity Damaged') }}</dt><dd><strong>{{ $report->quantity_damaged }}</strong></dd></div>
                        <div class="info-row"><dt>{{ __('Cause') }}</dt><dd>{{ str_replace('_',' ',$report->cause) }}</dd></div>
                        <div class="info-row"><dt>{{ __('Action Taken') }}</dt><dd>{{ $report->action_taken ? str_replace('_',' ',$report->action_taken) : '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Reported By') }}</dt><dd>{{ optional($report->reportedBy)->name }} <small class="text-muted">{{ $report->created_at?->diffForHumans() }}</small></dd></div>
                    </dl>

                    @if ($report->notes)
                        <h6 class="text-uppercase text-muted small mt-3">{{ __('Notes') }}</h6>
                        <p class="mb-0">{{ $report->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            @if (!empty($report->photos))
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">{{ __('Photos') }}</h6></div>
                    <div class="card-body">
                        <div class="ph-grid">
                            @foreach ($report->photos as $p)
                                <a href="{{ url($p) }}" target="_blank"><img src="{{ url($p) }}" alt="damage"></a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
