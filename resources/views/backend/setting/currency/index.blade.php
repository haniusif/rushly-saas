@extends('backend.partials.master')
@section('title')
    {{ __('settings.currency') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700">{{ __('menus.settings') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('settings.currency') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('settings.currency') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ @$currencies->total() ?? count($currencies) }} {{ __('Showing') }}</p>
                    </div>
                    @if (hasPermission('currency_create'))
                        <a href="{{ route('currency.create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i> {{ __('levels.add') }}
                        </a>
                    @endif
                </div>

                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.name') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('settings.symbol') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('settings.exchange_rate') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.position') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                @if (hasPermission('currency_update') || hasPermission('currency_delete'))
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = method_exists($currencies, 'firstItem') ? $currencies->firstItem() : 1; @endphp
                            @forelse ($currencies as $currency)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-font-medium tw-text-gray-900">{{ @$currency->name }}</td>
                                    <td class="tw-px-5 tw-py-3">
                                        <span class="tw-inline-flex tw-items-center tw-justify-center tw-min-w-[28px] tw-h-7 tw-px-2 tw-text-xs tw-font-semibold tw-text-gray-700 tw-bg-gray-100 tw-rounded">{!! @$currency->symbol !!}</span>
                                    </td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-text-gray-700">{{ number_format(@$currency->exchange_rate, 2) }}</td>
                                    <td class="tw-px-5 tw-py-3 tw-text-right tw-tabular-nums tw-text-gray-700">{{ @$currency->position }}</td>
                                    <td class="tw-px-5 tw-py-3">{!! @$currency->my_status !!}</td>
                                    @if (hasPermission('currency_update') || hasPermission('currency_delete'))
                                        <td class="tw-px-5 tw-py-3 tw-text-right">
                                            <div class="dropdown tw-inline-block">
                                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if (hasPermission('currency_update'))
                                                        <a href="{{ route('currency.edit', $currency->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('currency_delete'))
                                                        <form id="delete" action="{{ route('currency.delete', $currency->id) }}" method="POST" data-title="{{ __('Do you want to delete currency ?') }}">
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
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="7" class="tw-px-5 tw-py-12 tw-text-center">
                                    <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-money tw-text-3xl"></i></div>
                                    <p class="tw-text-sm tw-text-gray-500 tw-m-0">No currencies yet.</p>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($currencies, 'hasPages') && $currencies->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!} <span class="tw-font-medium tw-text-gray-700">{{ $currencies->firstItem() }}</span>
                            {!! __('to') !!} <span class="tw-font-medium tw-text-gray-700">{{ $currencies->lastItem() }}</span>
                            {!! __('of') !!} <span class="tw-font-medium tw-text-gray-700">{{ $currencies->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $currencies->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection()
