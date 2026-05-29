@extends('backend.partials.master')
@section('title')
    {{ __('NDR') }} {{ __('levels.list') }}
@endsection

@push('styles')
<style>
  .stat-card { border-radius: 10px; padding: 18px 20px; color: #fff; }
  .stat-card .num { font-size: 28px; font-weight: 700; line-height: 1; }
  .stat-card .lbl { font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.9; margin-top: 4px; }
  .sc-today    { background: linear-gradient(135deg, #4f46e5, #6366f1); }
  .sc-open     { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .sc-progress { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
  .sc-resolved { background: linear-gradient(135deg, #10b981, #059669); }
  .sc-rate     { background: linear-gradient(135deg, #ef4444, #b91c1c); }

  .attempt-badge { padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
  .att-1 { background: #fef3c7; color: #92400e; }
  .att-2 { background: #ffedd5; color: #9a3412; }
  .att-3 { background: #fee2e2; color: #991b1b; }

  .nstatus { padding: 3px 9px; border-radius: 5px; font-size: 11.5px; font-weight: 600; }
  .ns-open        { background: #fff7ed; color: #9a3412; }
  .ns-in_progress { background: #e0f2fe; color: #075985; }
  .ns-resolved    { background: #dcfce7; color: #166534; }
  .ns-returned    { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('NDR') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('levels.list') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Stats cards ===== --}}
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card sc-today">
                <div class="num">{{ $stats['today'] ?? 0 }}</div>
                <div class="lbl">{{ __("Today's NDRs") }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card sc-open">
                <div class="num">{{ ($stats['open'] ?? 0) + ($stats['in_progress'] ?? 0) }}</div>
                <div class="lbl">{{ __('Pending') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card sc-resolved">
                <div class="num">{{ $stats['resolved'] ?? 0 }}</div>
                <div class="lbl">{{ __('Resolved') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="stat-card sc-rate">
                <div class="num">{{ $stats['return_rate'] ?? 0 }}%</div>
                <div class="lbl">{{ __('Return Rate') }}</div>
            </div>
        </div>
    </div>

    {{-- ===== Filters ===== --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body py-3">
                    <form method="GET" class="form-inline" id="ndrFilters">
                        <select name="status" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('All status') }}</option>
                            @foreach (['open','in_progress','resolved','returned'] as $s)
                                <option value="{{ $s }}" {{ request('status')==$s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                        <select name="failure_reason" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('All reasons') }}</option>
                            @foreach ($failureReasons as $key => $label)
                                <option value="{{ $key }}" {{ request('failure_reason')==$key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="deliveryman_id" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">{{ __('Any deliveryman') }}</option>
                            @foreach ($deliverymans as $dm)
                                <option value="{{ $dm->user_id ?? $dm->id }}" {{ request('deliveryman_id')==($dm->user_id ?? $dm->id) ? 'selected' : '' }}>
                                    {{ $dm->user->name ?? $dm->name ?? ('#'.$dm->id) }}
                                </option>
                            @endforeach
                        </select>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm mr-2 mb-1" />
                        <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="form-control form-control-sm mr-2 mb-1" />
                        <button type="submit" class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
                        <a href="{{ route('ndr.index') }}" class="btn btn-sm btn-light mr-2 mb-1">{{ __('Clear') }}</a>
                        <a href="{{ route('ndr.export', request()->only(['status','failure_reason','deliveryman_id','date_from','date_to'])) }}"
                           class="btn btn-sm btn-success mb-1">
                            <i class="fa fa-file-excel-o"></i> {{ __('Export to Excel') }}
                        </a>
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
                                    <th>{{ __('Attempt') }}</th>
                                    <th>{{ __('Failure Reason') }}</th>
                                    <th>{{ __('Deliveryman') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created') }}</th>
                                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ndrs as $ndr)
                                    <tr>
                                        <td>{{ $ndr->id }}</td>
                                        <td>{{ optional($ndr->parcel)->tracking_id ?? ('#'.$ndr->parcel_id) }}</td>
                                        <td>
                                            <span class="attempt-badge att-{{ (int) $ndr->attempt_number }}">
                                                {{ $ndr->attempt_number }}/3
                                            </span>
                                        </td>
                                        <td>{{ ucwords(str_replace('_',' ',$ndr->failure_reason)) }}</td>
                                        <td>{{ optional($ndr->deliveryman)->name ?? '—' }}</td>
                                        <td><span class="nstatus ns-{{ $ndr->status }}">{{ ucwords(str_replace('_',' ',$ndr->status)) }}</span></td>
                                        <td><small class="text-muted">{{ $ndr->created_at?->diffForHumans() }}</small></td>
                                        <td class="text-right pr-3">
                                            <a href="{{ route('ndr.show', $ndr->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">{{ __('No NDRs found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($ndrs->hasPages())
                    <div class="card-footer">
                        {{ $ndrs->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
