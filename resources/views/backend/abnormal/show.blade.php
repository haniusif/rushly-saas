@extends('backend.partials.master')
@section('title')
    {{ __('abnormal.singular') }} #{{ $abnormal->id }}
@endsection

@push('styles')
<style>
  .info-row { display: flex; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
  .info-row dt { width: 38%; color: #6b7280; font-size: 13px; }
  .info-row dd { width: 62%; margin: 0; font-size: 14px; font-weight: 500; }

  .progress-stale { height: 18px; border-radius: 6px; background: #f3f4f6; overflow: hidden; position: relative; }
  .progress-stale .fill { height: 100%; background: linear-gradient(90deg, #fbbf24, #ef4444); transition: width .4s; }
  .progress-stale .lbl { position: absolute; top: 0; left: 0; right: 0; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #111; }

  .sev-badge { padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
  .sev-warning  { background: #fef3c7; color: #92400e; }
  .sev-danger   { background: #fee2e2; color: #991b1b; }
  .sev-critical { background: #1f2937; color: #fcd34d; }

  .stat-pill { padding: 3px 9px; border-radius: 5px; font-size: 12px; font-weight: 600; }
  .stat-open          { background: #fff7ed; color: #9a3412; }
  .stat-investigating { background: #e0f2fe; color: #075985; }
  .stat-resolved      { background: #dcfce7; color: #166534; }
  .stat-closed_lost   { background: #f3f4f6; color: #1f2937; }

  .timeline { border-left: 2px solid #e5e7eb; padding-left: 16px; }
  .timeline .tl-item { position: relative; margin-bottom: 14px; }
  .timeline .tl-item::before { content: ""; width: 10px; height: 10px; background: #6366f1; border-radius: 50%; position: absolute; left: -22px; top: 6px; }

  .action-chip { display: inline-flex; align-items: center; padding: 8px 14px; margin-right: 6px; margin-bottom: 6px; border-radius: 999px; background: #f9fafb; border: 1px solid #e5e7eb; font-size: 13px; cursor: pointer; }
  .action-chip:hover { background: #f3f4f6; border-color: #d1d5db; }
  .action-chip button { all: unset; cursor: pointer; }
</style>
@endpush

@section('maincontent')
@php
    $stale  = (int) $abnormal->stale_days;
    $cap    = max(1, (int) $autoEscalate);
    $pct    = min(100, round(($stale / $cap) * 100));
@endphp

<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('abnormal.index') }}" class="breadcrumb-link">{{ __('abnormal.title') }}</a></li>
                            <li class="breadcrumb-item active">#{{ $abnormal->id }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- LEFT: parcel + stale progress + timeline --}}
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ optional($abnormal->parcel)->tracking_id ?? ('Parcel #'.$abnormal->parcel_id) }}</h5>
                    <div>
                        <span class="sev-badge sev-{{ $abnormal->severity }}">{{ $abnormal->severity }}</span>
                        <span class="stat-pill stat-{{ $abnormal->status }} ml-1">{{ ucwords(str_replace('_',' ',$abnormal->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('levels.customer') }}</dt><dd>{{ optional($abnormal->parcel)->customer_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('levels.phone') }}</dt><dd>{{ optional($abnormal->parcel)->customer_phone ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('levels.merchant') }}</dt><dd>{{ optional($abnormal->parcel?->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('abnormal.detected') }}</dt><dd>{{ $abnormal->detected_at?->diffForHumans() }}</dd></div>
                        <div class="info-row"><dt>{{ __('abnormal.last_event') }}</dt><dd>{{ $abnormal->last_event_at?->toDateTimeString() }} <small class="text-muted">({{ $abnormal->last_event_at?->diffForHumans() }})</small></dd></div>
                        <div class="info-row"><dt>{{ __('abnormal.assigned_to') }}</dt><dd>{{ optional($abnormal->assignedTo)->name ?? __('abnormal.nobody_yet') }}</dd></div>
                    </dl>

                    <h6 class="text-uppercase text-muted small mt-4 mb-2">{{ __('abnormal.stale_progress') }}</h6>
                    <div class="progress-stale">
                        <div class="fill" style="width: {{ $pct }}%;"></div>
                        <div class="lbl">{{ $stale }} {{ __('of') }} {{ $autoEscalate }} {{ __('days (auto-escalation threshold)') }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('abnormal.event_timeline') }}</h6></div>
                <div class="card-body">
                    @if ($events->isEmpty())
                        <p class="text-muted mb-0">{{ __('abnormal.no_events') }}</p>
                    @else
                        <div class="timeline">
                            @foreach ($events as $e)
                                <div class="tl-item">
                                    <strong>{{ \App\Support\ParcelStatusHelper::label((int) $e->parcel_status) }}</strong>
                                    <div class="small text-muted">{{ $e->created_at?->toDateTimeString() }} @if ($e->hub_id) — Hub #{{ $e->hub_id }} @endif</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: actions + history --}}
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">{{ __('abnormal.investigation') }}</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('abnormal.assign', $abnormal->id) }}" class="mb-3">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label class="small text-muted">{{ __('abnormal.assign_to_investigator') }}</label>
                            <div class="d-flex">
                                <select name="assigned_to" class="form-control mr-2">
                                    <option value="">—</option>
                                    @foreach ($deliverymans as $dm)
                                        <option value="{{ $dm->user_id ?? $dm->id }}" {{ $abnormal->assigned_to == ($dm->user_id ?? $dm->id) ? 'selected' : '' }}>
                                            {{ $dm->user->name ?? $dm->name ?? ('#'.$dm->id) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary">{{ __('abnormal.assign') }}</button>
                            </div>
                        </div>
                    </form>

                    <hr>

                    <div class="small text-muted mb-2">{{ __('abnormal.take_action') }}</div>
                    <form method="POST" action="{{ route('abnormal.action', $abnormal->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="create_ndr" />
                        <span class="action-chip"><button>🔴 {{ __('abnormal.create_ndr') }}</button></span>
                    </form>
                    <form method="POST" action="{{ route('abnormal.action', $abnormal->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="log_contact" />
                        <input type="hidden" name="note" value="{{ __('abnormal.customer_contact_logged') }}" />
                        <span class="action-chip"><button>📞 {{ __('abnormal.log_customer_contact') }}</button></span>
                    </form>
                    <form method="POST" action="{{ route('abnormal.action', $abnormal->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="action" value="escalate" />
                        <span class="action-chip"><button>⬆ {{ __('abnormal.escalate') }}</button></span>
                    </form>
                    <form method="POST" action="{{ route('abnormal.action', $abnormal->id) }}" class="d-inline"
                          onsubmit="return confirm('{{ __('abnormal.close_as_lost_confirm') }}');">
                        @csrf
                        <input type="hidden" name="action" value="close_lost" />
                        <span class="action-chip" style="background:#fee2e2; border-color:#fca5a5; color:#991b1b;"><button>🚫 {{ __('abnormal.close_as_lost') }}</button></span>
                    </form>

                    @if ($abnormal->status !== 'resolved' && $abnormal->status !== 'closed_lost')
                        <hr>
                        <form method="POST" action="{{ route('abnormal.resolve', $abnormal->id) }}">
                            @csrf @method('PUT')
                            <textarea name="note" rows="2" class="form-control mb-2" placeholder="{{ __('abnormal.resolution_note_placeholder') }}"></textarea>
                            <button class="btn btn-success btn-block">{{ __('abnormal.mark_resolved') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($abnormal->resolution_note)
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">{{ __('abnormal.notes') }}</h6></div>
                    <div class="card-body">
                        <pre class="mb-0" style="white-space: pre-wrap; font-size: 13px;">{{ $abnormal->resolution_note }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
