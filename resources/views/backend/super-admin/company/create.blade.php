@extends('backend.partials.master')
@section('title')
    {{ __('levels.company') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('company.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('menus.company') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.create') }}</span>
            </nav>

            <form action="{{ route('company.store') }}" method="POST" enctype="multipart/form-data" id="basicform" class="tw-space-y-4">
                @csrf

                {{-- Company section --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                        <span class="tw-shrink-0 tw-w-9 tw-h-9 tw-rounded-lg tw-bg-brand-50 tw-text-brand-600 tw-flex tw-items-center tw-justify-center">
                            <i class="fa fa-building"></i>
                        </span>
                        <div>
                            <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.company') }} {{ __('levels.information') }}</h2>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">Basic identity, domain, and plan.</p>
                        </div>
                    </div>
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5 tw-p-6">

                        <div>
                            <label for="company_name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.company') }} {{ __('levels.name') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="company_name" type="text" name="company_name"
                                   placeholder="{{ __('placeholder.Enter_company_name') }}" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('company_name') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('company_name') }}" required>
                            @error('company_name')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="domain" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.domain') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <div class="tw-flex tw-items-stretch tw-h-10 tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg tw-overflow-hidden focus-within:tw-border-brand-600 focus-within:tw-ring-2 focus-within:tw-ring-brand-100">
                                <span class="tw-inline-flex tw-items-center tw-px-3 tw-text-xs tw-bg-gray-50 tw-text-gray-500 tw-border-r tw-border-gray-200">{{ scheme_name() }}</span>
                                <input id="domain" type="text" name="domain"
                                       placeholder="{{ __('placeholder.Enter_domain') }}" autocomplete="off"
                                       class="tw-flex-1 tw-h-full tw-px-3 tw-text-sm tw-border-0 tw-outline-none focus:tw-ring-0"
                                       value="{{ old('domain') }}" required>
                                <span class="tw-inline-flex tw-items-center tw-px-3 tw-text-xs tw-bg-gray-50 tw-text-gray-500 tw-border-l tw-border-gray-200">{{ '.' . get_host() }}</span>
                            </div>
                            @error('domain')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="currency" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.currency') }}</label>
                            <select class="select2 tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('currency') tw-border-red-300 @else tw-border-gray-200 @enderror" id="currency" name="currency" required>
                                <option value="" selected disabled>Select Currency</option>
                                @forelse ($currencies as $currency)
                                    <option value="{{ $currency->symbol }}">{{ @$currency->name }} {{ @$currency->symbol }}</option>
                                @empty
                                    <option value="&#36;">Dollar &#36;</option>
                                @endforelse
                            </select>
                            @error('currency')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="plan_id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.plan') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <select class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('plan_id') tw-border-red-300 @else tw-border-gray-200 @enderror" name="plan_id" required>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                            @error('plan_id')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('settings.parcel_tracking') }} {{ __('levels.prefix') }}</label>
                            <input type="text" name="par_track_prefix" class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg" placeholder="Enter Parcel Tracking Prefix" />
                        </div>

                        <div>
                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('invoice.invoice') }} {{ __('levels.prefix') }}</label>
                            <input type="text" name="invoice_prefix" class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg" placeholder="Enter Invoice Prefix" />
                        </div>

                        <div class="md:tw-col-span-2">
                            <label for="logo" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.logo') }}</label>
                            <input id="logo" type="file" name="logo"
                                   class="tw-input tw-w-full tw-h-10 tw-text-sm tw-file:tw-mr-3 tw-file:tw-bg-gray-100 tw-file:tw-text-gray-700 tw-file:tw-px-3 tw-file:tw-h-10 tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg @error('logo') tw-border-red-300 @enderror">
                            @error('logo')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                    </div>
                </div>

                {{-- User section --}}
                <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                    <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                        <span class="tw-shrink-0 tw-w-9 tw-h-9 tw-rounded-lg tw-bg-indigo-50 tw-text-indigo-600 tw-flex tw-items-center tw-justify-center">
                            <i class="fa fa-user"></i>
                        </span>
                        <div>
                            <h2 class="tw-text-base tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.user') }} {{ __('levels.information') }}</h2>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">Primary admin user for this tenant.</p>
                        </div>
                    </div>
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-5 tw-p-6">

                        <div>
                            <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.name') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="name" type="text" name="name" autocomplete="off"
                                   placeholder="{{ __('placeholder.Enter_name') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('name') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="email" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.email') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="email" type="email" name="email" autocomplete="off"
                                   placeholder="{{ __('placeholder.enter_email') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('email') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="mobile" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.phone') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="mobile" type="number" name="mobile" autocomplete="off"
                                   placeholder="{{ __('placeholder.Enter_mobile') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('mobile') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('mobile') }}" required>
                            @error('mobile')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="password" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.password') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="password" type="password" name="password" autocomplete="off"
                                   placeholder="{{ __('placeholder.Enter_password') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('password') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('password') }}" required>
                            @error('password')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="address" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.address') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="address" type="text" name="address" autocomplete="off"
                                   placeholder="{{ __('placeholder.Enter_address') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('address') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('address') }}" required>
                            @error('address')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="nid_number" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.nid') }}</label>
                            <input id="nid_number" type="number" name="nid_number" autocomplete="off"
                                   placeholder="{{ __('placeholder.Enter_nid_number') }}"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-border-gray-200 tw-rounded-lg @error('nid_number') tw-border-red-300 @enderror"
                                   value="{{ old('nid_number') }}">
                            @error('nid_number')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.designation') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <select class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('designation_id') tw-border-red-300 @else tw-border-gray-200 @enderror" name="designation_id" required>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}" {{ old('designation_id') == $designation->id ? 'selected' : '' }}>{{ $designation->title }}</option>
                                @endforeach
                            </select>
                            @error('designation_id')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.department') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <select class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('department_id') tw-border-red-300 @else tw-border-gray-200 @enderror" name="department_id" required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->title }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="joining_date" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                {{ __('levels.opening_date') }} <span class="tw-text-red-500">*</span>
                            </label>
                            <input id="joining_date" type="text" readonly data-toggle="datepicker" name="joining_date"
                                   placeholder="yyyy-mm-dd" autocomplete="off"
                                   class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('joining_date') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                   value="{{ old('joining_date', date('Y-m-d')) }}" required>
                            @error('joining_date')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.status') }}</label>
                            <select name="status" id="status"
                                    class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('status') tw-border-red-300 @else tw-border-gray-200 @enderror">
                                @foreach (trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ old('status', \App\Enums\Status::ACTIVE) == $key ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                        <div class="md:tw-col-span-2">
                            <label for="image" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">{{ __('levels.image') }}</label>
                            <input id="image" type="file" name="image"
                                   class="tw-input tw-w-full tw-h-10 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('image') tw-border-red-300 @else tw-border-gray-200 @enderror">
                            @error('image')<small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>@enderror
                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-px-6 tw-py-4">
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
@endsection()
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
