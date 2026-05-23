@extends('backend.partials.master')
@section('title'){{ __('WMS') }} — {{ __('Fulfillment') }}@endsection

@push('styles')
<style>
  .fu-card { border-radius:10px; padding:14px 18px; color:#fff; }
  .fu-card .num { font-size:24px; font-weight:800; line-height:1; }
  .fu-card .lbl { font-size:11px; text-transform:uppercase; letter-spacing:0.05em; opacity:0.92; margin-top:3px; }
  .fc-pending     { background: linear-gradient(135deg, #6b7280, #4b5563); }
  .fc-picking     { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .fc-packing     { background: linear-gradient(135deg, #6366f1, #4338ca); }
  .fc-ready       { background: linear-gradient(135deg, #10b981, #047857); }
  .fc-dispatched  { background: linear-gradient(135deg, #0ea5e9, #0369a1); }
  .fc-sla         { background: linear-gradient(135deg, #ef4444, #b91c1c); }

  .fu-pill { padding:3px 9px; border-radius:5px; font-size:11.5px; font-weight:600; text-transform:uppercase; }
  .fp-pending     { background:#f3f4f6; color:#374151; }
  .fp-picking     { background:#fef3c7; color:#92400e; }
  .fp-packing     { background:#e0e7ff; color:#3730a3; }
  .fp-ready       { background:#dcfce7; color:#166534; }
  .fp-dispatched  { background:#e0f2fe; color:#075985; }
  .fp-cancelled   { background:#f3f4f6; color:#6b7280; }
  .sla-bad { color:#b91c1c; font-weight:700; }
  .sla-ok  { color:#16a34a; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="">{{ __('WMS') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Fulfillment') }}</li>
        </ol></nav>
    </div></div>

    <div class="row mb-3">
        @foreach ([
            ['pending','Pending','fc-pending'],
            ['picking','Picking','fc-picking'],
            ['packing','Packing','fc-packing'],
            ['ready','Ready','fc-ready'],
            ['dispatched_today',"Dispatched today",'fc-dispatched'],
            ['sla_breached','SLA breached','fc-sla'],
        ] as [$k, $lbl, $cls])
            <div class="col-md-2 col-sm-4 col-6 mb-2">
                <div class="fu-card {{ $cls }}">
                    <div class="num">{{ $summary[$k] ?? 0 }}</div>
                    <div class="lbl">{{ __($lbl) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3"><div class="card-body py-3">
        <form method="GET" class="form-inline">
            <select name="status" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('All status') }}</option>
                @foreach (['pending','picking','packing','ready','dispatched','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status')==$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="hub_id" class="form-control form-control-sm mr-2 mb-1">
                <option value="">{{ __('Any hub') }}</option>
                @foreach ($hubs as $h)<option value="{{ $h->id }}" {{ request('hub_id')==$h->id ? 'selected' : '' }}>{{ $h->name ?? ('#'.$h->id) }}</option>@endforeach
            </select>
            <div class="custom-control custom-switch mr-2 mb-1">
                <input type="checkbox" class="custom-control-input" id="sla" name="sla_breached" value="1" {{ request('sla_breached') ? 'checked' : '' }}>
                <label class="custom-control-label" for="sla">{{ __('SLA breached only') }}</label>
            </div>
            <button class="btn btn-sm btn-primary mr-2 mb-1">{{ __('Filter') }}</button>
            <a href="{{ route('wms.fulfillment.index') }}" class="btn btn-sm btn-light mb-1">{{ __('Clear') }}</a>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>#</th>
                    <th>{{ __('Fulfillment #') }}</th>
                    <th>{{ __('Parcel') }}</th>
                    <th>{{ __('Merchant') }}</th>
                    <th>{{ __('Hub') }}</th>
                    <th>{{ __('Picker') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('SLA') }}</th>
                    <th class="text-right pr-3">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($fulfillments as $f)
                        @php $breached = $f->isSlaBreached(); @endphp
                        <tr>
                            <td>{{ $f->id }}</td>
                            <td><strong class="text-monospace">{{ $f->fulfillment_number }}</strong></td>
                            <td>{{ optional($f->parcel)->tracking_id ?? ('#'.$f->parcel_id) }}</td>
                            <td>{{ optional($f->merchant)->business_name ?? '—' }}</td>
                            <td>{{ optional($f->hub)->name ?? '—' }}</td>
                            <td>{{ optional($f->picker)->name ?? '—' }}</td>
                            <td><span class="fu-pill fp-{{ $f->status }}">{{ ucfirst($f->status) }}</span></td>
                            <td class="{{ $breached ? 'sla-bad' : ($f->sla_deadline ? 'sla-ok' : '') }}">
                                @if ($f->sla_deadline)
                                    {{ $breached ? '⚠ ' . __('overdue') : __('in :t', ['t' => $f->sla_deadline->diffForHumans(null, true)]) }}
                                @else — @endif
                            </td>
                            <td class="text-right pr-3">
                                <a href="{{ route('wms.fulfillment.show', $f->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No fulfillments yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($fulfillments->hasPages())<div class="card-footer">{{ $fulfillments->withQueryString()->links() }}</div>@endif
    </div>
</div>
@endsection
