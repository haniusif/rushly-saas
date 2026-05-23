@extends('backend.partials.master')
@section('title'){{ __('Picking') }} — {{ $f->fulfillment_number }}@endsection

@push('styles')
<style>
  .pick-wrap { max-width:560px; margin:30px auto; }
  .pick-card { background:#fff; border-radius:14px; box-shadow:0 4px 16px rgba(0,0,0,0.08); padding:28px; }
  .ff-num { color:#9ca3af; font-size:12px; letter-spacing:0.05em; text-transform:uppercase; }
  .loc-code {
      display:inline-block; background:#0f172a; color:#fff;
      font-family:ui-monospace,Menlo,monospace; font-size:42px; font-weight:700;
      padding:14px 24px; border-radius:10px; margin: 14px 0; letter-spacing:2px;
  }
  .prod-name { font-size:22px; font-weight:600; color:#111827; margin-bottom:4px; }
  .prod-sku  { color:#6b7280; font-family:ui-monospace,Menlo,monospace; font-size:14px; }
  .qty-block { background:#f9fafb; border-radius:10px; padding:18px; margin:18px 0; text-align:center; }
  .qty-req { font-size:14px; color:#6b7280; }
  .qty-num { font-size:48px; font-weight:800; color:#1f2937; line-height:1; margin-top:4px; }
  .qty-input { font-size:32px !important; font-weight:700; text-align:center; height:auto; padding:14px; }
  .confirm-btn { width:100%; padding:18px; font-size:17px; font-weight:700; }
  .progress { height:8px; border-radius:999px; }
  .all-done { text-align:center; padding:50px 20px; }
  .all-done .check { font-size:72px; color:#16a34a; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid">
    <div class="pick-wrap">
        <div class="pick-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <a href="{{ route('wms.fulfillment.show', $f->id) }}" class="text-muted small">‹ {{ __('Back to fulfillment') }}</a>
                <span class="ff-num">{{ $f->fulfillment_number }}</span>
            </div>

            @php
                $total = $f->items->count();
                $done  = $f->items->where('status', 'picked')->count();
                $pct   = $total > 0 ? round($done / $total * 100) : 0;
            @endphp

            <div class="progress mb-3"><div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div></div>
            <div class="text-center small text-muted mb-3">{{ $done }} {{ __('of') }} {{ $total }} {{ __('items picked') }}</div>

            @if ($next)
                <form method="POST" action="{{ route('wms.fulfillment.pick', $f->id) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="item_id" value="{{ $next->id }}">

                    <div class="text-center">
                        <div class="ff-num">{{ __('Walk to location') }}</div>
                        <div class="loc-code">{{ optional($next->location)->code ?? '?' }}</div>
                    </div>

                    <div class="text-center mb-3">
                        <div class="prod-name">{{ optional($next->product)->name }}</div>
                        <div class="prod-sku">SKU: {{ optional($next->product)->sku }}</div>
                    </div>

                    <div class="qty-block">
                        <div class="qty-req">{{ __('Pick this many:') }}</div>
                        <div class="qty-num">{{ $next->quantity_required }}</div>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted">{{ __('Picked quantity (defaults to required)') }}</label>
                        <input type="number" name="picked_qty" class="form-control qty-input"
                               min="0" max="{{ $next->quantity_required }}"
                               value="{{ $next->quantity_required }}" required>
                    </div>

                    <button class="btn btn-warning confirm-btn">✓ {{ __('Confirm pick') }}</button>
                </form>

                @if ($f->items->whereIn('status', ['pending', 'short'])->count() > 1)
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            {{ __(':n more item(s) after this one', ['n' => $f->items->whereIn('status', ['pending','short'])->count() - 1]) }}
                        </small>
                    </div>
                @endif
            @else
                <div class="all-done">
                    <div class="check">✓</div>
                    <h4 class="mt-3">{{ __('All items picked!') }}</h4>
                    <p class="text-muted">{{ __('Head back to the fulfillment to pack and dispatch.') }}</p>
                    <a href="{{ route('wms.fulfillment.show', $f->id) }}" class="btn btn-primary mt-2">{{ __('Continue') }}</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
