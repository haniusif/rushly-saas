@extends('backend.partials.master')
@section('title')
    {{ __('merchant.dashboard') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 tw-pb-2 sm:tw-px-2">

            {{-- Header --}}
            <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-mb-6">
                <div>
                    <h1 class="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-mb-1">{{ __('merchant.dashboard') }}</h1>
                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">{{ __('levels.dashboard') }}</p>
                </div>
                <form action="{{ route('dashboard.index', ['test' => 'custom']) }}" method="get"
                      class="tw-flex tw-items-center tw-gap-2">
                    <input type="hidden" name="days" value="custom" />
                    <div class="tw-relative">
                        <i class="fa fa-calendar tw-absolute tw-top-1/2 tw-left-3 -tw-translate-y-1/2 tw-text-gray-400 tw-text-sm"></i>
                        <input type="text" name="filter_date" placeholder="YYYY-MM-DD" autocomplete="off"
                               class="date_range_picker tw-input tw-h-10 tw-pl-9 tw-pr-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-w-56"
                               value="{{ $request->filter_date }}" required />
                    </div>
                    <button type="submit"
                            class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-transition-colors">
                        <i class="fa fa-filter tw-text-xs"></i>
                        {{ __('levels.filter') }}
                    </button>
                </form>
            </div>

            {{-- KPI tiles --}}
            <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 xl:tw-grid-cols-4 tw-gap-4 tw-mb-6">
                @php
                    $kpis = [
                        ['route'=>route('company.index'), 'icon'=>'fa-solid fa-building', 'label'=>__('dashboard.total_company'), 'value'=>$total_company,                         'tint'=>'tw-bg-brand-50 tw-text-brand-600'],
                        ['route'=>route('plan.index'),    'icon'=>'fa-solid fa-grip-vertical', 'label'=>__('dashboard.total_plans'),   'value'=>$total_plans,                           'tint'=>'tw-bg-indigo-50 tw-text-indigo-600'],
                        ['route'=>'javascript:void(0)',   'icon'=>'fa fa-hands-helping',       'label'=>__('dashboard.total_subscription'), 'value'=>$total_subscription,               'tint'=>'tw-bg-emerald-50 tw-text-emerald-600'],
                        ['route'=>'javascript:void(0)',   'icon'=>'fa fa-donate',              'label'=>__('dashboard.total_subscription_price'), 'value'=>settings()->currency.' '.$total_subscription_amount, 'tint'=>'tw-bg-amber-50 tw-text-amber-600'],
                    ];
                @endphp
                @foreach ($kpis as $kpi)
                    <a href="{{ $kpi['route'] }}"
                       class="tw-group tw-block tw-no-underline tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-p-5 tw-shadow-card hover:tw-shadow-card-hover hover:-tw-translate-y-0.5 tw-transition-all">
                        <div class="tw-flex tw-items-center tw-gap-4">
                            <div class="tw-shrink-0 tw-w-12 tw-h-12 tw-rounded-xl tw-flex tw-items-center tw-justify-center {{ $kpi['tint'] }}">
                                <i class="{{ $kpi['icon'] }} tw-text-xl"></i>
                            </div>
                            <div class="tw-min-w-0">
                                <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-1">{{ $kpi['label'] }}</div>
                                <div class="tw-text-2xl tw-font-semibold tw-text-gray-900 tw-truncate">{{ $kpi['value'] }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Recent tables --}}
            <div class="tw-grid tw-grid-cols-1 xl:tw-grid-cols-2 tw-gap-4">

                {{-- Recent companies --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                        <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.recent_company') }}</h2>
                        <a href="{{ route('company.index') }}" class="tw-text-xs tw-font-medium tw-text-brand-600 hover:tw-text-brand-700 tw-no-underline">
                            {{ __('levels.list') }} <i class="fa fa-arrow-right tw-text-[10px] tw-rtl-flip"></i>
                        </a>
                    </div>
                    <div class="tw-overflow-x-auto">
                        <table class="tw-w-full tw-text-sm">
                            <thead class="tw-bg-gray-50">
                                <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.name') }}</th>
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.user_details') }}</th>
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.modules') }}</th>
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tw-divide-y tw-divide-gray-100">
                                @forelse ($recent_companies as $company)
                                    <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                        <td class="tw-px-5 tw-py-3">
                                            <div class="tw-flex tw-items-center tw-gap-3">
                                                <img src="{{ $company->company->LogoImage }}" alt="logo" class="tw-w-9 tw-h-9 tw-rounded-lg tw-object-cover tw-bg-gray-100" />
                                                <span class="tw-font-medium tw-text-gray-900 tw-truncate">{{ @$company->company->name }}</span>
                                            </div>
                                        </td>
                                        <td class="tw-px-5 tw-py-3">
                                            <div class="tw-flex tw-items-center tw-gap-3">
                                                <img src="{{ $company->image }}" alt="user" class="tw-w-9 tw-h-9 tw-rounded-full tw-object-cover tw-bg-gray-100" />
                                                <div class="tw-min-w-0">
                                                    <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ $company->name }}</div>
                                                    <div class="tw-text-xs tw-text-gray-500 tw-truncate">{{ $company->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="tw-px-5 tw-py-3">
                                            @if (!empty($company->company->plan))
                                                <span class="tw-inline-flex tw-items-center tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-full tw-bg-brand-50 tw-text-brand-700">
                                                    {{ count($company->company->plan->modules) }}
                                                </span>
                                            @else
                                                <span class="tw-text-xs tw-text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="tw-px-5 tw-py-3">{!! $company->my_status !!}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="tw-px-5 tw-py-10 tw-text-center tw-text-sm tw-text-gray-400">No recent companies.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Recent subscriptions --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                        <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.recent_subscriptions') }}</h2>
                    </div>
                    <div class="tw-overflow-x-auto">
                        <table class="tw-w-full tw-text-sm">
                            <thead class="tw-bg-gray-50">
                                <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.company') }}</th>
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.plan') }}</th>
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.price') }}</th>
                                    <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.expired_date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tw-divide-y tw-divide-gray-100">
                                @forelse ($subscriptions as $subscription)
                                    <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                        <td class="tw-px-5 tw-py-3 tw-font-medium tw-text-gray-900">{{ $subscription->company?->name ?? '—' }}</td>
                                        <td class="tw-px-5 tw-py-3 tw-text-gray-700">{{ $subscription->plan?->name ?? '—' }}</td>
                                        <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-text-gray-900 tw-font-medium">
                                            {{ settings()->currency }} {{ $subscription->price }}
                                        </td>
                                        <td class="tw-px-5 tw-py-3 tw-text-xs tw-text-gray-500 tw-tabular-nums">{{ $subscription->expired_date }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="tw-px-5 tw-py-10 tw-text-center tw-text-sm tw-text-gray-400">No recent subscriptions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection()

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
@push('scripts')
    <script type="text/javascript" src="{{ static_asset('backend/js/charts/apexcharts.js') }}"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
@endpush
