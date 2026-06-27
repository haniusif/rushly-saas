<form action="{{ route('company.subscription.switch.store', ['user_id' => $user_id]) }}" method="post" class="tw-space-y-4">
    @csrf

    <div class="tw-flex tw-items-center tw-gap-3 tw-px-4 tw-py-3 tw-bg-brand-50 tw-text-brand-800 tw-rounded-lg">
        <i class="fa fa-info-circle tw-text-brand-600"></i>
        <div class="tw-text-sm">
            <span class="tw-text-gray-600">{{ __('levels.current_plan') }}:</span>
            <span class="tw-font-semibold tw-ml-1">{{ @$plan->name ?? '—' }}</span>
        </div>
    </div>

    <div>
        <label for="plan_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
            {{ __('levels.plan') }} <span class="tw-text-red-500">*</span>
        </label>
        <select id="plan_id" name="plan_id" required
                class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('plan_id') tw-border-red-300 @else tw-border-gray-200 @enderror">
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
            @endforeach
        </select>
        @error('plan_id')
            <small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>
        @enderror
    </div>

    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-pt-2">
        <button type="submit"
                class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">
            {{ __('levels.save') }}
        </button>
    </div>
</form>
