@extends('backend.partials.master')
@section('title')
    {{ __('settings.currency') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700">{{ __('menus.settings') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('currency.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('settings.currency') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.edit') }}</span>
            </nav>

            <div class="tw-max-w-2xl">
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('settings.currency') }} {{ __('levels.edit') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ @$currency->name }} <span class="tw-text-gray-400">·</span> {!! @$currency->symbol !!}</p>
                    </div>
                    <form action="{{ route('currency.update', ['id' => $currency->id]) }}" method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="tw-p-6 tw-space-y-5">
                            <div>
                                <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.name') }} <span class="tw-text-red-500">*</span></label>
                                <input id="name" type="text" name="name" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off"
                                       class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('name') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                       value="{{ old('name', $currency->name) }}" required>
                                @error('name')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div>
                                <label for="symbol" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('settings.symbol') }} <span class="tw-text-red-500">*</span></label>
                                <input id="symbol" type="text" name="symbol" placeholder="{{ __('settings.enter_symbol') }}" autocomplete="off"
                                       class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('symbol') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                       value="{{ old('symbol', $currency->symbol) }}">
                                @error('symbol')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div>
                                <label for="exchange_rate" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('settings.exchange_rate') }} <span class="tw-text-red-500">*</span></label>
                                <input id="exchange_rate" type="text" name="exchange_rate" placeholder="{{ __('settings.enter_exchange_rate') }}" autocomplete="off"
                                       class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('exchange_rate') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                       value="{{ old('exchange_rate', $currency->exchange_rate) }}">
                                @error('exchange_rate')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div>
                                <label for="position" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.position') }}</label>
                                <input id="position" type="number" name="position" placeholder="{{ __('placeholder.Enter_Position') }}" autocomplete="off"
                                       class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg @error('position') tw-border-red-300 @enderror"
                                       value="{{ old('position', $currency->position) }}">
                                @error('position')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                            </div>
                            <div>
                                <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.status') }} <span class="tw-text-red-500">*</span></label>
                                <select name="status"
                                        class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('status') tw-border-red-300 @else tw-border-gray-200 @enderror">
                                    @foreach (trans('status') as $key => $status)
                                        <option value="{{ $key }}" {{ old('status', $currency->status) == $key ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('status')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                            <a href="{{ route('currency.index') }}" class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">{{ __('levels.cancel') }}</a>
                            <button type="submit" class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">{{ __('levels.save_change') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection()
