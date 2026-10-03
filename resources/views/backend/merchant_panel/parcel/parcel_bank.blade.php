@extends('backend.partials.master')
@section('title')
    {{ __('parcel.parcel_bank') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    @php
        $currency = settings()->currency;
        $fmt      = fn ($n) => number_format((float) ($n ?? 0), 2);
    @endphp

    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            {{-- Breadcrumb --}}
            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('merchant-panel.parcel-bank.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('parcel.parcel_bank') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.list') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">

                {{-- Header --}}
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <span class="tw-shrink-0 tw-w-10 tw-h-10 tw-rounded-lg tw-bg-brand-50 tw-text-brand-600 tw-flex tw-items-center tw-justify-center">
                            <i class="fa fa-archive tw-text-lg"></i>
                        </span>
                        <div>
                            <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('parcel.parcel_bank') }}</h1>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ $parcels->total() }} {{ __('Showing') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-4 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.tracking_id') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.recipient_info') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium tw-text-right">{{ __('parcel.amount') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.status') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium">{{ __('parcel.payment') }}</th>
                                <th class="tw-px-4 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($parcels, 'firstItem') ? $parcels->firstItem() : 1; @endphp
                            @forelse ($parcels as $parcel)
                                @php
                                    // Mirror the original payment-status derivation, just hoisted out of the cell for readability.
                                    $inv = $parcel->parcel_invoice;
                                    if ($inv !== null && $inv->status == \App\Enums\InvoiceStatus::PAID) {
                                        $payStatus = $inv->status;
                                    } elseif ($inv !== null && $inv->status == \App\Enums\InvoiceStatus::UNPAID) {
                                        $payStatus = \App\Enums\InvoiceStatus::UNPAID;
                                    } elseif ($inv !== null) {
                                        $payStatus = ($parcel->status == \App\Enums\ParcelStatus::DELIVERED || $parcel->status == \App\Enums\ParcelStatus::PARTIAL_DELIVERED)
                                            ? \App\Enums\InvoiceStatus::PROCESSING
                                            : \App\Enums\InvoiceStatus::UNPAID;
                                    } else {
                                        $payStatus = \App\Enums\InvoiceStatus::UNPAID;
                                    }
                                    $isPaid = $payStatus == \App\Enums\InvoiceStatus::PAID;
                                @endphp
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors tw-align-top">
                                    <td class="tw-px-4 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>
                                    <td class="tw-px-4 tw-py-3">
                                        <code class="tw-text-xs tw-px-2 tw-py-0.5 tw-bg-gray-100 tw-text-gray-700 tw-rounded">{{ $parcel->tracking_id }}</code>
                                    </td>
                                    <td class="tw-px-4 tw-py-3 merchantpayment">
                                        <div class="tw-space-y-1 tw-min-w-0 tw-max-w-xs">
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-sm">
                                                <i class="fa fa-user tw-text-gray-400 tw-mt-1 tw-text-xs"></i>
                                                <span class="tw-font-medium tw-text-gray-900 tw-truncate">{{ $parcel->customer_name }}</span>
                                            </div>
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-xs tw-text-gray-600">
                                                <i class="fas fa-phone tw-text-gray-400 tw-mt-0.5"></i>
                                                <span class="tw-tabular-nums">{{ $parcel->customer_phone }}</span>
                                            </div>
                                            <div class="tw-flex tw-items-start tw-gap-2 tw-text-xs tw-text-gray-600">
                                                <i class="fas fa-map-marker-alt tw-text-gray-400 tw-mt-0.5"></i>
                                                <span class="tw-line-clamp-2">{{ $parcel->customer_address }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tw-px-4 tw-py-3 tw-text-right tw-tabular-nums">
                                        <div class="tw-space-y-0.5 tw-text-xs">
                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                <span class="tw-text-gray-500">{{ __('levels.cod') }}:</span>
                                                <span class="tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->cash_collection) }}</span>
                                            </div>
                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                <span class="tw-text-gray-500">{{ __('levels.total_delivery_amount') }}:</span>
                                                <span class="tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->total_delivery_amount) }}</span>
                                            </div>
                                            <div class="tw-flex tw-justify-between tw-gap-3">
                                                <span class="tw-text-gray-500">{{ __('levels.vat_amount') }}:</span>
                                                <span class="tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->vat_amount) }}</span>
                                            </div>
                                            <div class="tw-flex tw-justify-between tw-gap-3 tw-pt-1 tw-border-t tw-border-gray-100">
                                                <span class="tw-text-gray-500">{{ __('levels.current_payable') }}:</span>
                                                <span class="tw-font-semibold tw-text-gray-900">{{ $currency }} {{ $fmt($parcel->current_payable) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tw-px-4 tw-py-3">
                                        <div>{!! $parcel->parcel_status !!}</div>
                                        <div class="tw-text-[11px] tw-text-gray-500 tw-mt-1 tw-tabular-nums">
                                            {{ __('parcel.updated_on') }}: {{ \Carbon\Carbon::parse($parcel->updated_at)->format('Y-m-d h:i A') }}
                                        </div>
                                    </td>
                                    <td class="tw-px-4 tw-py-3">
                                        <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-full {{ $isPaid ? 'tw-bg-emerald-50 tw-text-emerald-700' : 'tw-bg-amber-50 tw-text-amber-700' }}">
                                            {{ __('invoice.' . $payStatus) }}
                                        </span>
                                        @if (@$inv->invoice_id)
                                            <div class="tw-text-xs tw-text-gray-700 tw-mt-1 tw-tabular-nums">{{ $inv->invoice_id }}</div>
                                        @endif
                                        @if ($isPaid)
                                            <div class="tw-text-[11px] tw-text-gray-500 tw-tabular-nums">{{ __('Paid at') }} {{ @dateFormat($inv->updated_at) }}</div>
                                        @endif
                                    </td>
                                    <td class="tw-px-4 tw-py-3 tw-text-right">
                                        <div class="dropdown tw-inline-block">
                                            <button tabindex="-1" data-toggle="dropdown" type="button"
                                                    class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent"
                                                    aria-label="actions">
                                                <i class="fa fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a href="{{ route('merchant-parcel.clone', $parcel->id) }}" class="dropdown-item">
                                                    <i class="fas fa-clone"></i> {{ __('levels.clone') }}
                                                </a>
                                                @if ($parcel->status == \App\Enums\ParcelStatus::PENDING)
                                                    <a href="{{ route('merchant-panel.parcel.edit', $parcel->id) }}" class="dropdown-item">
                                                        <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                    </a>
                                                    <form id="delete" action="{{ route('merchant-panel.parcel.delete', $parcel->id) }}" method="POST" data-title="{{ __('delete.parcel') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <button type="submit" class="dropdown-item tw-text-red-600">
                                                            <i class="fa fa-trash"></i> {{ __('levels.delete') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="tw-px-4 tw-py-12 tw-text-center">
                                        <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-archive tw-text-3xl"></i></div>
                                        <p class="tw-text-sm tw-text-gray-500 tw-m-0">No parcels in the bank.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($parcels->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $parcels->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $parcels->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $parcels->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $parcels->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection()
