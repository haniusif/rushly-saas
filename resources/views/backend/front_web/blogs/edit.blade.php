@extends('backend.partials.master')
@section('title')
    {{ __('levels.blog') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <span class="tw-text-gray-700">{{ __('levels.front_web') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('blogs.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.blog') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.edit') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                    @if (@$blog->image)
                        <img src="{{ $blog->image }}" alt="" class="tw-w-10 tw-h-10 tw-rounded-lg tw-object-cover tw-bg-gray-100" />
                    @endif
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.blog') }} {{ __('levels.edit') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0 tw-line-clamp-1">{{ @$blog->title }}</p>
                    </div>
                </div>
                <form action="{{ route('blogs.update', $blog->id) }}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    @method('put')
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5 tw-p-6">
                        <div>
                            <label for="title" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.title') }} <span class="tw-text-red-500">*</span></label>
                            <input id="title" type="text" name="title" placeholder="{{ __('placeholder.Enter_title') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('title') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('title', @$blog->title) }}" required>
                            @error('title')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="image" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.image') }}
                                <span class="tw-text-xs tw-font-normal tw-text-gray-400">(leave empty to keep current)</span>
                            </label>
                            <input id="image" type="file" name="image" accept="image/*"
                                   class="tw-input tw-w-full tw-h-10 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('image') tw-border-red-300 @else tw-border-gray-200 @enderror">
                            @error('image')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="position" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.position') }}</label>
                            <input id="position" type="text" name="position" placeholder="{{ __('placeholder.Enter_Position') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('position') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('position', $blog->position) }}">
                            @error('position')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div>
                            <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.status') }} <span class="tw-text-red-500">*</span></label>
                            <select name="status"
                                    class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('status') tw-border-red-300 @else tw-border-gray-200 @enderror">
                                @foreach (trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ old('status', $blog->status) == $key ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div class="md:tw-col-span-2">
                            <label for="summernote" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.description') }} <span class="tw-text-red-500">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="summernote" rows="12">{{ old('description', @$blog->description) }}</textarea>
                            @error('description')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                        <a href="{{ route('blogs.index') }}" class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">{{ __('levels.cancel') }}</a>
                        <button type="submit" class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">{{ __('levels.update') }}</button>
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
