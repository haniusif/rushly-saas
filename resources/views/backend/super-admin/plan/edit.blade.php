@extends('backend.partials.master')
@section('title')
    {{ __('levels.plans') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('plan.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.plans') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.edit') }}</span>
            </nav>

            <form action="{{ route('plan.update', ['id' => $plan->id]) }}" method="POST" enctype="multipart/form-data" id="basicform" class="tw-space-y-4">
                @csrf
                @method('put')

                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                        <span class="tw-shrink-0 tw-w-9 tw-h-9 tw-rounded-lg tw-bg-brand-50 tw-text-brand-600 tw-flex tw-items-center tw-justify-center">
                            <i class="fa fa-grip-vertical"></i>
                        </span>
                        <div>
                            <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.edit') }} {{ __('levels.plans') }}</h2>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">{{ $plan->name }}</p>
                        </div>
                    </div>
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5 tw-p-6">

                        <div>
                            <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.name') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   placeholder="{{ __('levels.enter_name') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('name') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('name', $plan->name) }}">
                            @error('name')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="price" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.price') }} <span class="tw-text-red-500">*</span>
                                <span class="tw-text-xs tw-font-normal tw-text-gray-400">(min $0.50)</span>
                            </label>
                            <input type="text" id="price" name="price"
                                   placeholder="{{ __('levels.price') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('price') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('price', $plan->price) }}">
                            @error('price')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="parcel_count" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.parcel_count') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input type="text" id="parcel_count" name="parcel_count"
                                   placeholder="{{ __('levels.parcel_count') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('parcel_count') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('parcel_count', $plan->parcel_count) }}">
                            @error('parcel_count')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="deliveryman_count" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.max_deliveryman') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input type="text" id="deliveryman_count" name="deliveryman_count"
                                   placeholder="{{ __('levels.deliveryman_count') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('deliveryman_count') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('deliveryman_count', @$plan->deliveryman_count) }}">
                            @error('deliveryman_count')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="days_count" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.days_count') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input type="text" id="days_count" name="days_count"
                                   placeholder="{{ __('levels.days_count') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('days_count') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('days_count', $plan->days_count) }}">
                            @error('days_count')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="position" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.position') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input type="text" id="position" name="position"
                                   placeholder="{{ __('levels.position') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('position') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('position', $plan->position) }}">
                            @error('position')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.status') }}</label>
                            <select name="status" id="status"
                                    class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('status') tw-border-red-300 @else tw-border-gray-200 @enderror">
                                @foreach (trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ old('status', $plan->status) == $key ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div class="md:tw-col-span-2">
                            <label for="description" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.description') }}</label>
                            <textarea class="tw-textarea tw-w-full tw-px-3 tw-py-2 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg"
                                      placeholder="{{ __('placeholder.Enter_description') }}" name="description" rows="3">{{ $plan->description }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Modules --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                        <span class="tw-shrink-0 tw-w-9 tw-h-9 tw-rounded-lg tw-bg-indigo-50 tw-text-indigo-600 tw-flex tw-items-center tw-justify-center">
                            <i class="fa fa-cubes"></i>
                        </span>
                        <div>
                            <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.select_modules') }}</h2>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">{{ count($plan->modules ?? []) }} {{ __('levels.modules') }} selected.</p>
                        </div>
                    </div>
                    <div class="tw-p-6">
                        <label for="selectAllModules"
                               class="tw-flex tw-items-center tw-gap-3 tw-px-4 tw-py-3 tw-mb-4 tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-cursor-pointer">
                            <input id="selectAllModules" class="read common-key tw-w-4 tw-h-4 tw-accent-brand-600" type="checkbox" />
                            <span class="tw-text-sm tw-font-medium tw-text-gray-800">{{ __('levels.select_all') }}</span>
                        </label>
                        <div class="check-module tw-grid tw-grid-cols-2 md:tw-grid-cols-3 lg:tw-grid-cols-4 tw-gap-2">
                            @foreach ($modules as $module)
                                @php $checked = in_array($module, $plan->modules ?? []); @endphp
                                <label for="{{ @$module }}"
                                       class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 {{ $checked ? 'tw-bg-brand-50 tw-border-brand-200' : 'tw-bg-white tw-border-gray-200' }} hover:tw-bg-brand-50 hover:tw-border-brand-200 tw-border tw-rounded-lg tw-cursor-pointer tw-transition-colors">
                                    <input id="{{ @$module }}" class="read module common-key tw-w-4 tw-h-4 tw-accent-brand-600" type="checkbox" value="{{ @$module }}" name="modules[]"
                                           @if ($checked) checked @endif />
                                    <span class="tw-text-sm tw-text-gray-800 tw-truncate">{{ __('permissions.' . $module) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-px-6 tw-py-4">
                    <a href="{{ route('plan.index') }}"
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
