@extends('backend.partials.master')
@section('title')
    {{ __('levels.plans') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('plan.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.plans') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.permissions') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                    <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.permissions') }}</h1>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ __('levels.select_modules') }}</p>
                </div>
                <form action="{{ route('company.permissions', ['id' => $id]) }}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    @method('put')
                    <div class="tw-p-6">

                        {{-- Select all bar --}}
                        <label for="selectAllModules"
                               class="tw-flex tw-items-center tw-gap-3 tw-px-4 tw-py-3 tw-mb-4 tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-cursor-pointer">
                            <input id="selectAllModules" class="read common-key tw-w-4 tw-h-4 tw-accent-brand-600" type="checkbox" />
                            <span class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('levels.select_all') }}</span>
                        </label>

                        <div class="check-module tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-2">
                            @foreach ($modules as $module)
                                <label for="{{ @$module }}"
                                       class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-bg-white hover:tw-bg-brand-50 tw-border tw-border-gray-200 hover:tw-border-brand-200 tw-rounded-lg tw-cursor-pointer tw-transition-colors">
                                    <input id="{{ @$module }}" class="read module common-key tw-w-4 tw-h-4 tw-accent-brand-600" type="checkbox" value="{{ @$module }}" name="modules[]" />
                                    <span class="tw-text-sm tw-text-gray-800 tw-truncate">{{ __('permissions.' . $module) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                        <a href="{{ route('company.index') }}"
                           class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">
                            {{ __('levels.cancel') }}
                        </a>
                        <button type="submit"
                                class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">
                            {{ __('levels.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection()
@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $(document).on('change', '#selectAllModules', function() {
                if ($(this).is(':checked')) {
                    $('.check-module').find('.common-key').prop('checked', true);
                } else {
                    $('.check-module').find('.common-key').prop('checked', false);
                }
            });
        });
    </script>
@endpush
