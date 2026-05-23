@extends('backend.partials.master')
@section('title'){{ $f->fulfillment_number }}@endsection

@push('styles')
<style>
  .fu-pill { padding:3px 9px; border-radius:5px; font-size:12px; font-weight:600; text-transform:uppercase; }
  .fp-pending     { background:#f3f4f6; color:#374151; }
  .fp-picking     { background:#fef3c7; color:#92400e; }
  .fp-packing     { background:#e0e7ff; color:#3730a3; }
  .fp-ready       { background:#dcfce7; color:#166534; }
  .fp-dispatched  { background:#e0f2fe; color:#075985; }
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:38%; color:#6b7280; font-size:13px; }
  .info-row dd { width:62%; margin:0; font-size:14px; font-weight:500; }

  .ship-step { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:6px; background:#f9fafb; margin-bottom:6px; }
  .ship-step .dot { width:10px; height:10px; border-radius:50%; background:#9ca3af; }
  .ship-step.done .dot { background:#16a34a; }
  .ship-step.now  .dot { background:#f59e0b; animation: pulse 1.4s infinite; }
  @keyframes pulse { 0%{box-shadow:0 0 0 0 rgba(245,158,11,.7)} 70%{box-shadow:0 0 0 8px rgba(245,158,11,0)} 100%{box-shadow:0 0 0 0 rgba(245,158,11,0)} }
  .ship-step .lbl { font-weight:500; }
  .ship-step .ts  { margin-left:auto; color:#9ca3af; font-size:12px; }

  .item-row.done { background:#f0fdf4; }
  .item-row.short { background:#fef3c7; }
  .sla-bad { color:#b91c1c; font-weight:700; }
</style>
@endpush

@section('maincontent')
@php
    $stages = ['pending','picking','packing','ready','dispatched'];
    $currentIdx = array_search($f->status, $stages);
    $timestamps = [
        'pending'    => $f->created_at,
        'picking'    => $f->picked_at  ? null : null, // started ≠ finished; show picked_at on packing stage
        'packing'    => $f->picked_at,
        'ready'      => $f->packed_at,
        'dispatched' => $f->dispatched_at,
    ];
@endphp

<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.fulfillment.index') }}">{{ __('Fulfillment') }}</a></li>
                <li class="breadcrumb-item active text-monospace">{{ $f->fulfillment_number }}</li>
            </ol></nav>
        </div>

        @if (in_array($f->status, ['pending','picking']))
            <a href="{{ route('wms.fulfillment.picking', $f->id) }}" class="btn btn-sm btn-warning">▶ {{ __('Start picking') }}</a>
        @elseif ($f->status === 'packing')
            <form method="POST" action="{{ route('wms.fulfillment.pack', $f->id) }}" class="d-inline">
                @csrf @method('PUT')
                <button class="btn btn-sm btn-primary">📦 {{ __('Confirm Pack') }}</button>
            </form>
        @elseif ($f->status === 'ready')
            <form method="POST" action="{{ route('wms.fulfillment.dispatch', $f->id) }}" class="d-inline"
                  onsubmit="return confirm('{{ __('Dispatch this fulfillment? Stock will be deducted and the parcel handed to the courier workflow.') }}');">
                @csrf @method('PUT')
                <button class="btn btn-sm btn-success">🚚 {{ __('Dispatch') }}</button>
            </form>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 text-monospace">{{ $f->fulfillment_number }}</h5>
                    <span class="fu-pill fp-{{ $f->status }}">{{ ucfirst($f->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Parcel') }}</dt><dd>{{ optional($f->parcel)->tracking_id ?? ('#'.$f->parcel_id) }}</dd></div>
                        <div class="info-row"><dt>{{ __('Customer') }}</dt><dd>{{ optional($f->parcel)->customer_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Merchant') }}</dt><dd>{{ optional($f->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Hub') }}</dt><dd>{{ optional($f->hub)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Picker') }}</dt><dd>{{ optional($f->picker)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Packer') }}</dt><dd>{{ optional($f->packer)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('SLA deadline') }}</dt><dd class="{{ $f->isSlaBreached() ? 'sla-bad' : '' }}">
                            {{ $f->sla_deadline?->toDateTimeString() ?? '—' }}
                            @if ($f->sla_deadline) <small>({{ $f->sla_deadline->diffForHumans() }})</small>@endif
                        </dd></div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Pipeline') }}</h6></div>
                <div class="card-body">
                    @foreach ($stages as $i => $stage)
                        @php
                            $isDone = $currentIdx !== false && $i < $currentIdx;
                            $isNow  = $f->status === $stage;
                            $ts     = $timestamps[$stage] ?? null;
                        @endphp
                        <div class="ship-step {{ $isDone ? 'done' : ($isNow ? 'now' : '') }}">
                            <span class="dot"></span>
                            <span class="lbl">{{ ucfirst($stage) }}</span>
                            <span class="ts">{{ $ts ? $ts->diffForHumans() : '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Items') }}</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="thead-light"><tr>
                            <th>#</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Location') }}</th>
                            <th class="text-center">{{ __('Required') }}</th>
                            <th class="text-center">{{ __('Picked') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($f->items as $i => $it)
                                <tr class="item-row {{ $it->status === 'picked' ? 'done' : ($it->status === 'short' ? 'short' : '') }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-monospace">{{ optional($it->product)->sku }}</td>
                                    <td>{{ optional($it->product)->name }}</td>
                                    <td class="text-monospace">{{ optional($it->location)->code }}</td>
                                    <td class="text-center">{{ $it->quantity_required }}</td>
                                    <td class="text-center"><strong>{{ $it->quantity_picked }}</strong></td>
                                    <td>{{ $it->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">{{ __('No items.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($f->notes)
                <div class="card mt-3"><div class="card-header"><h6 class="mb-0">{{ __('Notes') }}</h6></div>
                    <div class="card-body"><p class="mb-0">{{ $f->notes }}</p></div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
