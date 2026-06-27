@if (!blank($plan->modules))
    <div class="tw-mb-3">
        <h3 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-uppercase tw-tracking-wider tw-m-0">
            {{ __('levels.modules') }} <span class="tw-text-gray-400 tw-font-normal">({{ count($plan->modules) }})</span>
        </h3>
    </div>
    <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 tw-gap-2">
        @foreach ($plan->modules as $module)
            <div class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-bg-emerald-50/50 tw-border tw-border-emerald-100 tw-rounded-lg">
                <span class="tw-shrink-0 tw-w-5 tw-h-5 tw-rounded-full tw-bg-emerald-100 tw-text-emerald-700 tw-flex tw-items-center tw-justify-center">
                    <i class="fa fa-check tw-text-[10px]"></i>
                </span>
                <span class="tw-text-sm tw-text-gray-800 tw-truncate">{{ __('permissions.' . $module) }}</span>
            </div>
        @endforeach
    </div>
@else
    <div class="tw-text-center tw-py-8">
        <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-inbox tw-text-2xl"></i></div>
        <p class="tw-text-sm tw-text-gray-500 tw-m-0">Modules not found.</p>
    </div>
@endif
