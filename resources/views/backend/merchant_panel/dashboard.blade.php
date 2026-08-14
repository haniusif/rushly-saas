@extends('backend.partials.master')
@section('title')
    {{ __('merchant.dashboard') }}
@endsection
@section('maincontent')
    @php
        $currency      = settings()->currency;
        $rlMerchant    = optional(Auth::user())->merchant;
        $rlServices    = $rlMerchant ? $rlMerchant->activeServices() : [];

        // Null-safe values for the financial cards (older merchant rows can
        // have null on some of these columns).
        $fmt           = fn ($n) => number_format((float) ($n ?? 0), 2);
        $netProfit     = ((float) ($t_cash_collection ?? 0)) - ((float) ($t_selling_price ?? 0));
        $totalProfit   = ((float) ($t_sale ?? 0)) - ((float) ($t_delivery_fee ?? 0)) - ((float) ($ts_vat ?? 0));
        $inTransit     = max(0, (int) $t_parcel - ((int) $t_delivered + (int) $t_return));

        $computedBal   = (float) (optional($rlMerchant)->computed_balance ?? 0);
        $openingBal    = (float) (optional($rlMerchant)->opening_balance ?? 0);
        $merchantVat   = (float) (optional($rlMerchant)->vat ?? 0);

        $serviceMeta = [
            'last_mile'   => ['icon' => 'ti-truck-delivery',   'tint' => 'tw-bg-sky-50 tw-text-sky-700 tw-border-sky-200'],
            'fulfillment' => ['icon' => 'ti-package',          'tint' => 'tw-bg-emerald-50 tw-text-emerald-700 tw-border-emerald-200'],
            'storage'    => ['icon' => 'ti-building-warehouse', 'tint' => 'tw-bg-amber-50 tw-text-amber-700 tw-border-amber-200'],
        ];
    @endphp

    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            {{-- Breadcrumb --}}
            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ url('/') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('merchant.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('merchant.merchant_dashboard') }}</span>
            </nav>

            {{-- Header + filter --}}
            <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-end sm:tw-justify-between tw-gap-3 tw-mb-5">
                <div>
                    <h1 class="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-mb-1">{{ __('merchant.merchant_dashboard') }}</h1>
                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">{{ __('merchant.dashboard') }}</p>
                </div>
                <form action="{{ route('merchant-panel.dashboard.filter') }}" method="POST" class="tw-flex tw-items-center tw-gap-2">
                    @csrf
                    <div class="tw-relative">
                        <i class="fa fa-calendar tw-absolute tw-top-1/2 tw-left-3 -tw-translate-y-1/2 tw-text-gray-400 tw-text-sm"></i>
                        <input type="text" autocomplete="off" id="date" name="date"
                               class="date_range_picker tw-input tw-h-10 tw-pl-9 tw-pr-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-w-56"
                               value="{{ isset($request->date) ? $request->date : old('date') }}"
                               placeholder="{{ __('merchantPlaceholder.date') }}">
                    </div>
                    <button type="submit"
                            class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg">
                        <i class="fa fa-filter tw-text-xs"></i>
                        {{ __('levels.filter') }}
                    </button>
                </form>
            </div>

            {{-- Services badges --}}
            @if (!empty($rlServices))
                <div class="tw-mb-5 tw-flex tw-items-center tw-flex-wrap tw-gap-2">
                    <span class="tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mr-1">{{ __('merchant.services') }}</span>
                    @foreach ($rlServices as $rlSvc)
                        @php $meta = $serviceMeta[$rlSvc] ?? ['icon' => 'ti-check', 'tint' => 'tw-bg-gray-50 tw-text-gray-700 tw-border-gray-200']; @endphp
                        <span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium tw-rounded-full tw-border {{ $meta['tint'] }}">
                            <i class="ti {{ $meta['icon'] }}"></i>
                            {{ __('merchant.service_' . $rlSvc) }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Parcel KPI tiles --}}
            <div class="tw-grid tw-gap-3 tw-grid-cols-2 lg:tw-grid-cols-4 tw-mb-5">
                @php
                    $parcelKpis = [
                        ['url' => route('merchant-panel.parcel.index'), 'icon' => 'fa-box-open',     'label' => __('dashboard.total_parcel'),    'value' => (int) $t_parcel,   'tint' => 'tw-bg-brand-50 tw-text-brand-600'],
                        ['url' => route('merchant-panel.parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::DELIVERED]), 'icon' => 'fa-shipping-fast', 'label' => __('dashboard.total_deliverd_'), 'value' => (int) $t_delivered, 'tint' => 'tw-bg-emerald-50 tw-text-emerald-600'],
                        ['url' => route('merchant-panel.parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT]), 'icon' => 'fa-undo', 'label' => __('dashboard.total_return'), 'value' => (int) $t_return, 'tint' => 'tw-bg-amber-50 tw-text-amber-600'],
                        ['url' => route('merchant-panel.parcel.index'), 'icon' => 'fa-dolly',        'label' => __('dashboard.total_transit'),   'value' => $inTransit,         'tint' => 'tw-bg-indigo-50 tw-text-indigo-600'],
                    ];
                @endphp
                @foreach ($parcelKpis as $k)
                    <a href="{{ $k['url'] }}"
                       class="tw-group tw-block tw-no-underline tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-p-5 tw-shadow-card hover:tw-shadow-card-hover hover:-tw-translate-y-0.5 tw-transition-all">
                        <div class="tw-flex tw-items-center tw-gap-4">
                            <span class="tw-shrink-0 tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center {{ $k['tint'] }}">
                                <i class="fa {{ $k['icon'] }} tw-text-xl"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1">{{ $k['label'] }}</div>
                                <div class="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ number_format($k['value']) }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Three grouped financial lists --}}
            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-3 tw-gap-3 tw-mb-5">

                {{-- Active shipments amounts --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-5 tw-py-3 tw-border-b tw-border-gray-100">
                        <h3 class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('Active Shipments Amount') }}</h3>
                    </div>
                    <ul class="tw-divide-y tw-divide-gray-100 tw-m-0 tw-list-none tw-p-0">
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('Active Shipments Amount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_cash_collection) }} {{ $currency }}</span>
                        </li>
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_selling_price') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_selling_price) }} {{ $currency }}</span>
                        </li>
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3 tw-bg-emerald-50/40">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('dashboard.net_profit_ammount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-tabular-nums {{ $netProfit >= 0 ? 'tw-text-emerald-700' : 'tw-text-rose-600' }}">
                                {{ $fmt($netProfit) }} {{ $currency }}
                            </span>
                        </li>
                    </ul>
                </div>

                {{-- Liquid / packaging / VAT --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-5 tw-py-3 tw-border-b tw-border-gray-100">
                        <h3 class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('dashboard.total_liquid_fragile_amount') }}</h3>
                    </div>
                    <ul class="tw-divide-y tw-divide-gray-100 tw-m-0 tw-list-none tw-p-0">
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_liquid_fragile_amount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_liquid_fragile) }} {{ $currency }}</span>
                        </li>
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_packaging_amount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_packaging) }} {{ $currency }}</span>
                        </li>
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_vat_amount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_vat_amount) }} {{ $currency }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Delivery / COD / total --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-5 tw-py-3 tw-border-b tw-border-gray-100">
                        <h3 class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('dashboard.total_total_delivery_amount') }}</h3>
                    </div>
                    <ul class="tw-divide-y tw-divide-gray-100 tw-m-0 tw-list-none tw-p-0">
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_delivery_charge') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_delivery_charge) }} {{ $currency }}</span>
                        </li>
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_cod_amount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_cod_amount) }} {{ $currency }}</span>
                        </li>
                        <li class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-3">
                            <span class="tw-text-sm tw-text-gray-700">{{ __('dashboard.total_total_delivery_amount') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-tabular-nums">{{ $fmt($t_delivery_amount) }} {{ $currency }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Charts --}}
            <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-3 tw-mb-5">
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card">
                    <div class="tw-px-5 tw-py-3 tw-border-b tw-border-gray-100">
                        <h3 class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('dashboard.parcels_chart') ?: 'Parcels — last 8 days' }}</h3>
                    </div>
                    <div class="tw-p-4">
                        <div class="apexcharts" id="apexparcels"></div>
                    </div>
                </div>
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card">
                    <div class="tw-px-5 tw-py-3 tw-border-b tw-border-gray-100">
                        <h3 class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('dashboard.parcels_breakdown') ?: 'Parcels by status' }}</h3>
                    </div>
                    <div class="tw-p-4">
                        <div class="apexcharts" id="apexparcelspiechart" style="padding-bottom:120px"></div>
                    </div>
                </div>
            </div>

            {{-- All reports --}}
            <h2 class="tw-text-base tw-font-semibold tw-text-gray-800 tw-mb-3">{{ __('dashboard.all_reports') }}</h2>
            @php
                $reports = [
                    ['icon' => 'fa-hand-holding-usd', 'label' => __('dashboard.total_sales_amount'),        'value' => $fmt($t_sale),         'unit' => $currency, 'tint' => 'tw-bg-emerald-50 tw-text-emerald-600'],
                    ['icon' => 'fa-hands-helping',   'label' => __('dashboard.total_delivery_fees_paid'),  'value' => $fmt($t_delivery_fee), 'unit' => $currency, 'tint' => 'tw-bg-amber-50 tw-text-amber-600'],
                    ['icon' => 'fa-percent',         'label' => __('levels.total_vat'),                    'value' => $fmt($ts_vat),         'unit' => $currency, 'tint' => 'tw-bg-purple-50 tw-text-purple-600'],
                    ['icon' => 'fa-coins',           'label' => __('dashboard.net_profit_ammount'),        'value' => $fmt($totalProfit),    'unit' => $currency, 'tint' => $totalProfit >= 0 ? 'tw-bg-emerald-50 tw-text-emerald-600' : 'tw-bg-rose-50 tw-text-rose-600', 'highlight' => $totalProfit < 0],
                    ['icon' => 'fa-credit-card',     'label' => __('dashboard.current_balance'),           'value' => $fmt($computedBal),    'unit' => $currency, 'tint' => 'tw-bg-brand-50 tw-text-brand-600'],
                    ['icon' => 'fa-donate',          'label' => __('dashboard.opening_balance'),           'value' => $fmt($openingBal),     'unit' => $currency, 'tint' => 'tw-bg-sky-50 tw-text-sky-600'],
                    ['icon' => 'fa-percent',         'label' => __('dashboard.vat'),                       'value' => $fmt($merchantVat),    'unit' => $currency, 'tint' => 'tw-bg-purple-50 tw-text-purple-600'],
                    ['icon' => 'fa-hourglass-half',  'label' => __('dashboard.payment_processing'),        'value' => $fmt($t_balance_proc), 'unit' => $currency, 'tint' => 'tw-bg-amber-50 tw-text-amber-600'],
                    ['icon' => 'fa-database',        'label' => __('dashboard.paid_amount'),               'value' => $fmt($t_balance_paid), 'unit' => $currency, 'tint' => 'tw-bg-emerald-50 tw-text-emerald-600'],
                    ['icon' => 'fa-home',            'label' => __('dashboard.total_shop'),                'value' => number_format((int) $t_shop),         'unit' => '',        'tint' => 'tw-bg-indigo-50 tw-text-indigo-600'],
                    ['icon' => 'fa-boxes',           'label' => __('dashboard.total_parcel_bank_items'),   'value' => number_format((int) $t_parcel_bank),  'unit' => '',        'tint' => 'tw-bg-brand-50 tw-text-brand-600'],
                    ['icon' => 'fa-history',         'label' => __('dashboard.total_payment_request'),     'value' => number_format((int) $t_request),      'unit' => '',        'tint' => 'tw-bg-sky-50 tw-text-sky-600'],
                ];
            @endphp
            <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-3">
                @foreach ($reports as $r)
                    <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-p-4 hover:tw-shadow-card-hover tw-transition-shadow">
                        <div class="tw-flex tw-items-center tw-gap-3">
                            <span class="tw-shrink-0 tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center {{ $r['tint'] }}">
                                <i class="fa {{ $r['icon'] }} tw-text-lg"></i>
                            </span>
                            <div class="tw-min-w-0">
                                <div class="tw-text-[11px] tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-line-clamp-1">{{ $r['label'] }}</div>
                                <div class="tw-text-base tw-font-semibold tw-text-gray-900 tw-tabular-nums tw-truncate">
                                    {{ $r['value'] }}
                                    @if ($r['unit'])
                                        <span class="tw-text-xs tw-text-gray-500 tw-font-normal tw-ml-0.5">{{ $r['unit'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection()

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
@push('scripts')
    <script type="text/javascript" src="{{ static_asset('backend/js/charts/apexcharts.js') }}"></script>
    @include('backend.merchant_panel.dashboard-chart')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
@endpush
