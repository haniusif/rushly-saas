@extends('backend.partials.master')
@section('title'){{ $product->sku }}@endsection

@push('styles')
<style>
  .info-row { display:flex; padding:8px 0; border-bottom:1px dashed #e5e7eb; }
  .info-row dt { width:38%; color:#6b7280; font-size:13px; }
  .info-row dd { width:62%; margin:0; font-size:14px; font-weight:500; }
  .stock-table td, .stock-table th { padding:8px 12px; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header d-flex align-items-center">
        <div class="page-breadcrumb flex-grow-1">
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('wms.products.index') }}">{{ __('Products') }}</a></li>
                <li class="breadcrumb-item active">{{ $product->sku }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('wms.products.barcode', $product->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary mr-2">📊 {{ __('Barcode') }}</a>
        <a href="{{ route('wms.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0">{{ $product->name }}</h5>
                    @if (!$product->is_active)<span class="badge badge-secondary">{{ __('Inactive') }}</span>@endif
                </div>
                <div class="card-body">
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('SKU') }}</dt><dd>{{ $product->sku }}</dd></div>
                        <div class="info-row"><dt>{{ __('Barcode') }}</dt><dd class="text-monospace">{{ $product->barcode ?: '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Merchant') }}</dt><dd>{{ optional($product->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Hub') }}</dt><dd>{{ optional($product->hub)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Unit') }}</dt><dd>{{ $product->unit }}</dd></div>
                        <div class="info-row"><dt>{{ __('Weight') }}</dt><dd>{{ $product->weight ?? '—' }} kg</dd></div>
                        @if ($product->dimensions)
                            <div class="info-row"><dt>{{ __('Dimensions') }}</dt><dd>{{ ($product->dimensions['l'] ?? '?').' × '.($product->dimensions['w'] ?? '?').' × '.($product->dimensions['h'] ?? '?') }} cm</dd></div>
                        @endif
                        <div class="info-row"><dt>{{ __('Reorder Point') }}</dt><dd>{{ $product->reorder_point }}</dd></div>
                        <div class="info-row"><dt>{{ __('Track Expiry') }}</dt><dd>{{ $product->track_expiry ? '✓' : '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('Stock by location') }}</h6>
                    <div>
                        <span class="badge badge-pill badge-primary mr-1">{{ __('On hand') }}: {{ $product->total_qty }}</span>
                        <span class="badge badge-pill badge-warning mr-1">{{ __('Reserved') }}: {{ $product->reserved_qty }}</span>
                        <span class="badge badge-pill badge-success">{{ __('Available') }}: {{ $product->available_qty }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0 stock-table">
                        <thead class="thead-light"><tr>
                            <th>{{ __('Location') }}</th>
                            <th class="text-center">{{ __('Qty') }}</th>
                            <th class="text-center">{{ __('Reserved') }}</th>
                            <th class="text-center">{{ __('Available') }}</th>
                            <th>{{ __('Batch') }}</th>
                            <th>{{ __('Expiry') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($product->stocks as $s)
                                <tr>
                                    <td class="text-monospace">{{ optional($s->location)->code ?? '—' }}</td>
                                    <td class="text-center">{{ $s->quantity }}</td>
                                    <td class="text-center">{{ $s->reserved_qty }}</td>
                                    <td class="text-center"><strong>{{ max(0, $s->quantity - $s->reserved_qty) }}</strong></td>
                                    <td>{{ $s->batch_number ?? '—' }}</td>
                                    <td>{{ $s->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('No stock yet — create a GRN to receive inventory.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($product->description)
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">{{ __('Description') }}</h6></div>
                    <div class="card-body"><p class="mb-0">{{ $product->description }}</p></div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
