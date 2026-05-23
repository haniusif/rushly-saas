@extends('backend.partials.master')
@section('title'){{ $outbound->outbound_number }}@endsection
@push('styles')<style>
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:38%; color:#6b7280; font-size:13px; }
  .info-row dd { width:62%; margin:0; font-size:14px; font-weight:500; }
  .ob-pill { padding:3px 9px; border-radius:5px; font-size:12px; font-weight:600; text-transform:uppercase; }
  .ob-pending   { background:#fff7ed; color:#9a3412; }
  .ob-completed { background:#dcfce7; color:#166534; }
</style>@endpush
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.outbound.index') }}">{{ __('Outbound') }}</a></li>
                <li class="breadcrumb-item active text-monospace">{{ $outbound->outbound_number }}</li>
            </ol></nav>
        </div>
        @if ($outbound->status !== 'completed')
            <form method="POST" action="{{ route('wms.outbound.complete', $outbound->id) }}"
                  onsubmit="return confirm('{{ __('Complete this outbound? Stock will be deducted.') }}');">
                @csrf @method('PUT')
                <button class="btn btn-sm btn-success">✓ {{ __('Complete & deduct stock') }}</button>
            </form>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 text-monospace">{{ $outbound->outbound_number }}</h5>
                    <span class="ob-pill ob-{{ $outbound->status }}">{{ ucfirst($outbound->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Type') }}</dt><dd>{{ str_replace('_',' ',$outbound->type) }}</dd></div>
                        <div class="info-row"><dt>{{ __('Merchant') }}</dt><dd>{{ optional($outbound->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Hub') }}</dt><dd>{{ optional($outbound->hub)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Processed By') }}</dt><dd>{{ optional($outbound->processedBy)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Completed') }}</dt><dd>{{ $outbound->completed_at?->toDateTimeString() ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Items') }}</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="thead-light"><tr>
                            <th>#</th><th>{{ __('SKU') }}</th><th>{{ __('Product') }}</th>
                            <th>{{ __('Location') }}</th><th class="text-center">{{ __('Quantity') }}</th>
                            <th>{{ __('Batch') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($outbound->items as $i => $it)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="text-monospace">{{ optional($it->product)->sku }}</td>
                                    <td>{{ optional($it->product)->name }}</td>
                                    <td class="text-monospace">{{ optional($it->location)->code }}</td>
                                    <td class="text-center"><strong>{{ $it->quantity }}</strong></td>
                                    <td>{{ $it->batch_number ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No items.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
