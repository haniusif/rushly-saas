@extends('backend.partials.master')
@section('title')
    {{ __('Abnormal Shipments') }}
@endsection

@push('styles')
<style>
  .sev-card { border-radius: 10px; padding: 18px 20px; color: #fff; }
  .sev-card .num { font-size: 30px; font-weight: 800; line-height: 1; }
  .sev-card .lbl { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.92; margin-top: 4px; }
  .sc-warn { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .sc-dang { background: linear-gradient(135deg, #ef4444, #dc2626); }
  .sc-crit { background: linear-gradient(135deg, #7c2d12, #991b1b); }
  .sc-lost { background: linear-gradient(135deg, #4b5563, #1f2937); }

  .sev-badge { padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
  .sev-warning  { background: #fef3c7; color: #92400e; }
  .sev-danger   { background: #fee2e2; color: #991b1b; }
  .sev-critical { background: #1f2937; color: #fcd34d; }

  .stat-pill { padding: 2px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; }
  .stat-open          { background: #fff7ed; color: #9a3412; }
  .stat-investigating { background: #e0f2fe; color: #075985; }
  .stat-resolved      { background: #dcfce7; color: #166534; }
  .stat-closed_lost   { background: #f3f4f6; color: #1f2937; }

  .days-cell { font-variant-numeric: tabular-nums; font-weight: 600; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex align-items-center">
                <div class="page-breadcrumb flex-grow-1">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Abnormal Shipments') }}</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('abnormal.settings') }}" class="btn btn-sm btn-outline-secondary">⚙ {{ __('Settings') }}</a>
            </div>
        </div>
    </div>

    {{-- ===== Summary cards ===== --}}
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="sev-card sc-warn"><div class="num">{{ $summary['stalled_3'] }}</div><div class="lbl">{{ __('Stalled 3+ days') }}</div></div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="sev-card sc-dang"><div class="num">{{ $summary['stalled_5'] }}</div><div class="lbl">{{ __('Stalled 5+ days') }}</div></div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="sev-card sc-crit"><div class="num">{{ $summary['stalled_7'] }}</div><div class="lbl">{{ __('Stalled 7+ days (critical)') }}</div></div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="sev-card sc-lost"><div class="num">{{ $summary['closed_lost'] }}</div><div class="lbl">{{ __('Closed as Lost') }}</div></div>
        </div>
    </div>

    {{-- ===== Filter bar ===== --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body py-3">
                    <form method="GET" class="form-inline">
                        <label class="mr-2 mb-1 text-muted small">{{ __('Duration') }}:</label>
                        <select name="min_days" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('All') }}</option>
                            <option value="3" {{ request('min_days')=='3' ? 'selected' : '' }}>3+</option>
                            <option value="5" {{ request('min_days')=='5' ? 'selected' : '' }}>5+</option>
                            <option value="7" {{ request('min_days')=='7' ? 'selected' : '' }}>7+</option>
                        </select>
                        <select name="severity" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('Any severity') }}</option>
                            <option value="warning"  {{ request('severity')=='warning'  ? 'selected' : '' }}>Warning</option>
                            <option value="danger"   {{ request('severity')=='danger'   ? 'selected' : '' }}>Danger</option>
                            <option value="critical" {{ request('severity')=='critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                        <select name="status" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('All status') }}</option>
                            @foreach (['open','investigating','resolved','closed_lost'] as $s)
                                <option value="{{ $s }}" {{ request('status')==$s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                        <select name="assigned_to" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('Any investigator') }}</option>
                            @foreach ($deliverymans as $dm)
                                <option value="{{ $dm->user_id ?? $dm->id }}" {{ request('assigned_to')==($dm->user_id ?? $dm->id) ? 'selected' : '' }}>
                                    {{ $dm->user->name ?? $dm->name ?? ('#'.$dm->id) }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
                        <a href="{{ route('abnormal.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
                        <span class="ml-auto small text-muted mb-1">{{ __('Detection threshold') }}: <strong>{{ $threshold }} {{ __('days') }}</strong></span>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== List ===== --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Tracking') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Last Event') }}</th>
                                    <th class="text-center">{{ __('Stale Days') }}</th>
                                    <th>{{ __('Severity') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($abnormals as $a)
                                    <tr>
                                        <td>{{ $a->id }}</td>
                                        <td>{{ optional($a->parcel)->tracking_id ?? ('#'.$a->parcel_id) }}</td>
                                        <td>{{ optional($a->parcel)->customer_name ?? '—' }}</td>
                                        <td><small class="text-muted">{{ $a->last_event_at?->diffForHumans() }}</small></td>
                                        <td class="text-center days-cell">{{ $a->stale_days }}</td>
                                        <td><span class="sev-badge sev-{{ $a->severity }}">{{ $a->severity }}</span></td>
                                        <td><span class="stat-pill stat-{{ $a->status }}">{{ ucwords(str_replace('_',' ',$a->status)) }}</span></td>
                                        <td class="text-right pr-3">
                                            <a href="{{ route('abnormal.show', $a->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No abnormal shipments. The hourly cron will surface them as they appear.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($abnormals->hasPages())
                    <div class="card-footer">{{ $abnormals->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
