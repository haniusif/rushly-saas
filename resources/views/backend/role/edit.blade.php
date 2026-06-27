@extends('backend.partials.master')
@section('title')
    {{ __('role.title') }} {{ __('levels.edit') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            {{-- Breadcrumb --}}
            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700">{{ __('menus.user_role') }}</span>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('roles.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('role.title') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('levels.edit') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100">
                    <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('role.edit_role') }}</h1>
                    <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ $role->name }}</p>
                </div>

                <form action="{{ route('roles.update', ['id' => $role->id]) }}" method="POST" enctype="multipart/form-data" id="basicform">
                    @csrf
                    @method('PUT')
                    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-6 tw-p-6">

                        <div class="lg:tw-col-span-4 tw-space-y-5">
                            <div>
                                <label for="name" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                    {{ __('levels.name') }} <span class="tw-text-red-500">*</span>
                                </label>
                                <input id="name" type="text" name="name"
                                       placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off"
                                       class="tw-input tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('name') tw-border-red-300 @else tw-border-gray-200 @enderror"
                                       value="{{ old('name', $role->name) }}" required>
                                @error('name')
                                    <small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                            <div>
                                <label for="status" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1.5">
                                    {{ __('levels.status') }} <span class="tw-text-red-500">*</span>
                                </label>
                                <select name="status" id="status"
                                        class="tw-select tw-w-full tw-h-10 tw-px-3 tw-text-sm tw-bg-white tw-border tw-rounded-lg @error('status') tw-border-red-300 @else tw-border-gray-200 @enderror">
                                    @foreach (trans('status') as $key => $status)
                                        <option value="{{ $key }}" {{ old('status', $role->status) == $key ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <small class="tw-block tw-text-xs tw-text-red-500 tw-mt-1">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="lg:tw-col-span-8">
                            <div class="tw-border tw-border-gray-200 tw-rounded-lg tw-overflow-hidden">
                                <div class="tw-grid tw-grid-cols-12 tw-bg-gray-50 tw-px-4 tw-py-2.5 tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500">
                                    <div class="tw-col-span-4">{{ __('permissions.modules') }}</div>
                                    <div class="tw-col-span-8">{{ __('permissions.permissions') }}</div>
                                </div>
                                <div class="tw-divide-y tw-divide-gray-100">
                                    @foreach ($permissions as $permission)
                                        <div class="tw-grid tw-grid-cols-12 tw-px-4 tw-py-3 tw-items-start">
                                            <div class="tw-col-span-4 tw-text-sm tw-font-medium tw-text-gray-800 tw-pt-1">
                                                {{ __('permissions.' . $permission->attribute) }}
                                            </div>
                                            <div class="tw-col-span-8 tw-flex tw-flex-wrap tw-gap-2">
                                                @foreach ($permission->keywords as $key => $keyword)
                                                    @php $checked = $role->permissions !== null && in_array($keyword, $role->permissions); @endphp
                                                    <label for="{{ $keyword }}"
                                                           class="tw-inline-flex tw-items-center tw-gap-2 tw-px-3 tw-py-1.5 tw-rounded-full {{ $checked ? 'tw-bg-brand-50 tw-border-brand-200 tw-text-brand-700' : 'tw-bg-gray-50 tw-border-gray-200 tw-text-gray-700' }} hover:tw-bg-brand-50 hover:tw-border-brand-200 tw-border tw-cursor-pointer tw-transition-colors tw-text-xs tw-font-medium">
                                                        <input id="{{ $keyword }}" class="read common-key tw-w-3.5 tw-h-3.5 tw-accent-brand-600" type="checkbox"
                                                               value="{{ $keyword }}" name="permissions[]"
                                                               @if ($checked) checked @endif />
                                                        {{ __('permissions.' . $key) }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 tw-px-6 tw-py-4 tw-border-t tw-border-gray-100 tw-bg-gray-50">
                        <a href="{{ route('roles.index') }}"
                           class="tw-inline-flex tw-items-center tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-gray-700 tw-bg-white hover:tw-bg-gray-100 tw-border tw-border-gray-200 tw-rounded-lg tw-no-underline">
                            {{ __('levels.cancel') }}
                        </a>
                        <button type="submit"
                                class="tw-inline-flex tw-items-center tw-h-10 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-border-0">
                            {{ __('levels.save_change') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection()
@push('scripts')
    <script src="{{ static_asset('backend/js/roles/roles.js') }}"></script>
@endpush
