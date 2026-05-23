@extends('backend.partials.master')
@section('title'){{ $grn->grn_number }}@endsection

@push('styles')
<style>
  .grn-pill { padding:3px 9px; border-radius:5px; font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; }
  .gp-draft        { background:#f3f4f6; color:#374151; }
  .gp-in_progress  { background:#e0f2fe; color:#075985; }
  .gp-completed    { background:#dcfce7; color:#166534; }
  .gp-discrepancy  { background:#fee2e2; color:#991b1b; }
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:38%; color:#6b7280; font-size:13px; }
  .info-row dd { width:62%; margin:0; font-size:14px; font-weight:500; }
  .qty-mismatch { background:#fef2f2; }
  .qty-mismatch td { font-weight:600; }
  .cond-good     { background:#dcfce7; color:#166534; padding:2px 7px; border-radius:4px; font-size:11px; }
  .cond-damaged  { background:#fee2e2; color:#991b1b; padding:2px 7px; border-radius:4px; font-size:11px; }
  .cond-expired  { background:#f3f4f6; color:#1f2937; padding:2px 7px; border-radius:4px; font-size:11px; }
  @media print { .no-print { display:none !important; } body { background:#fff; } }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center no-print">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.grn.index') }}">{{ __('Receiving') }}</a></li>
                <li class="breadcrumb-item active text-monospace">{{ $grn->grn_number }}</li>
            </ol></nav>
        </div>
        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="window.print()">🖨 {{ __('Print label') }}</button>
        @if (in_array($grn->status, ['draft','in_progress']))
            <form method="POST" action="{{ route('wms.grn.complete', $grn->id) }}" class="d-inline mr-2"
                  onsubmit="return confirm('{{ __('Complete this GRN? Stock will be credited and discrepancies flagged.') }}');">
                @csrf @method('PUT')
                <button class="btn btn-sm btn-success">✓ {{ __('Complete GRN') }}</button>
            </form>
            <form method="POST" action="{{ route('wms.grn.destroy', $grn->id) }}" class="d-inline"
                  onsubmit="return confirm('{{ __('Delete this draft?') }}');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
            </form>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 text-monospace">{{ $grn->grn_number }}</h5>
                    <span class="grn-pill gp-{{ $grn->status }}">{{ ucwords(str_replace('_',' ',$grn->status)) }}</span>
                </div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Merchant') }}</dt><dd>{{ optional($grn->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Hub') }}</dt><dd>{{ optional($grn->hub)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Reference') }}</dt><dd>{{ $grn->reference_number ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Received By') }}</dt><dd>{{ optional($grn->receivedBy)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Received At') }}</dt><dd>{{ $grn->received_at?->toDateTimeString() ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Created') }}</dt><dd>{{ $grn->created_at?->toDateTimeString() }}</dd></div>
                    </dl>
                    @if ($grn->notes)
                        <h6 class="text-uppercase text-muted small mt-3">{{ __('Notes') }}</h6>
                        <p class="mb-0">{{ $grn->notes }}</p>
                    @endif
                </div>
            </div>

            @if ($grn->hasDiscrepancy())
                <div class="alert alert-danger no-print">
                    <strong>⚠ {{ __('Discrepancy detected') }}</strong><br>
                    {{ __('One or more lines received a different quantity than expected. Highlighted rows below.') }}
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Line items') }}</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th class="text-center">{{ __('Expected') }}</th>
                                <th class="text-center">{{ __('Received') }}</th>
                                <th>{{ __('Batch') }}</th>
                                <th>{{ __('Expiry') }}</th>
                                <th>{{ __('Condition') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($grn->items as $i => $item)
                                @php $mismatch = (int)$item->expected_qty !== (int)$item->received_qty; @endphp
                                <tr class="{{ $mismatch ? 'qty-mismatch' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-monospace">{{ optional($item->product)->sku ?? '—' }}</td>
                                    <td>{{ optional($item->product)->name ?? '—' }}</td>
                                    <td class="text-monospace">{{ optional($item->location)->code ?? '—' }}</td>
                                    <td class="text-center">{{ $item->expected_qty }}</td>
                                    <td class="text-center">{{ $item->received_qty }}@if ($mismatch) <small class="text-danger">({{ ($item->received_qty - $item->expected_qty) > 0 ? '+' : '' }}{{ $item->received_qty - $item->expected_qty }})</small>@endif</td>
                                    <td>{{ $item->batch_number ?? '—' }}</td>
                                    <td>{{ $item->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td><span class="cond-{{ $item->condition }}">{{ $item->condition }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">{{ __('No items.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
