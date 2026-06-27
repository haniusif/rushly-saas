@extends('backend.partials.master')
@section('title')
    {{ $section_type }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('section.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.sections') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ @$section_type }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                    <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ @$section_type }} {{ __('levels.edit') }}</h1>
                </div>
                <form action="{{ route('section.update', $type) }}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    @method('put')
                    <div class="tw-p-6">
                        @if ($type == App\Enums\SectionType::BANNER)
                            @include('backend.front_web.section.banner')
                        @elseif($type == App\Enums\SectionType::ACHIEVEMENT)
                            @include('backend.front_web.section.achievement')
                        @elseif($type == App\Enums\SectionType::ABOUT)
                            @include('backend.front_web.section.about')
                        @elseif($type == App\Enums\SectionType::SUBSCRIBE)
                            @include('backend.front_web.section.subscribe')
                        @elseif($type == App\Enums\SectionType::APP_LINK)
                            @include('backend.front_web.section.app_link')
                        @elseif($type == App\Enums\SectionType::MAP_LINK)
                            @include('backend.front_web.section.map_link')
                        @endif
                    </div>
                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                        <a href="{{ route('section.index') }}" class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">{{ __('levels.cancel') }}</a>
                        <button type="submit" class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">{{ __('levels.update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                placeholder: '{{ __("placeholder.Enter_description")}}',
                height: 220
            });
        });
    </script>
@endpush
