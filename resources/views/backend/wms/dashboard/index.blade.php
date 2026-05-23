@extends('backend.partials.master')
@section('title'){{ __('WMS Dashboard') }}@endsection

@push('styles')
<style>
  .kpi { background:#fff; border-radius:12px; padding:18px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border-left:4px solid #6366f1; }
  .kpi .lbl { font-size:11.5px; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280; font-weight:600; }
  .kpi .num { font-size:28px; font-weight:800; color:#111827; line-height:1; margin-top:5px; }
  .kpi .sub { font-size:12px; color:#9ca3af; margin-top:2px; }
  .kpi.green  { border-left-color:#10b981; }
  .kpi.amber  { border-left-color:#f59e0b; }
  .kpi.red    { border-left-color:#ef4444; }
  .kpi.blue   { border-left-color:#0ea5e9; }
  .kpi.purple { border-left-color:#8b5cf6; }
  .kpi.gray   { border-left-color:#6b7280; }

  .alert-card { background:#fff; border-radius:10px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
  .alert-card h6 { font-size:13px; text-transform:uppercase; letter-spacing:0.04em; color:#6b7280; font-weight:700; margin-bottom:12px; }
  .alert-row { display:flex; padding:6px 0; border-bottom:1px dashed #e5e7eb; font-size:13px; }
  .alert-row:last-child { border-bottom:none; }
  .alert-row .l { flex:1; }
  .alert-row .r { color:#9ca3af; font-size:12px; }
  .alert-row.bad .r { color:#dc2626; font-weight:600; }

  .chart-card { background:#fff; border-radius:10px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
  .ff-bar { display:flex; align-items:center; gap:10px; margin-bottom:6px; font-size:13px; }
  .ff-bar .name { width:90px; }
  .ff-bar .bar  { flex:1; height:14px; border-radius:4px; background:#f3f4f6; overflow:hidden; }
  .ff-bar .bar > div { height:100%; }
  .ff-bar .n { width:34px; text-align:right; font-weight:600; }
  .b-pending     { background:#9ca3af; }
  .b-picking     { background:#f59e0b; }
  .b-packing     { background:#6366f1; }
  .b-ready       { background:#10b981; }
  .b-dispatched  { background:#0ea5e9; }
  .b-cancelled   { background:#374151; }

  .mv-row { display:flex; align-items:flex-end; gap:6px; height:140px; padding:10px 0; }
  .mv-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:2px; }
  .mv-col .credit { background:#10b981; width:100%; border-radius:3px 3px 0 0; min-height:1px; }
  .mv-col .debit  { background:#ef4444; width:100%; border-radius:0 0 3px 3px; min-height:1px; }
  .mv-col .lbl    { font-size:10px; color:#9ca3af; margin-top:6px; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('WMS Dashboard') }}</li>
        </ol></nav>
    </div></div>

    {{-- ===== KPI grid ===== --}}
    <div class="row mb-3">
        @php $cards = [
            ['lbl'=>'Total SKUs',             'num'=>$kpi['total_skus'],           'cls'=>'blue',   'sub'=>__('active products')],
            ['lbl'=>'Total Units in Stock',   'num'=>number_format($kpi['total_units']), 'cls'=>'green', 'sub'=>__('across all locations')],
            ['lbl'=>'Pending Fulfillments',   'num'=>$kpi['pending_fulfillments'], 'cls'=>'purple', 'sub'=>__('not yet dispatched')],
            ['lbl'=>'GRNs Today',             'num'=>$kpi['grns_today'],           'cls'=>'blue',   'sub'=>__('goods received')],
            ['lbl'=>'Low Stock Items',        'num'=>$kpi['low_stock_count'],      'cls'=>'amber',  'sub'=>__('at or below reorder')],
            ['lbl'=>'Damage Reports (month)', 'num'=>$kpi['damage_this_month'],    'cls'=>'red',    'sub'=>__('this month')],
            ['lbl'=>'SLA Breached',           'num'=>$kpi['sla_breached'],         'cls'=>'red',    'sub'=>__('overdue fulfillments')],
            ['lbl'=>'Pending Approvals',      'num'=>$kpi['pending_adjustments'],  'cls'=>'amber',  'sub'=>__('adjustments awaiting 2nd supervisor')],
        ]; @endphp
        @foreach ($cards as $c)
            <div class="col-md-3 col-sm-6 mb-2">
                <div class="kpi {{ $c['cls'] }}">
                    <div class="lbl">{{ $c['lbl'] }}</div>
                    <div class="num">{{ $c['num'] }}</div>
                    <div class="sub">{{ $c['sub'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Charts row ===== --}}
    <div class="row mb-3">
        <div class="col-md-7 mb-2">
            <div class="chart-card">
                <h6 class="text-uppercase text-muted mb-2" style="font-size:13px;letter-spacing:0.04em;">{{ __('Stock movement — last 7 days') }}</h6>
                @php
                    $maxAbs = collect($movement)->flatMap(fn ($r) => [abs($r['credit']), abs($r['debit'])])->max() ?: 1;
                @endphp
                <div class="mv-row">
                    @foreach ($movement as $d)
                        <div class="mv-col">
                            <div class="credit" style="height: {{ round((($d['credit'] / $maxAbs) * 100)) }}%; "></div>
                            <div class="debit"  style="height: {{ round((abs($d['debit']) / $maxAbs) * 100) }}%; "></div>
                            <div class="lbl">{{ $d['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center" style="gap:18px;font-size:12px;">
                    <span><span style="display:inline-block;width:10px;height:10px;background:#10b981;border-radius:2px;"></span> {{ __('Credit (added)') }}</span>
                    <span><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:2px;"></span> {{ __('Debit (removed)') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-2">
            <div class="chart-card h-100">
                <h6 class="text-uppercase text-muted mb-3" style="font-size:13px;letter-spacing:0.04em;">{{ __('Fulfillment status breakdown') }}</h6>
                @php $maxN = collect($fulChart)->max('n') ?: 1; @endphp
                @foreach ($fulChart as $f)
                    <div class="ff-bar">
                        <div class="name">{{ ucfirst($f['status']) }}</div>
                        <div class="bar"><div class="b-{{ $f['status'] }}" style="width: {{ round(($f['n'] / $maxN) * 100) }}%"></div></div>
                        <div class="n">{{ $f['n'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== Alerts row ===== --}}
    <div class="row">
        <div class="col-md-4 mb-2">
            <div class="alert-card">
                <h6>⚠ {{ __('Low stock') }}</h6>
                @forelse ($lowStockProducts as $p)
                    <div class="alert-row bad">
                        <div class="l">
                            <a href="{{ route('wms.products.show', $p->id) }}" class="text-monospace">{{ $p->sku }}</a><br>
                            <small class="text-muted">{{ $p->name }}</small>
                        </div>
                        <div class="r">{{ $p->total_qty }} / {{ $p->reorder_point }}</div>
                    </div>
                @empty
                    <small class="text-muted">{{ __('No low stock — well done.') }}</small>
                @endforelse
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="alert-card">
                <h6>⏰ {{ __('Expiring within 7 days') }}</h6>
                @forelse ($expiringSoon as $s)
                    <div class="alert-row">
                        <div class="l">
                            <a href="{{ route('wms.products.show', $s->product_id) }}" class="text-monospace">{{ optional($s->product)->sku }}</a>
                            <small class="text-muted">@ {{ optional($s->location)->code }}</small>
                        </div>
                        <div class="r">{{ $s->expiry_date?->format('M j') }}</div>
                    </div>
                @empty
                    <small class="text-muted">{{ __('Nothing expiring soon.') }}</small>
                @endforelse
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="alert-card">
                <h6>🚨 {{ __('SLA breached fulfillments') }}</h6>
                @forelse ($slaBreached as $f)
                    <div class="alert-row bad">
                        <div class="l">
                            <a href="{{ route('wms.fulfillment.show', $f->id) }}" class="text-monospace">{{ $f->fulfillment_number }}</a><br>
                            <small class="text-muted">{{ ucfirst($f->status) }} · {{ optional($f->parcel)->tracking_id }}</small>
                        </div>
                        <div class="r">{{ $f->sla_deadline?->diffForHumans() }}</div>
                    </div>
                @empty
                    <small class="text-muted">{{ __('All within SLA.') }}</small>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
