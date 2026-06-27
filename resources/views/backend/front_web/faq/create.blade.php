@extends('backend.partials.master')
@section('title')
    {{ __('levels.faq') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('faq.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.faq') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.create') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                    <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.faq') }} {{ __('levels.add') }}</h1>
                </div>
                <form action="{{ route('faq.store') }}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    <div class="tw-p-6 tw-space-y-5">
                        <div>
                            <label for="question" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.question') }} <span class="tw-text-red-500">*</span></label>
                            <input id="question" type="text" name="question" placeholder="{{ __('levels.Enter_question') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('question') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('question') }}" required>
                            @error('question')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="summernote" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.answer') }} <span class="tw-text-red-500">*</span></label>
                            <textarea class="form-control @error('answer') is-invalid @enderror" name="answer" id="summernote" rows="12"></textarea>
                            @error('answer')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5">
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
                    </div>
                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                        <a href="{{ route('faq.index') }}" class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">{{ __('levels.cancel') }}</a>
                        <button type="submit" class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">{{ __('levels.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ static_asset('backend/vendor/summernote/css') }}/summernote-lite.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ static_asset('backend/vendor/summernote/js') }}/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: '{{ __('placeholder.Enter_description') }}',
                height: 220
            });
        });
    </script>
@endpush
