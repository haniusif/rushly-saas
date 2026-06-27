@extends('backend.partials.master')
@section('title')
    {{ __('levels.why_courier') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('why.rushly.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.why_courier') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.create') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                    <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.why_courier') }} {{ __('levels.add') }}</h1>
                </div>
                <form action="{{ route('why.rushly.store') }}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5 tw-p-6">
                        <div>
                            <label for="title" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.title') }} <span class="tw-text-red-500">*</span></label>
                            <input id="title" type="text" name="title" placeholder="{{ __('placeholder.Enter_title') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('title') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('title') }}" required>
                            @error('title')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="image" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.image') }} <span class="tw-text-red-500">*</span></label>
                            <input id="image" type="file" name="image" accept="image/*"
                                   class="tw-input tw-w-full tw-h-10 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('image') tw-border-red-300 @else tw-border-gray-200 @enderror">
                            @error('image')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="position" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.position') }}</label>
                            <input id="position" type="text" name="position" placeholder="{{ __('placeholder.Enter_Position') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('position') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('position') }}">
                            @error('position')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.status') }} <span class="tw-text-red-500">*</span></label>
                            <select name="status"
                                    class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('status') tw-border-red-300 @else tw-border-gray-200 @enderror">
                                @foreach (trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ old('status', \App\Enums\Status::ACTIVE) == $key ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                        <a href="{{ route('why.rushly.index') }}" class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">{{ __('levels.cancel') }}</a>
                        <button type="submit" class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">{{ __('levels.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
