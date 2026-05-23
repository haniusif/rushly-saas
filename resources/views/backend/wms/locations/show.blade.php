@extends('backend.partials.master')
@section('title'){{ $location->code }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="page-header"><div class="page-breadcrumb">
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('levels.dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('wms.locations.index') }}">{{ __('Locations') }}</a></li>
            <li class="breadcrumb-item active text-monospace">{{ $location->code }}</li>
        </ol></nav>
    </div></div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 text-monospace">{{ $location->code }}</h5>
                    <span class="badge badge-info">{{ $location->type }}</span>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ __('Hub') }}:</strong> {{ optional($location->hub)->name ?? '—' }}</p>
                    <p class="mb-1"><strong>{{ __('Zone / Aisle / Rack / Shelf / Bin') }}:</strong>
                        {{ $location->zone ?? '—' }} / {{ $location->aisle ?? '—' }} / {{ $location->rack }} / {{ $location->shelf }} / {{ $location->bin ?? '—' }}
                    </p>
                    <p class="mb-1"><strong>{{ __('Capacity') }}:</strong> {{ $location->capacity ?? '—' }}</p>
                    <p class="mb-0"><strong>{{ __('Status') }}:</strong> {{ $location->is_active ? __('Active') : __('Inactive') }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('Stocked products') }}</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="thead-light"><tr>
                            <th>{{ __('SKU') }}</th><th>{{ __('Product') }}</th>
                            <th class="text-center">{{ __('Qty') }}</th><th class="text-center">{{ __('Reserved') }}</th>
                            <th>{{ __('Batch') }}</th><th>{{ __('Expiry') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($location->stocks as $s)
                                <tr>
                                    <td><a href="{{ route('wms.products.show', $s->product_id) }}">{{ optional($s->product)->sku ?? '#'.$s->product_id }}</a></td>
                                    <td>{{ optional($s->product)->name ?? '—' }}</td>
                                    <td class="text-center">{{ $s->quantity }}</td>
                                    <td class="text-center">{{ $s->reserved_qty }}</td>
                                    <td>{{ $s->batch_number ?? '—' }}</td>
                                    <td>{{ $s->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">{{ __('Empty location.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
